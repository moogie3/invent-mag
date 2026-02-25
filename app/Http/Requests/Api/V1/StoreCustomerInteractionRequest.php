<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerInteractionRequest extends FormRequest
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
            'customer_id' => 'required|exists:customers,id',
            'type' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'interaction_date' => 'required|date',
        ];
    }

    public function bodyParameters()
    {
        return [
            'customer_id' => [
                'description' => 'The ID of the customer.',
                'example' => 1,
            ],
            'type' => [
                'description' => 'The type of interaction.',
                'example' => 'Call',
            ],
            'notes' => [
                'description' => 'Notes about the interaction.',
                'example' => 'Followed up on the recent order.',
            ],
            'interaction_date' => [
                'description' => 'The date of the interaction.',
                'example' => '2025-11-28',
            ],
        ];
    }
}
