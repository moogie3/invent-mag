<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
            'paymentable_id' => 'required|integer',
            'paymentable_type' => 'required|string',
        ];
    }

    public function bodyParameters()
    {
        return [
            'amount' => [
                'description' => 'The amount of the payment.',
                'example' => 100.00,
            ],
            'payment_date' => [
                'description' => 'The date of the payment.',
                'example' => '2025-11-28',
            ],
            'payment_method' => [
                'description' => 'The method of payment.',
                'example' => 'Cash',
            ],
            'notes' => [
                'description' => 'Any notes for the payment.',
                'example' => 'Paid in full',
            ],
            'paymentable_id' => [
                'description' => 'The ID of the model the payment belongs to (e.g., Purchase, Sales).',
                'example' => 1,
            ],
            'paymentable_type' => [
                'description' => 'The fully qualified class name of the model the payment belongs to (e.g., App\\Models\\Purchase, App\\Models\\Sales).',
                'example' => 'App\\Models\\Purchase',
            ],
        ];
    }
}
