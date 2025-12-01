<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
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
            'amount' => 'required|numeric',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
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
                'description' => 'The payment method.',
                'example' => 'Cash',
            ],
            'notes' => [
                'description' => 'Notes about the payment.',
                'example' => 'Paid in full',
            ],
        ];
    }
}
