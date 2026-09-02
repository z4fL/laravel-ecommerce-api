<?php

namespace App\Providers;

use App\Models\Product;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Route::bind('public_product', function ($value) {
            return Product::query()
                ->published()
                ->whereSlug($value)
                ->firstOrFail();
        });

        Route::bind('store_product', function ($slug) {
            return request()->user()
                ->store
                ->products()
                ->whereSlug($slug)
                ->firstOrFail();
        });

        Route::bind('restore_product', function ($slug) {
            return request()->user()
                ->store
                ->products()
                ->withTrashed()
                ->whereSlug($slug)
                ->whereNotNull('deleted_at')
                ->firstOrFail();
        });
    }
}
