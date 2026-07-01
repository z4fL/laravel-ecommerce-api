<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {

        // Authentication
        Route::prefix('auth')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);

            Route::post('/refresh', [AuthController::class, 'refresh']);

            Route::middleware('auth:api')->group(function () {
                Route::post('/logout', [AuthController::class, 'logout']);
                Route::get('/me', [AuthController::class, 'me']);
            });
        });


        // Protected Routes
        Route::middleware('auth:api')->group(function () {
            Route::middleware('role:admin')->group(function () {
                Route::get('/admin/test', fn() => response()->json([
                    'message' => 'Admin access granted.',
                ]));
            });

            Route::middleware('role:seller')->group(function () {
                Route::get('/seller/test', fn() => response()->json([
                    'message' => 'Seller access granted.',
                ]));
            });

            Route::middleware('role:customer')->group(function () {
                Route::get('/customer/test', fn() => response()->json([
                    'message' => 'Customer access granted.',
                ]));
            });
        });
    });
