<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:warehouses,name',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'is_main' => 'boolean',
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the warehouse.',
                'example' => 'Main Warehouse',
            ],
            'address' => [
                'description' => 'The address of the warehouse.',
                'example' => '123 Warehouse St',
            ],
            'description' => [
                'description' => 'A description for the warehouse.',
                'example' => 'Primary storage facility.',
            ],
            'is_main' => [
                'description' => 'Is this the main warehouse.',
                'example' => true,
            ],
        ];
    }
}