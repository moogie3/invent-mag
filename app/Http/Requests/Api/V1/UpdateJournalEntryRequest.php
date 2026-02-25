<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJournalEntryRequest extends FormRequest
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
            'date' => 'required|date',
            'description' => 'required|string',
            'sourceable_id' => 'nullable|integer',
            'sourceable_type' => 'nullable|string',
        ];
    }

    public function bodyParameters()
    {
        return [
            'date' => [
                'description' => 'The date of the journal entry.',
                'example' => '2025-11-28',
            ],
            'description' => [
                'description' => 'A description for the journal entry.',
                'example' => 'Initial investment',
            ],
            'sourceable_id' => [
                'description' => 'The ID of the sourceable model.',
                'example' => 1,
            ],
            'sourceable_type' => [
                'description' => 'The type of the sourceable model.',
                'example' => 'App\\Models\\User',
            ],
        ];
    }
}
