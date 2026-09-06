<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware(['guest', 'throttle:password-reset']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest');
});

Route::get(
    '/email/verify/{id}/{hash}',
    [EmailVerificationController::class, 'verify']
)->middleware(['signed', 'throttle:api'])->name('verification.verify');

// Categories
Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show'])
    ->middleware('throttle:api');

// Tags
Route::apiResource('tags', TagController::class)
    ->only(['index', 'show'])
    ->middleware('throttle:api');

// Products
Route::get('/products', [ProductController::class, 'index'])->middleware('throttle:api');
Route::get('/products/{public_product}', [ProductController::class, 'show'])->middleware('throttle:api');

Route::prefix('stores')->middleware('throttle:api')->group(function () {
    Route::get('/', [StoreController::class, 'index']);
    Route::get('/{store}', [StoreController::class, 'show']);
    Route::get('/{store}/products', [StoreController::class, 'showProducts']);
});

// Webhook
Route::post('/webhook/payment/{gateway}', [WebhookController::class, 'handle'])->middleware('throttle:api');
