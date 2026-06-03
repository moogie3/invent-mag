<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use App\Http\Controllers\Auth\LoginController;

Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', function (Request $request) {
        $email = $request->session()->get('attempted_email');
        $ip = $request->ip() ?? 'unknown';

        // 1. Check by Email if session has it
        if ($email) {
            $throttleKey = "login|" . Str::lower($email) . "|" . $ip;
            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                $seconds = RateLimiter::availableIn($throttleKey);
                return response()->view('errors.429', ['seconds' => $seconds], 429);
            }
        }

        // 2. Secondary check by IP only to prevent "New Tab" bypass
        $ipKey = "login_ip|" . $ip;
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);
            return response()->view('errors.429', ['seconds' => $seconds], 429);
        }

        return view('admin.auth.login');
    })->name('admin.login');

    // Forgot Password
    Route::get('/forgot-password', fn() => view('admin.auth.forgot-password'))->name('admin.password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('admin.password.email');

    // Reset Password
    Route::get('/reset-password/{token}', fn($token) => view('admin.auth.reset-password', ['token' => $token]))->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

// Email Verification Success Page
Route::get('/email/verified', function () {
    return view('admin.auth.verify-email-success');
})->name('verification.verified');

// Override Fortify's verification.send route to ensure correct middleware
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::post('/logout', [LoginController::class, 'destroy'])->name('admin.logout');

// Session keepalive — used by the inactivity modal "Stay" button to refresh server session
Route::post('/session/keepalive', [LoginController::class, 'keepAlive'])
    ->middleware('auth')
    ->name('admin.session.keepalive');
