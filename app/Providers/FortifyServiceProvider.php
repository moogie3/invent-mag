<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Cache\RateLimiting\Limit;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\CustomLoginResponse;
use App\Http\Responses\CustomRegisterResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;

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
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('admin.auth.forgot-password'));
        Fortify::resetPasswordView(fn ($request) => view('admin.auth.reset-password', ['request' => $request]));

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = \App\Models\User::where('email', $request->email)->first();

            return ($user && Hash::check($request->password, $user->password)) ? $user : null;
        });

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