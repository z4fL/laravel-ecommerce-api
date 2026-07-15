<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProductStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;

class ProductStockController extends Controller
{
    public function update(UpdateProductStockRequest $request, Product $store_product)
    {
        Gate::authorize('update', $store_product);

        $store_product->update($request->safe()->only('stock'));

        return $this->updated(
            'Product',
            new ProductResource(
                $store_product->load([
                    'store',
                    'category:id,name,slug',
                    'tags',
                ])
            )
        );
    }
}
