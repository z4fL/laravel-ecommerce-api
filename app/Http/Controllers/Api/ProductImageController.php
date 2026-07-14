<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\InteractsWithCurrentStore;
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
    use InteractsWithCurrentStore;

    public function index(string $product)
    {
        $product = $this->findOwnedProduct($product);

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

        DB::beginTransaction();
        try {
            $imageRelation = $store_product->images();
            $sortOrder = ($imageRelation->max('sort_order') ?? 0) + 1;

            $image = $imageRelation->create([
                'path' => $imagePath,
                'sort_order' => $sortOrder,
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Storage::disk('public')->delete($imagePath);

            throw $th;
        }

        return $this->created('Product Image', new ProductImageResource($image));
    }

    public function destroy(Product $store_product, ProductImage $image)
    {
        Gate::authorize('update', $store_product);

        $oldPath = $image->path;

        DB::beginTransaction();

        try {
            $image->delete();
            $store_product->reorderImages();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }

        Storage::disk('public')->delete($oldPath);

        return $this->deleted('Product Image');
    }

    public function reorder(ReorderProductImageRequest $request, Product $store_product)
    {
        Gate::authorize('update', $store_product);

        DB::beginTransaction();
        try {
            $imageIds = $request->safe()['image_ids'];

            foreach ($imageIds as $index => $id) {
                $store_product->images()
                    ->whereKey($id)
                    ->update([
                        'sort_order' => $index + 1,
                    ]);
            }

            DB::commit();
        } catch (\Throwable $th) {

            DB::rollBack();
            throw $th;
        }

        return $this->success(
            ProductImageResource::collection(
                $store_product->images()->orderBy('sort_order')->get()
            )
        );
    }
}
