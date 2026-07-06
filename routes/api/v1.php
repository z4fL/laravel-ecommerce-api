<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TagController;
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

// Profile
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [ProfileController::class, 'show']);
    Route::patch('/me', [ProfileController::class, 'update']);
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
