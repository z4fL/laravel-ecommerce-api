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
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/refresh', [AuthController::class, 'refresh']);
});

Route::get(
    '/email/verify/{id}/{hash}',
    [EmailVerificationController::class, 'verify']
)->middleware('signed')->name('verification.verify');

// Categories
Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show']);

// Tags
Route::apiResource('tags', TagController::class)
    ->only(['index', 'show']);

// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{public_product}', [ProductController::class, 'show']);

Route::prefix('stores')->group(function () {
    Route::get('/', [StoreController::class, 'index']);
    Route::get('/{store}', [StoreController::class, 'show']);
    Route::get('/{store}/products', [StoreController::class, 'showProducts']);
});

// Webhook
Route::post('/webhook/payment/{gateway}', [WebhookController::class, 'handle']);
