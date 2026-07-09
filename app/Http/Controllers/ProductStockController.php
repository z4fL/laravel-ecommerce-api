<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductStockController extends Controller
{
    public function update(Product $product, UpdateProductStockRequest $request) {
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
