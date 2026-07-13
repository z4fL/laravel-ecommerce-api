<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductIndexRequest $request)
    {
        $search = $request->query('search');
        $filters = $request->safe()->except(['search', 'sort']);
        $sort = $request->input('sort');

        $perPage = $request->integer('per_page', 10);

        $products = Product::query()
            ->published()
            ->with([
                'store',
                'category:id,name,slug',
                'tags',
            ])
            ->search($search ?? null)
            ->filter($filters)
            ->sort($sort)
            ->paginate($perPage);

        return $this->pagination(
            paginator: $products,
            data: ProductResource::collection($products),
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {

        $productData = $product->query()
            ->published()
            ->load([
                'store',
                'category:id,name,slug',
                'tags',
            ]);

        return $this->success(new ProductResource($productData));
    }
}
