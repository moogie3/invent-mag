<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
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
        $unitId = $this->route('unit')?->id;

        $rules = [
            'symbol' => 'required|string|max:255',
        ];

        if ($unitId) {
            $rules['name'] = 'required|string|max:255|unique:units,name,' . $unitId;
        } else {
            $rules['name'] = 'required|string|max:255|unique:units,name';
        }

        return $rules;
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the unit.',
                'example' => 'Kilogram',
            ],
            'symbol' => [
                'description' => 'The symbol of the unit.',
                'example' => 'kg',
            ],
        ];
    }
}