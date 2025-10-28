<?php

use App\Http\Controllers\Api\AuthController;
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
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);

    Route::patch('products/{id}/restore', [ProductController::class, 'restore']);
    Route::post('/products/{id}/forceDelete', [ProductController::class, 'forceDelete']);
    Route::get('/products/trashed', [ProductController::class, 'trashed']);
});


Route::get('/search', [ProductFilterController::class, 'search']);
Route::get('/filter', [ProductFilterController::class, 'filteredProducts']);
