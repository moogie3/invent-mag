<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the tax.',
                'example' => 'Sales Tax',
            ],
            'rate' => [
                'description' => 'The tax rate.',
                'example' => 0.05,
            ],
            'is_active' => [
                'description' => 'Is the tax active.',
                'example' => true,
            ],
        ];
    }
}