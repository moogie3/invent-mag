<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Admin routes - all under /admin prefix with web middleware
Route::middleware('web')->prefix('admin')->group(function () {
    // Auth routes (login, logout, password reset, email verification)
    require __DIR__.'/admin/auth.php';

    // Protected routes (requires auth + verified)
    Route::middleware(['auth', 'verified'])->group(function () {
        require __DIR__.'/admin/dashboard.php';
        require __DIR__.'/admin/products.php';
        require __DIR__.'/admin/contacts.php';
        require __DIR__.'/admin/crm.php';
        require __DIR__.'/admin/purchases.php';
        require __DIR__.'/admin/sales.php';
        require __DIR__.'/admin/inventory.php';
        require __DIR__.'/admin/reports.php';
        require __DIR__.'/admin/notifications.php';
        require __DIR__.'/admin/hr.php';
        require __DIR__.'/admin/settings.php';
        require __DIR__.'/admin/accounting.php';
        require __DIR__.'/admin/quotations.php';
        require __DIR__.'/admin/efaktur.php';
        require __DIR__.'/admin/bank-reconciliation.php';
    });
});

// Root redirect to admin login
Route::get('/', function (Request $request) {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('admin.login');
});

// Fallback route for 404 - must be LAST
Route::fallback(function (Request $request) {
    return response()->view('errors.404', [], 404);
});
