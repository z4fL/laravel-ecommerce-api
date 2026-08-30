<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ShippingAddressController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [ProfileController::class, 'show']);
    Route::patch('/me', [ProfileController::class, 'update']);

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post(
        '/auth/email/verification-notification',
        [EmailVerificationController::class, 'send']
    );

    Route::middleware('verified')->group(function () {
        Route::post('/store', [StoreController::class, 'store']); // create store for user

        Route::apiResource('/shipping-addresses', ShippingAddressController::class);

        Route::patch(
            '/shipping-addresses/{shipping_address}/default',
            [ShippingAddressController::class, 'makeDefault']
        );

        Route::post('/checkout', [CheckoutController::class, 'preview']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

        Route::post('/orders/{order}/payment', [PaymentController::class, 'store']);
    });
});

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('categories', CategoryController::class)
        ->except(['index', 'show']);

    Route::apiResource('tags', TagController::class)
        ->except(['index', 'show']);
});

// Cart
Route::middleware(['auth:api', 'verified'])
    ->prefix('cart')
    ->group(function () {
        Route::get('/', [CartController::class, 'show']);
        Route::delete('/', [CartController::class, 'destroy']);

        Route::post('/items/{public_product}', [CartItemController::class, 'store']);
        Route::patch('/items/{item}', [CartItemController::class, 'update']);
        Route::delete('/items/{item}', [CartItemController::class, 'destroy']);
    });
