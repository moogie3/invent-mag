<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
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
        $accountId = $this->route('account')?->id;

        $rules = [
            'parent_id' => 'nullable|exists:accounts,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        if ($accountId) {
            $rules['code'] = 'required|string|max:255|unique:accounts,code,' . $accountId;
        } else {
            $rules['code'] = 'required|string|max:255|unique:accounts,code';
        }

        return $rules;
    }

    public function bodyParameters()
    {
        return [
            'parent_id' => [
                'description' => 'The ID of the parent account, if any.',
                'example' => 1,
            ],
            'name' => [
                'description' => 'The name of the account.',
                'example' => 'Cash',
            ],
            'code' => [
                'description' => 'The unique code for the account.',
                'example' => '1110',
            ],
            'type' => [
                'description' => 'The type of the account.',
                'example' => 'asset',
            ],
            'description' => [
                'description' => 'A description for the account.',
                'example' => 'Cash on hand',
            ],
            'is_active' => [
                'description' => 'Whether the account is active.',
                'example' => true,
            ],
        ];
    }
}
