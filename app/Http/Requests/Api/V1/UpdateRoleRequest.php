<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
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
        $roleId = $this->route('role')?->id;

        $rules = [
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];

        if ($roleId) {
            $rules['name'] = 'required|string|max:255|unique:roles,name,' . $roleId;
        } else {
            $rules['name'] = 'required|string|max:255|unique:roles,name';
        }

        return $rules;
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the role.',
                'example' => 'Admin',
            ],
            'permissions' => [
                'description' => 'An array of permission names to assign to the role.',
                'example' => ['view-users', 'create-users'],
            ],
        ];
    }
}
