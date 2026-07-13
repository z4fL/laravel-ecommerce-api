<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait InteractsWithCurrentStore
{
    protected function store(): Store
    {
        return auth()->user()->store;
    }

    protected function products(): HasMany
    {
        return $this->store()->products();
    }

    protected function findOwnedProduct(string $slug): Product
    {
        return $this->products()
            ->whereSlug($slug)
            ->firstOrFail();
    }
}
