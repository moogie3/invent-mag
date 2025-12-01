<?php

namespace App\Http\Requests\Api\V1;

use App\Models\PipelineStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePipelineStageRequest extends FormRequest
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
        $stage = PipelineStage::find($this->route('pipeline_stage'));
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('pipeline_stages')->ignore($stage?->id)->where(function ($query) use ($stage) {
                return $query->where('sales_pipeline_id', $stage?->sales_pipeline_id);
            })],
            'position' => 'required|integer|min:0',
            'is_closed' => 'boolean',
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The new name of the stage.',
                'example' => 'Negotiation',
            ],
            'position' => [
                'description' => 'The new position of the stage.',
                'example' => 1,
            ],
            'is_closed' => [
                'description' => 'Whether this stage represents a closed state.',
                'example' => false,
            ],
        ];
    }
}
