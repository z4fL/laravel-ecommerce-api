<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class UploadProductImageRequest extends FormRequest
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
            'image' => ['required', File::image()->max(3 * 1024)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $product = $this->route('store_product');

                if ($product->images()->count() >= 5) {
                    $validator->errors()->add(
                        'image',
                        'A product can have a maximum of 5 images.'
                    );
                }
            },
        ];
    }
}
