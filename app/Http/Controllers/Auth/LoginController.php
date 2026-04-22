<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Scopes\TenantScope;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Responses\LogoutResponse;

class LoginController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Find the user by email, ignoring the tenant scope for this initial lookup
        $user = User::withoutGlobalScope(TenantScope::class)
                    ->where('email', $request->email)
                    ->first();

        // If no user is found, or they don't have a tenant, fail as normal
        if (! $user || ! $user->tenant) {
            return $this->failAuthentication($request);
        }

        $tenantDomain = $user->tenant->domain;

        // If the user is not on their correct tenant domain, redirect them
        if ($tenantDomain !== $request->getHost()) {
            // Reconstruct the URL with the correct domain and path
            $redirectUrl = "https://{$tenantDomain}" . $request->getRequestUri();
            
            return redirect()->away($redirectUrl)->withInput($request->only('email'));
        }

        // If the user is on the correct domain, attempt to log them in
        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Log successful login
            try {
                app(ActivityLogService::class)->log(
                    ActivityLog::MODULE_DASHBOARD,
                    ActivityLog::ACTION_LOGIN,
                    'User logged in: ' . Auth::user()->name . ' (' . Auth::user()->email . ')',
                );
            } catch (\Throwable $e) {
                // never block login because of logging failure
            }

            return redirect()->intended(config('fortify.home'));
        }

        // If authentication fails (e.g., wrong password), fail as normal
        return $this->failAuthentication($request);
    }

    /**
     * Handle a failed authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function failAuthentication(Request $request)
    {
        return back()
            ->with('error', __('auth.failed'))
            ->withInput($request->only('email'));
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Responses\LogoutResponse
     */
    public function destroy(Request $request)
    {
        // Log logout before the session is invalidated
        try {
            if (Auth::check()) {
                app(ActivityLogService::class)->log(
                    ActivityLog::MODULE_DASHBOARD,
                    ActivityLog::ACTION_LOGOUT,
                    'User logged out: ' . Auth::user()->name . ' (' . Auth::user()->email . ')',
                );
            }
        } catch (\Throwable $e) {
            // never block logout because of logging failure
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return new LogoutResponse();
    }
}