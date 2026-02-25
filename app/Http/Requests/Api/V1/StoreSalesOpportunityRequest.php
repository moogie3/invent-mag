<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOpportunityRequest extends FormRequest
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
            'sales_pipeline_id' => 'required|exists:sales_pipelines,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expected_close_date' => 'nullable|date',
            'status' => 'required|in:open,won,lost',
            'amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ];
    }

    public function bodyParameters()
    {
        return [
            'customer_id' => [
                'description' => 'The ID of the customer.',
                'example' => 1,
            ],
            'sales_pipeline_id' => [
                'description' => 'The ID of the sales pipeline.',
                'example' => 1,
            ],
            'pipeline_stage_id' => [
                'description' => 'The ID of the pipeline stage.',
                'example' => 1,
            ],
            'name' => [
                'description' => 'The name of the sales opportunity.',
                'example' => 'New Client Project',
            ],
            'description' => [
                'description' => 'A description of the sales opportunity.',
                'example' => 'Develop a new e-commerce platform.',
            ],
            'expected_close_date' => [
                'description' => 'The expected close date of the opportunity.',
                'example' => '2024-12-31',
            ],
            'status' => [
                'description' => 'The status of the opportunity.',
                'example' => 'Open',
            ],
            'items' => [
                'description' => 'A list of product items for the opportunity.',
                'example' => '[{"product_id":1,"quantity":1,"price":100.00}]',
            ],
        ];
    }
}
