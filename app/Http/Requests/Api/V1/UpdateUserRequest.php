<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')?->id;

        $rules = [
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'permissions' => 'nullable|array',
        ];

        if ($userId) {
            $rules['email'] = 'required|string|email|max:255|unique:users,email,' . $userId;
        } else {
            $rules['email'] = 'required|string|email|max:255|unique:users,email';
        }

        return $rules;
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the user.',
                'example' => 'John Doe',
            ],
            'email' => [
                'description' => 'The email address of the user.',
                'example' => 'john.doe@example.com',
            ],
            'password' => [
                'description' => 'The password for the user.',
                'example' => 'password123',
            ],
            'password_confirmation' => [
                'description' => 'The password confirmation.',
                'example' => 'password123',
            ],
            'roles' => [
                'description' => 'An array of role names to assign to the user.',
                'example' => ['Admin', 'Manager'],
            ],
            'permissions' => [
                'description' => 'An array of permission names to assign to the user.',
                'example' => ['view-users', 'create-users'],
            ],
        ];
    }
}