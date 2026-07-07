<?php

namespace App\Http\Requests;

use App\Enum\ProductStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'category_id' => [
                'sometimes',
                'integer',
                Rule::exists('categories', 'id'),
            ],
            'sku' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('products')->ignore($this->route('product')),
            ],
            'name' => [
                'sometimes',
                'string',
                'min:3',
                'max:150',
            ],
            'description' => [
                'nullable',
                'string',
                'min:10',
                'max:1000',
            ],
            'price' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'status' => [
                'sometimes',
                Rule::enum(ProductStatus::class),
            ],
            'tag_ids' => [
                'sometimes',
                'array',
            ],
            'tag_ids.*' => [
                'integer',
                Rule::exists('tags', 'id'),
            ],
        ];
    }
}
