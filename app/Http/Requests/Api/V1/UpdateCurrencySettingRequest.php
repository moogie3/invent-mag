<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencySettingRequest extends FormRequest
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
            'currency_symbol' => 'required|string|max:5',
            'decimal_separator' => 'required|string|max:1',
            'thousand_separator' => 'required|string|max:1',
            'decimal_places' => 'required|integer|min:0|max:4',
            'position' => 'required|string|in:prefix,suffix',
            'currency_code' => 'required|string|max:3',
            'locale' => 'required|string|max:10',
        ];
    }

    public function bodyParameters()
    {
        return [
            'currency_symbol' => [
                'description' => 'The currency symbol.',
                'example' => '$',
            ],
            'decimal_separator' => [
                'description' => 'The decimal separator.',
                'example' => '.',
            ],
            'thousand_separator' => [
                'description' => 'The thousand separator.',
                'example' => ',',
            ],
            'decimal_places' => [
                'description' => 'The number of decimal places.',
                'example' => 2,
            ],
            'position' => [
                'description' => 'The position of the currency symbol.',
                'example' => 'prefix',
            ],
            'currency_code' => [
                'description' => 'The currency code.',
                'example' => 'USD',
            ],
            'locale' => [
                'description' => 'The locale.',
                'example' => 'en_US',
            ],
        ];
    }
}
