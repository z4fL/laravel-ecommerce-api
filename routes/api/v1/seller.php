<?php

use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ProductStockController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\StoreProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'role:seller,admin', 'verified'])
    ->prefix('store')
    ->group(function () {
        Route::get('/', [StoreController::class, 'me']);
        Route::patch('/', [StoreController::class, 'update']);
        Route::delete('/', [StoreController::class, 'destroy']);

        Route::apiResource('/products', StoreProductController::class)
            ->parameters([
                'products' => 'store_product',
            ]);
        Route::post('/products/{restore_product}/restore', [StoreProductController::class, 'restore']);

        Route::post('/products/{store_product}/images', [ProductImageController::class, 'store']);
        Route::patch('/products/{store_product}/images/reorder', [ProductImageController::class, 'reorder']);
        Route::delete('/products/{store_product}/images/{image}', [ProductImageController::class, 'destroy'])
            ->scopeBindings();

        Route::patch('/products/{store_product}/stock', [ProductStockController::class, 'update']);
    });
