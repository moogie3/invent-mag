<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit-purchases');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $purchaseId = $this->route('purchase')?->id;

        $rules = [
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'products' => 'required|json',
            'discount_total' => 'nullable|numeric',
            'discount_total_type' => 'nullable|in:fixed,percentage',
            'status' => 'nullable|string',
            'payment_type' => ['nullable', 'string', Rule::in(['Cash', 'Card', 'Transfer', 'eWallet', '-'])],
        ];

        if ($purchaseId) {
            $rules['invoice'] = 'required|string|unique:po,invoice,' . $purchaseId;
        } else {
            $rules['invoice'] = 'required|string|unique:po,invoice';
        }

        return $rules;
    }

    public function bodyParameters()
    {
        return [
            'invoice' => [
                'description' => 'The invoice number.',
                'example' => 'INV-2023-001',
            ],
            'supplier_id' => [
                'description' => 'The ID of the supplier.',
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
                'example' => '[{"product_id":1,"quantity":10,"price":100,"discount":0,"discount_type":"fixed","expiry_date":"2025-12-31"}]',
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
                'description' => 'The status of the purchase order.',
                'example' => 'Paid',
            ],
            'payment_type' => [
                'description' => 'The payment type.',
                'example' => 'Cash',
            ],
        ];
    }
}
