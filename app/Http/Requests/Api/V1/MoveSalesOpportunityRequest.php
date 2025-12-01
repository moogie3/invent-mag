<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class MoveSalesOpportunityRequest extends FormRequest
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
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
        ];
    }

    public function bodyParameters()
    {
        return [
            'pipeline_stage_id' => [
                'description' => 'The ID of the new pipeline stage.',
                'example' => 2,
            ],
        ];
    }
}
