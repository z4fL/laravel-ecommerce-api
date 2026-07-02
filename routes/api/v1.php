<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
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

// Protected Routes
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [ProfileController::class, 'show']);
    Route::patch('/me', [ProfileController::class, 'update']);

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
