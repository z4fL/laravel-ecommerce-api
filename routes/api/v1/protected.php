<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [ProfileController::class, 'show']);
    Route::patch('/me', [ProfileController::class, 'update']);

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/store', [StoreController::class, 'store']);
});

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('categories', CategoryController::class)
        ->except(['index', 'show']);

    Route::apiResource('tags', TagController::class)
        ->except(['index', 'show']);
});
