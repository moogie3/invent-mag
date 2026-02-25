<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesPipelineRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:sales_pipelines',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the sales pipeline.',
                'example' => 'Initial Sales Pipeline',
            ],
            'description' => [
                'description' => 'A description of the sales pipeline.',
                'example' => 'Pipeline for initial customer contact.',
            ],
            'is_default' => [
                'description' => 'Is this the default pipeline.',
                'example' => false,
            ],
        ];
    }
}
