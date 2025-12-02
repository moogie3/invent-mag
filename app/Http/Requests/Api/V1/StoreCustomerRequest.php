<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:customers,name',
            'address' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email',
            'payment_terms' => 'required|string|max:255',
        ];
    }

    /**
     * Get the body parameters for the request.
     *
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The name of the customer.',
                'example' => 'John Doe',
            ],
            'address' => [
                'description' => 'The address of the customer.',
                'example' => '123 Main St',
            ],
            'phone_number' => [
                'description' => 'The phone number of the customer.',
                'example' => '555-1234',
            ],
            'email' => [
                'description' => 'The email of the customer. Must be unique.',
                'example' => 'john.doe@example.com',
            ],
            'payment_terms' => [
                'description' => 'The payment terms.',
                'example' => 'Net 30',
            ],
        ];
    }
}
