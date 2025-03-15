<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Products (public read)
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('products/search/{query}', [ProductController::class, 'search']);
    Route::get('products/{product}/related', [ProductController::class, 'related']);

    // Categories (public read)
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
    Route::get('categories/{category}/products', [CategoryController::class, 'products']);

    // Admin only routes
    Route::middleware(AdminMiddleware::class)->group(function () {
        Route::apiResource('products', ProductController::class)
            ->except(['index', 'show']);
        Route::apiResource('categories', CategoryController::class)
            ->except(['index', 'show']);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    });

    // User routes
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
});
