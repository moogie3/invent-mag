<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
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
        $supplierId = $this->route('supplier')?->id;
        return [
            'code' => 'required|string|max:255|unique:suppliers,code,' . $supplierId,
            'name' => 'required|string|max:255|unique:suppliers,name,' . $supplierId,
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:suppliers,email,' . $supplierId,
            'image' => 'nullable|string',
        ];
    }

    public function bodyParameters()
    {
        return [
            'code' => [
                'description' => 'The unique code for the supplier.',
                'example' => 'SUP-001',
            ],
            'name' => [
                'description' => 'The name of the supplier.',
                'example' => 'Supplier A',
            ],
            'address' => [
                'description' => 'The address of the supplier.',
                'example' => '123 Main St',
            ],
            'phone_number' => [
                'description' => 'The phone number of the supplier.',
                'example' => '555-1234',
            ],
            'location' => [
                'description' => 'The location of the supplier.',
                'example' => 'London',
            ],
            'payment_terms' => [
                'description' => 'The payment terms with the supplier.',
                'example' => 'Net 30',
            ],
            'email' => [
                'description' => 'The email address of the supplier.',
                'example' => 'supplierA@example.com',
            ],
            'image' => [
                'description' => 'The URL or path to the supplier\'s image.',
                'example' => 'http://example.com/image1.jpg',
            ],
        ];
    }
}
