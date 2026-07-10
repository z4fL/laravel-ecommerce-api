<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductIndexRequest $request)
    {
        $search = $request->query('search');
        $perPage = $request->integer('per_page', 10);

        $products = Product::query()
            ->search($search)
            ->with([
                'seller',
                'category:id,name,slug',
                'tags',
            ])
            ->paginate($perPage);

        return $this->pagination(
            paginator: $products,
            data: ProductResource::collection($products),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        Gate::authorize('create', Product::class);

        $product = DB::transaction(function () use ($request) {

            $product = Product::create([
                'seller_id' => auth()->id(),
                ...$request->safe()->except('tag_ids')
            ]);

            $product->tags()->sync($request->validated('tag_ids', []));

            return $product;
        });

        return $this->created(
            'Product',
            new ProductResource(
                $product->load([
                    'seller',
                    'category:id,name,slug',
                    'tags',
                ])
            )
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return $this->success(new ProductResource($product->load([
            'seller',
            'category:id,name,slug',
            'tags',
        ])));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        Gate::authorize('update', $product);

        $product = DB::transaction(function () use ($request, $product) {

            $validated = $request->validated();
            $product->update($request->safe()->except('tag_ids'));

            if (array_key_exists('tag_ids', $validated)) {
                $product->tags()->sync($validated['tag_ids']);
            }

            return $product;
        });

        return $this->updated(
            'Product',
            new ProductResource(
                $product->load([
                    'seller',
                    'category:id,name,slug',
                    'tags',
                ])
            )
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product);

        $sku = $product->sku;
        $product->delete();

        return $this->deleted('Product', sprintf("Product with SKU: %s deleted successfully.", $sku));
    }
}
