<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit-customers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('customers')->ignore($this->customer),
            ],
            'address' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('customers')->ignore($this->customer),
            ],
            'payment_terms' => 'sometimes|required|string|max:255',
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
                'example' => 'Jane Doe',
            ],
            'address' => [
                'description' => 'The address of the customer.',
                'example' => '456 Oak Ave',
            ],
            'phone_number' => [
                'description' => 'The phone number of the customer.',
                'example' => '555-5678',
            ],
            'email' => [
                'description' => 'The email of the customer. Must be unique.',
                'example' => 'jane.doe@example.com',
            ],
            'payment_terms' => [
                'description' => 'The payment terms.',
                'example' => 'Net 60',
            ],
        ];
    }
}
