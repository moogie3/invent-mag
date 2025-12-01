<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePipelineStageRequest extends FormRequest
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
        $pipeline = $this->route('pipeline');
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('pipeline_stages')->where(function ($query) use ($pipeline) {
                return $query->where('sales_pipeline_id', $pipeline->id);
            })],
            'is_closed' => 'boolean',
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the new stage.',
                'example' => 'Qualification',
            ],
            'is_closed' => [
                'description' => 'Whether this stage represents a closed state.',
                'example' => false,
            ],
        ];
    }
}
