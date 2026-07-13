<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        require __DIR__ . '/v1/public.php';
        require __DIR__ . '/v1/protected.php';
        require __DIR__ . '/v1/seller.php';
    });
