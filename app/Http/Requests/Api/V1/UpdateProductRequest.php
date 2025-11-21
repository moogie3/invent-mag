<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For now, we'll allow any authenticated user to update a product.
        // You can add more specific authorization logic here later.
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
            'name' => 'sometimes|required|string|max:255',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products')->ignore($this->product),
            ],
            'price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'supplier_id' => 'sometimes|nullable|exists:suppliers,id',
            'units_id' => 'sometimes|required|exists:units,id',
            'warehouse_id' => 'sometimes|nullable|exists:warehouses,id',
            'stock_quantity' => 'sometimes|nullable|numeric|min:0',
            'description' => 'sometimes|nullable|string',
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
                'description' => 'The name of the product.',
                'example' => 'Laptop',
            ],
            'code' => [
                'description' => 'The product code. Must be unique.',
                'example' => 'LP-001',
            ],
            'price' => [
                'description' => 'The purchase price of the product.',
                'example' => 1000.00,
            ],
            'selling_price' => [
                'description' => 'The selling price of the product.',
                'example' => 1200.00,
            ],
            'category_id' => [
                'description' => 'The ID of the category the product belongs to.',
                'example' => 1,
            ],
            'supplier_id' => [
                'description' => 'The ID of the supplier.',
                'example' => 1,
            ],
            'units_id' => [
                'description' => 'The ID of the unit of measure.',
                'example' => 1,
            ],
            'warehouse_.id' => [
                'description' => 'The ID of the warehouse where the product is stored.',
                'example' => 1,
            ],
            'stock_quantity' => [
                'description' => 'The initial stock quantity.',
                'example' => 100,
            ],
            'description' => [
                'description' => 'A description of the product.',
                'example' => 'A powerful laptop.',
            ],
        ];
    }
}
