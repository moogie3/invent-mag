<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\Events\Registered;
use App\Models\User;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Actions\Fortify\CreateNewUser  $creator
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, CreateNewUser $creator)
    {
        // Manual validation since we are not using a FormRequest here
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'shopname' => ['required', 'string', 'max:255', 'unique:tenants,name'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'plan' => ['nullable', 'string', 'in:starter,professional,enterprise'],
        ]);

        // Use the existing action to create the user and tenant
        $user = $creator->create($request->all());

        // Create a token for the new user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load fresh tenant with plan relationship
        $tenant = $user->tenant->load('plan');
        $workspaceSlug = explode('.', $tenant->domain)[0];

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'tenant_domain' => $tenant->domain,
            'workspace_slug' => $workspaceSlug,
            'plan' => [
                'name' => $tenant->plan?->name,
                'slug' => $tenant->plan?->slug,
                'status' => $tenant->plan_status,
                'on_trial' => $tenant->onTrial(),
                'trial_ends_at' => $tenant->trial_ends_at?->toISOString(),
            ],
        ], 201);
    }
}
