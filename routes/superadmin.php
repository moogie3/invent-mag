<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\Auth\LoginController;

Route::middleware('guest:super_admin')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('superadmin.login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth:super_admin')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('superadmin.logout');
    Route::get('/', [DashboardController::class, 'index'])->name('superadmin.dashboard');

    // Redirect common tenant paths back to super admin dashboard to prevent
    // tenant routes (web.php) from matching on the super admin domain.
    Route::redirect('/admin/dashboard', '/');
    Route::redirect('/admin', '/');
    Route::redirect('/dashboard', '/');

    // Catch-all: any other unmatched path on super admin domain goes to dashboard
    Route::get('/{any}', function () {
        return redirect()->route('superadmin.dashboard');
    })->where('any', '.*');
});
