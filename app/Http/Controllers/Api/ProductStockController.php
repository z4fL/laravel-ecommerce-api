<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProductStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;

class ProductStockController extends Controller
{
    public function update(Product $product, UpdateProductStockRequest $request)
    {
        Gate::authorize('update', $product);

        $product->update($request->safe()->only('stock'));

        return $this->updated(
            'Product',
            new ProductResource(
                $product->load([
                    'seller',
                    'category',
                    'tags',
                ])
            )
        );
    }
}
