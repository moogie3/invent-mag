<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For now, we'll allow any authenticated user to create a product.
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
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:products,code|max:255',
            'price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'units_id' => 'required|exists:units,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'stock_quantity' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ];
    }
}
