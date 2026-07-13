<?php

use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ProductStockController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\StoreProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'role:seller'])
    ->prefix('store')
    ->group(function () {
        Route::get('/', [StoreController::class, 'me']);
        Route::patch('/', [StoreController::class, 'update']);
        Route::delete('/', [StoreController::class, 'destroy']);

        Route::apiResource('/products', StoreProductController::class);

        Route::post('/products/{product}/images', [ProductImageController::class, 'store']);
        Route::patch('/products/{product}/images/reorder', [ProductImageController::class, 'reorder']);
        Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy']);

        Route::patch('/products/{product}/stock', [ProductStockController::class, 'update']);
    });
