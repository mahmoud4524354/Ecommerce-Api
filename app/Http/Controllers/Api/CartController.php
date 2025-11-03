<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartItemResource;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        $cartItems = $cart->items()->with('product')->get();

        return ApiResponse::sendResponse(CartItemResource::collection($cartItems), 'Cart retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        $item = $cart->items()->where('product_id', $request->product_id)->first();

        if ($item) {
            $item->quantity += $request->quantity;
            $item->save();
            $cartItem = $item;
        } else {
            $cartItem = $cart->items()->create([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        return ApiResponse::sendResponse(new CartItemResource($cartItem), 'Item added to cart successfully.');
    }


    /**
     * Update the specified resource in storage.
     */
    public function updateCartItems(Request $request, $itemId)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $item = CartItem::whereHas('cart', fn($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($itemId);

        $item->update($data);

        return ApiResponse::sendResponse(new CartItemResource($item), 'Item updated successfully.');
    }

    public function removeItem($itemId)
    {
        $cart = Cart::where('user_id', Auth::id())->first();
        $item = $cart->items()->find($itemId);
        $item->delete();
        return ApiResponse::sendResponse([], 'Item removed successfully.');
    }


    public function clearCart(Cart $cart)
    {
        $cart = Cart::where('user_id', Auth::id())->first();

        if ($cart) {
            $cart->items()->delete();
        }

        return ApiResponse::sendResponse([], 'Cart cleared successfully.');
    }
}
