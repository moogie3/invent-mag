<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierInteractionRequest extends FormRequest
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
            'supplier_id' => 'required|exists:suppliers,id',
            'type' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'interaction_date' => 'required|date',
        ];
    }

    public function bodyParameters()
    {
        return [
            'supplier_id' => [
                'description' => 'The ID of the supplier.',
                'example' => 1,
            ],
            'type' => [
                'description' => 'The type of interaction.',
                'example' => 'Email',
            ],
            'notes' => [
                'description' => 'Notes about the interaction.',
                'example' => 'Sent follow-up email.',
            ],
            'interaction_date' => [
                'description' => 'The date of the interaction.',
                'example' => '2023-10-26',
            ],
        ];
    }
}