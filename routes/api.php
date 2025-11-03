<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductFilterController;
use Illuminate\Http\Request;
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

