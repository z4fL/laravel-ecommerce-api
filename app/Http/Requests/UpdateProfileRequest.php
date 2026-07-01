<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:50'],
            'username' => [
                'sometimes',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique('users')->ignore($this->user()),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }
}
