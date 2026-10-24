<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    /**
     * Register a new user and tenant.
     *
     * Creates a user account and its tenant (workspace) in one call, then
     * returns an access token for immediate use. Rate-limited to 3 per hour
     * per IP.
     *
     * @group Authentication
     * Public registration, login, and tenant lookup, plus token refresh for authenticated clients.
     *
     * @unauthenticated
     *
     * @bodyParam name string required The user's full name.
     * @bodyParam shopname string required The desired workspace name. Must be unique.
     * @bodyParam email string required The user's email address. Must be unique.
     * @bodyParam password string required The user's password (confirmed). Must meet the app's password rules.
     * @bodyParam password_confirmation string required Confirmation of the password.
     * @bodyParam plan string optional The plan slug to assign (defaults to the platform default plan when omitted). Enum: starter, professional, enterprise.
     *
     * @response status=201 scenario=success {
     *   "message": "Registration successful",
     *   "user": {
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "updated_at": "2026-08-12T09:00:00.000000Z",
     *     "created_at": "2026-08-12T09:00:00.000000Z",
     *     "id": 1
     *   },
     *   "access_token": "1|abcdefghijklmnopqrstuvwxyz",
     *   "token_type": "Bearer",
     *   "tenant_domain": "mycompany.invent-mag.web.id",
     *   "workspace_slug": "mycompany",
     *   "plan": {
     *     "name": "Starter",
     *     "slug": "starter",
     *     "status": "trial",
     *     "on_trial": true,
     *     "trial_ends_at": "2026-09-11T09:00:00.000000Z"
     *   }
     * }
     * @response status=422 scenario="validation error" {
     *   "message": "The shopname has already been taken. (and 2 more errors)",
     *   "errors": {
     *     "shopname": [
     *       "The shopname has already been taken."
     *     ],
     *     "email": [
     *       "The email has already been taken."
     *     ]
     *   }
     * }
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
