<?php

namespace App\Providers;

use App\Models\User; // Added
use App\Scopes\TenantScope; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Validation\ValidationException;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\CustomLoginResponse;
use App\Http\Responses\CustomRegisterResponse;
use App\Http\Responses\VerifyEmailResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth; // Added for Auth::attempt in case needed.

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, CustomLoginResponse::class);
        $this->app->singleton(RegisterResponse::class, CustomRegisterResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
        $this->app->singleton(\Laravel\Fortify\Contracts\PasswordResetResponse::class, \App\Http\Responses\PasswordResetResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure views for admin routes
        Fortify::loginView(fn() => view('admin.auth.login'));
        Fortify::registerView(fn() => view('admin.auth.register'));
        Fortify::requestPasswordResetLinkView(fn() => view('admin.auth.forgot-password'));
        Fortify::resetPasswordView(fn($request) => view('admin.auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn() => view('admin.auth.verify-email'));
        Fortify::confirmPasswordView(fn() => view('admin.auth.confirm-password'));

        // Register Fortify actions
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Define authentication pipeline, REMOVING the default rate limiter.
        Fortify::authenticateThrough(function (Request $request) {
            return array_filter([
                // EnsureLoginIsNotThrottled::class has been removed
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class
            ]);
        });
        
        // Custom, self-contained authentication logic for multi-tenancy with direct rate limiting
        Fortify::authenticateUsing(function (Request $request) {
            $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

            // Store the attempted email in session for back button protection
            $request->session()->put('attempted_email', $request->input('email'));

            // Manually handle the rate limiting directly.
            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                $seconds = RateLimiter::availableIn($throttleKey);
                // Abort directly with the 429 error page. This stops everything.
                abort(response()->view('errors.429', ['seconds' => $seconds], 429));
            }

            // Find the user by email, respecting the global TenantScope automatically.
            // Since User model has BelongsToTenant trait and global TenantScope,
            // this query will ONLY look for users where tenant_id matches the current tenant.
            $user = User::where('email', $request->email)->first();

            // If user not found (in this tenant) or password fails:
            if (! $user || ! Hash::check($request->password, $user->password)) {
                RateLimiter::hit($throttleKey);
                throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
            }

            // If we get here, the user exists IN THIS TENANT and password is correct.
            RateLimiter::clear($throttleKey);
            return $user;
        });

        // Rate limiting configuration
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower($request->input('email', 'guest'));
            $ip = $request->ip() ?? '127.0.0.1';
            $throttleKey = "$email|$ip";

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60
                    ], 429);
                });
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again in ' . 
                                     ($headers['Retry-After'] ?? 60) . ' seconds.',
                    ], 429);
                });
        });
    }
}
