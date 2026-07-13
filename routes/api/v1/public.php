<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreProductController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// Categories
Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show']);

    // Tags
Route::apiResource('tags', TagController::class)
    ->only(['index', 'show']);

// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::prefix('stores')->group(function () {
    Route::get('/', [StoreProductController::class, 'index']);
    Route::get('/{store}', [StoreProductController::class, 'show']);
    Route::get('/{store}/products', [StoreProductController::class, 'showProducts']);
});
