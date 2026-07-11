<?php

namespace App\Http\Requests;

use App\Enum\StoreStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'description' => ['nullable', 'string', 'min:10', 'max:1000'],
            'phone' => ['required', 'string', 'max:20',],
            'status' => ['required', Rule::enum(StoreStatus::class)],
        ];
    }
}
