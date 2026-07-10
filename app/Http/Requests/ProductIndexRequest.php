<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
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
            'category' => [
                'nullable',
                'string'
            ],
            'tag' => [
                'nullable',
                'string'
            ],
            'min_price' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:min_price'
            ],
            'in_stock' => [
                'nullable',
                'boolean'
            ],
            'page' => [
                'integer',
                'min:1'
            ],
            'per_page' => [
                'integer',
                'min:1',
                'max:100'
            ]
        ];
    }
}
