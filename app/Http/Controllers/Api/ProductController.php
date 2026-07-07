<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->success(ProductResource::collection(
            Product::with(['seller', 'category', 'tags'])->get()
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
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
                    'category',
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
            'category',
            'tags',
        ])));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
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
                    'category',
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
        $sku = $product->sku;
        $product->delete();

        return $this->deleted('Product', sprintf("Product with SKU: %s deleted successfully.", $sku));
    }
}
