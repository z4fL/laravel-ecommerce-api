<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\ProductStockController;
use App\Http\Controllers\Api\StoreController;
use Illuminate\Support\Facades\Route;

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Protected
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [ProfileController::class, 'show']);
    Route::patch('/me', [ProfileController::class, 'update']);
    Route::post('/store', [StoreController::class, 'store']);
});

// Categories
Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show']);

Route::apiResource('categories', CategoryController::class)
    ->except(['index', 'show'])
    ->middleware(['auth:api', 'role:admin']);

// Tags
Route::apiResource('tags', TagController::class)
    ->only(['index', 'show']);

Route::apiResource('tags', TagController::class)
    ->except(['index', 'show'])
    ->middleware(['auth:api', 'role:admin']);

// Product
Route::apiResource('products', ProductController::class)
    ->only(['index', 'show']);
Route::get('/products/{product}/images', [ProductImageController::class, 'index']);

Route::middleware(['auth:api', 'role:seller'])->group(function () {
    Route::apiResource('products', ProductController::class)
        ->except(['index', 'show']);

    Route::post('/products/{product}/images', [ProductImageController::class, 'store']);
    Route::patch('/products/{product}/images/reorder', [ProductImageController::class, 'reorder']);
    Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy'])
        ->scopeBindings();
    Route::patch('/products/{product}/stock', [ProductStockController::class, 'update']);
});

// Cart
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::delete('/', [CartController::class, 'destroy']);

    Route::post('/items/{product}', [CartItemController::class, 'store']);
    Route::patch('/items/{item}', [CartItemController::class, 'update']);
    Route::delete('/items/{item}', [CartItemController::class, 'destroy']);
})->middleware(['auth:api']);
