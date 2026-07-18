<?php

namespace App\Http\Requests;

use App\Enum\AddressLabel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShippingAddressRequest extends FormRequest
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
            'recipient_name' => ['sometimes', 'string', 'max:50'],
            'phone' => ['sometimes', 'string', 'max:20', 'regex:/^[0-9+\s\-()]+$/'],
            'label' => ['nullable', Rule::enum(AddressLabel::class)],
            'province' => ['sometimes', 'string', 'max:100'],
            'city' => ['sometimes', 'string', 'max:100'],
            'district' => ['sometimes', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'string', 'max:10'],
            'address' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
