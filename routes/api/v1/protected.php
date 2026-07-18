<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ShippingAddressController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [ProfileController::class, 'show']);
    Route::patch('/me', [ProfileController::class, 'update']);

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/store', [StoreController::class, 'store']);

    Route::apiResource('/shipping-addresses', ShippingAddressController::class);

    Route::patch('/shipping-addresses/{shipping_address}/default', [ShippingAddressController::class, 'makeDefault']);

    Route::post('/checkout', [CheckoutController::class, 'preview']);
});

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('categories', CategoryController::class)
        ->except(['index', 'show']);

    Route::apiResource('tags', TagController::class)
        ->except(['index', 'show']);
});

// Cart
Route::middleware(['auth:api'])
    ->prefix('cart')
    ->group(function () {
        Route::get('/', [CartController::class, 'show']);
        Route::delete('/', [CartController::class, 'destroy']);

        Route::post('/items/{public_product}', [CartItemController::class, 'store']);
        Route::patch('/items/{item}', [CartItemController::class, 'update']);
        Route::delete('/items/{item}', [CartItemController::class, 'destroy']);
    });
