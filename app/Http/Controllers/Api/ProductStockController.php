<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProductStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Store;
use Illuminate\Support\Facades\Gate;

class ProductStockController extends Controller
{
    private function seller(): Store
    {
        return auth()->user()->store;
    }

    public function update(string $product, UpdateProductStockRequest $request)
    {
        Gate::authorize('update', $product);

        $this->seller()->products()->update($request->safe()->only('stock'));

        return $this->updated(
            'Product',
            new ProductResource(
                $this->seller()->products()->load([
                    'seller',
                    'category',
                    'tags',
                ])
            )
        );
    }
}
