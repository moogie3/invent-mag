<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPipelineStagesRequest extends FormRequest
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
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:pipeline_stages,id',
            'stages.*.position' => 'required|integer|min:0',
        ];
    }

    public function bodyParameters()
    {
        return [
            'stages' => [
                'description' => 'An array of stage objects with id and new position.',
                'example' => '[{"id":1,"position":0},{"id":2,"position":1}]',
            ],
        ];
    }
}
