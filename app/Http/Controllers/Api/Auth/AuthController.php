<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Log in and receive an API token.
     *
     * Authenticates the user with email and password and returns a Sanctum
     * bearer token for authenticated API calls. Rate-limited to 5 requests
     * per minute per IP.
     *
     * @group Authentication
     * Public registration, login, and tenant lookup, plus token refresh for authenticated clients.
     *
     * @unauthenticated
     *
     * @bodyParam email string required The user's email address.
     * @bodyParam password string required The user's password.
     *
     * @response scenario=success {
     *   "token": "1|abcdefghijklmnopqrstuvwxyz",
     *   "token_type": "Bearer"
     * }
     * @response status=401 scenario="invalid credentials" {
     *   "message": "Invalid login details"
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = $request->user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'token_type' => 'Bearer',
            ]);
        }

        return response()->json([
            'message' => 'Invalid login details',
        ], 401);
    }

    /**
     * Refresh the current API token.
     *
     * Revokes the current token and issues a new one with a one-day expiry.
     * Call this with the token you want to replace.
     *
     * @group Authentication
     * Public registration, login, and tenant lookup, plus token refresh for authenticated clients.
     *
     * @response scenario=success {
     *   "token": "2|abcdefghijklmnopqrstuvwxyz",
     *   "expires_at": "2026-08-13T09:00:00.000000Z"
     * }
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->tokens()->delete();

        $token = $user->createToken('auth_token', ['*'], now()->addDay())->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_at' => now()->addDay()->toISOString(),
        ]);
    }
}
