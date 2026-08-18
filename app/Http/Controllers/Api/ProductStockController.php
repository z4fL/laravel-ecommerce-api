<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProductStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Gate;

class ProductStockController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function update(UpdateProductStockRequest $request, Product $store_product)
    {
        Gate::authorize('update', $store_product);

        $this->inventoryService->setStock(
            $store_product,
            $request->integer('stock'),
        );

        $store_product->refresh();

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
