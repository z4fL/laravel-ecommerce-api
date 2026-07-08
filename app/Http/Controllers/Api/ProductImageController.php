<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadProductImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(UploadProductImageRequest $request, Product $product)
    {
        $newImagePath = Storage::disk('public')->putFile('products', $request->safe()->file('image'));
        $oldImagePath = null;

        DB::beginTransaction();
        try {
            $primaryImage = $product->primaryImage()->first();

            $image = $product->images()->create([
                'path' => $newImagePath,
                'is_primary' => true,
                'sort_order' => 1
            ]);

            if ($primaryImage) {
                $oldImagePath = $primaryImage->path;

                $primaryImage->delete();
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            Storage::disk('public')->delete($newImagePath);

            throw $th;
        }

        if ($oldImagePath) Storage::delete($oldImagePath);

        return $this->created('Product Image', new ProductImageResource($image));
    }
}
