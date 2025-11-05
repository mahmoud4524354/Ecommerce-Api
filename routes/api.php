<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderManagementController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductFilterController;
use Illuminate\Support\Facades\Route;


Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
    Route::get('/user', 'user')->middleware('auth:sanctum');
});


Route::apiResource('products', ProductController::class)->only([
    'index', 'show'
]);

Route::middleware(['auth:sanctum', 'permission:create products'])->group(function () {

    Route::patch('products/{id}/restore', [ProductController::class, 'restore']);
    Route::post('/products/{id}/forceDelete', [ProductController::class, 'forceDelete']);
    Route::get('/products/trashed', [ProductController::class, 'trashed']);
});


Route::get('/search', [ProductFilterController::class, 'search']);
Route::get('/filter', [ProductFilterController::class, 'filteredProducts']);
Route::apiResource('products', ProductController::class)->except(['index', 'show']);


Route::middleware('auth:sanctum')->controller(CartController::class)->group(function () {
    Route::get('/cart', 'index');
    Route::post('/cart', 'addToCart');
    Route::patch('/cart/item/{itemId}', 'updateCartItems');
    Route::delete('/cart/item/{itemId}', 'removeItem');
    Route::post('/cart/clear', 'clearCart');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
    Route::get('/orders', [CheckoutController::class, 'orderHistory']);
    Route::get('/orders/{orderId}', [CheckoutController::class, 'orderDetails']);
});



// Payment routes
Route::middleware('auth:sanctum')->group(function () {
    // Create payment (Stripe, PayPal, or other providers)
    Route::post('/orders/{order}/payments', [PaymentController::class, 'createPayment']);

    // Confirm payment status
    Route::get('/payments/{paymentId}/confirm', [PaymentController::class, 'confirmPayment']);
});

// PayPal callback routes (no authentication required)
Route::get('/payments/paypal/success', [PaymentController::class, 'paypalSuccess'])
    ->name('paypal.success');
Route::get('/payments/paypal/cancel', [PaymentController::class, 'paypalCancel'])
    ->name('paypal.cancel');

// Webhook endpoints (no authentication required)
Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook'])
    ->name('webhook.stripe')
    ->withoutMiddleware(['auth:sanctum', 'throttle']);



// Admin-only order management routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Order management endpoints
    Route::get('/admin/orders', [OrderManagementController::class, 'index']);
    Route::get('/admin/orders/{order}', [OrderManagementController::class, 'show']);
    Route::patch('/admin/orders/{order}/status', [OrderManagementController::class, 'updateStatus']);
    Route::post('/admin/orders/{order}/cancel', [OrderManagementController::class, 'cancel']);
});
