<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function checkout(CheckoutRequest $request)
    {
        $user = $request->user();
        $cartItems = $user->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return ApiResponse::sendResponse([], 'Cart is empty');
        }

        $subtotal = 0;
        $items = [];

        foreach ($cartItems as $cartItem) {

            $product = $cartItem->product;

            if (!$product->is_active) {
                return ApiResponse::sendResponse([], "Product '{$product->name}' is no longer available");
            }

            if ($product->stock < $cartItem->quantity) {
                return ApiResponse::sendResponse([], "Product '{$product->name}' is out of stock");
            }

            $itemSubtotal = $product->price * $cartItem->quantity;
            $subtotal += $itemSubtotal;


        }
    }


    public function orderHistory(Request $request)
    {
        $orders = $request->user()->orders()->with('items')->orderBy('created_at', 'desc')->get();

        if ($orders->isEmpty()) {
            return ApiResponse::sendResponse([], 'Orders is empty');
        }
        return ApiResponse::sendResponse(OrderResource::collection($orders), 'Orders list');
    }


    public function orderDetails(Request $request, $orderId)
    {
        $order = $request->user()->orders()->with('items')->findOrFail($orderId);

        return ApiResponse::sendResponse(new OrderResource($order), 'Order details');
    }

}
