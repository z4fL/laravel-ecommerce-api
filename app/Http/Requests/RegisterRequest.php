<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                'unique:users,username'
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed'
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
            ]
        ];
    }
}
