<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePOItemRequest extends FormRequest
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
            'po_id' => 'sometimes|required|integer|exists:po,id',
            'product_id' => 'sometimes|required|integer|exists:products,id',
            'quantity' => 'sometimes|required|integer|min:1',
            'price' => 'sometimes|required|numeric|min:0.01',
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'expiry_date' => 'nullable|date',
        ];
    }

    public function bodyParameters()
    {
        return [
            'po_id' => [
                'description' => 'The ID of the purchase order.',
                'example' => 1,
            ],
            'product_id' => [
                'description' => 'The ID of the product.',
                'example' => 1,
            ],
            'quantity' => [
                'description' => 'The quantity of the product.',
                'example' => 10,
            ],
            'price' => [
                'description' => 'The price of the product.',
                'example' => 100.00,
            ],
            'discount' => [
                'description' => 'The discount applied to the item.',
                'example' => 5.00,
            ],
            'discount_type' => [
                'description' => 'The type of discount (fixed or percentage).',
                'example' => 'fixed',
            ],
            'expiry_date' => [
                'description' => 'The expiry date of the product (if applicable).',
                'example' => '2025-12-31',
            ],
        ];
    }
}
