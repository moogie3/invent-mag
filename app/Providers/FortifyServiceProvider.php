<?php

namespace App\Providers;

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
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Symfony\Component\HttpFoundation\Response;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, CustomLoginResponse::class);
        $this->app->singleton(RegisterResponse::class, CustomRegisterResponse::class);
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

        // Register Fortify actions
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Define authentication pipeline
        Fortify::authenticateThrough(function (Request $request) {
            return array_filter([config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class, Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null, AttemptToAuthenticate::class, PrepareAuthenticatedSession::class]);
        });

        Fortify::authenticateUsing(function (Request $request) {
            $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

            // Store the attempted email in session for back button protection
            $request->session()->put('attempted_email', $request->input('email'));

            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                // Reset login attempts on success
                RateLimiter::clear($throttleKey);
                return $user;
            }

            // Increment the rate limiter on failed attempts
            RateLimiter::hit($throttleKey, 60); // lock for 60 seconds per attempt

            // Check if we've now hit the limit (5 attempts)
            if (RateLimiter::attempts($throttleKey) >= 5) {
                $seconds = RateLimiter::availableIn($throttleKey);
                // Instead of returning a response, throw an exception
                throw ValidationException::withMessages([
                    'email' => [
                        trans('auth.throttle', [
                            'seconds' => $seconds,
                            'minutes' => ceil($seconds / 60),
                        ]),
                    ],
                ]);
            }

            return null;
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
    }
}
