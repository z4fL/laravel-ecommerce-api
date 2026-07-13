<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithCurrentStore;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StoreProductController extends Controller
{
    use InteractsWithCurrentStore;

    /**
     * Display a listing of the resource.
     */
    public function index(ProductIndexRequest $request)
    {
        $search = $request->query('search');
        $filters = $request->safe()->except(['search', 'sort']);
        $sort = $request->input('sort');
        $perPage = $request->integer('per_page', 10);

        $products = $this->products()
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
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        Gate::authorize('create', Product::class);

        $product = DB::transaction(function () use ($request) {

            $product = $this->products()->create([
                'store_id' => auth()->user()->store->id,
                ...$request->safe()->except('tag_ids')
            ]);

            $product->tags()->sync($request->validated('tag_ids', []));

            return $product;
        });

        return $this->created(
            'Product',
            new ProductResource(
                $product->load([
                    'store',
                    'category:id,name,slug',
                    'tags',
                ])
            )
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $product)
    {
        $product = $this->findOwnedProduct($product);

        Gate::authorize('view', $product);

        return $this->success(new ProductResource($product->load([
            'store',
            'category:id,name,slug',
            'tags',
        ])));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $product)
    {
        $product = $this->findOwnedProduct($product);

        Gate::authorize('update', $product);

        $product = DB::transaction(function () use ($request, $product) {

            $validated = $request->validated();

            $product->update(
                $request->safe()->except('tag_ids')
            );

            if (array_key_exists('tag_ids', $validated)) {
                $product->tags()->sync($validated['tag_ids']);
            }

            return $product;
        });

        return $this->updated(
            'Product',
            new ProductResource(
                $product->load([
                    'store',
                    'category:id,name,slug',
                    'tags',
                ])
            )
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $product)
    {
        $product = $this->findOwnedProduct($product);

        Gate::authorize('delete', $product);

        $sku = $product->sku;
        $product->delete();

        return $this->deleted('Product', sprintf("Product with SKU: %s deleted successfully.", $sku));
    }
}
