<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $cartItem->quantity,
                'price' => $product->price,
                'subtotal' => $itemSubtotal,
            ];
        }

        $tax = $subtotal * 0.1; // 10%
        $shippingCost = 50;
        $total = $subtotal + $tax + $shippingCost;

        DB::beginTransaction();
        try {
            $order = new Order([
                'user_id' => $user->id,
                'status' => 'pending',
                'shipping_name' => $request->shipping_name,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_zipcode' => $request->shipping_zipcode,
                'shipping_country' => $request->shipping_country,
                'shipping_phone' => $request->shipping_phone,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'order_number' => Order::generateOrderNumber(),
                'notes' => $request->notes,
            ]);

            $user->orders()->save($order);

            foreach ($items as $item) {
                $order->items()->create($item);
                Product::find($item['product_id'])->decrement('stock', $item['quantity']);
            }

            $user->cartItems()->delete();
            DB::commit();

            return ApiResponse::sendResponse($order->load('items.product'), 'Order created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::sendResponse([], 'Error: ' . $e->getMessage());
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
