<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReorderProductImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image_ids' => [
                'required',
                'array',
            ],
            'image_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('product_images', 'id')
            ]
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $product = $this->route('store_product');

                $imageIds = $this->safe()->collect('image_ids');

                $ownedImageCount = $product->images()->whereIn('id', $imageIds)->count();
                if ($ownedImageCount !== count($imageIds)) {
                    $validator->errors()->add(
                        'image_ids',
                        'Some images do not belong to this product.'
                    );
                }

                $productImageCount = $product->images()->count();
                if ($productImageCount !== count($imageIds)) {
                    $validator->errors()->add(
                        'image_ids',
                        'All product images must be included.'
                    );
                }
            }
        ];
    }
}
