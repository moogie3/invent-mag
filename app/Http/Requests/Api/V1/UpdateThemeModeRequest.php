<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateThemeModeRequest extends FormRequest
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
            'theme_mode' => 'required|in:light,dark',
        ];
    }


    public function bodyParameters()
    {
        return [
            'theme_mode' => [
                'description' => 'The user\'s desired theme mode.',
                'example' => 'dark',
            ],
        ];
    }
}