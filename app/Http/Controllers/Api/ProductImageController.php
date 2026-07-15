<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderProductImageRequest;
use App\Http\Requests\UploadProductImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function index(Product $product)
    {
        return $this->success(
            ProductImageResource::collection(
                $product->images()->orderBy('sort_order')->get()
            )
        );
    }

    public function store(UploadProductImageRequest $request, Product $store_product)
    {
        Gate::authorize('update', $store_product);

        $imagePath = Storage::disk('public')->putFile('products', $request->safe()->file('image'));

        try {
            $image = DB::transaction(function () use ($store_product, $imagePath) {
                $imageRelation = $store_product->images();

                $sortOrder = ($imageRelation->max('sort_order') ?? 0) + 1;

                return $imageRelation->create([
                    'path' => $imagePath,
                    'sort_order' => $sortOrder,
                ]);
            });
        } catch (\Throwable $th) {
            Storage::disk('public')->delete($imagePath);

            throw $th;
        }

        return $this->created('Product Image', new ProductImageResource($image));
    }

    public function destroy(Product $store_product, ProductImage $image)
    {
        Gate::authorize('update', $store_product);

        $oldPath = $image->path;

        DB::transaction(function () use ($store_product, $image) {
            $image->delete();
            $store_product->reorderImages();
        });

        Storage::disk('public')->delete($oldPath);

        return $this->deleted('Product Image');
    }

    public function reorder(ReorderProductImageRequest $request, Product $store_product)
    {
        Gate::authorize('update', $store_product);

        DB::transaction(function () use ($request, $store_product) {
            foreach ($request->safe()['image_ids'] as $index => $id) {
                $store_product->images()
                    ->whereKey($id)
                    ->update([
                        'sort_order' => $index + 1,
                    ]);
            }
        });

        return $this->success(
            ProductImageResource::collection(
                $store_product->images()->orderBy('sort_order')->get()
            )
        );
    }
}
