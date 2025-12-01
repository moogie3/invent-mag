<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
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
        $saleId = $this->route('sale')?->id;

        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'products' => 'required|json',
            'discount_total' => 'nullable|numeric|min:0',
            'discount_total_type' => 'nullable|in:fixed,percentage',
            'status' => 'nullable|string',
            'payment_type' => 'nullable|string',
        ];

        if ($saleId) {
            $rules['invoice'] = 'nullable|string|unique:sales,invoice,' . $saleId;
        } else {
            $rules['invoice'] = 'nullable|string|unique:sales,invoice';
        }

        return $rules;
    }

    public function bodyParameters()
    {
        return [
            'invoice' => [
                'description' => 'The invoice number.',
                'example' => 'INV-SALES-001',
            ],
            'customer_id' => [
                'description' => 'The ID of the customer.',
                'example' => 1,
            ],
            'order_date' => [
                'description' => 'The date of the order.',
                'example' => '2023-10-26',
            ],
            'due_date' => [
                'description' => 'The due date of the payment.',
                'example' => '2023-11-26',
            ],
            'products' => [
                'description' => 'A JSON string of products.',
                'example' => '[{"product_id":1,"quantity":5,"customer_price":100,"discount":0,"discount_type":"fixed"}]',
            ],
            'discount_total' => [
                'description' => 'The total discount applied.',
                'example' => 5.00,
            ],
            'discount_total_type' => [
                'description' => 'The type of discount.',
                'example' => 'fixed',
            ],
            'status' => [
                'description' => 'The status of the sales order.',
                'example' => 'Paid',
            ],
            'payment_type' => [
                'description' => 'The payment type.',
                'example' => 'Cash',
            ],
        ];
    }
}
