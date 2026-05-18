<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Admin routes - all under /admin prefix with web middleware
Route::middleware('web')->prefix('admin')->group(function () {
    // Auth routes (login, logout, password reset, email verification)
    require __DIR__ . '/admin/auth.php';

    // Protected routes (requires auth + verified + active subscription)
    Route::middleware(['auth', 'verified', 'subscription.active'])->group(function () {
        require __DIR__ . '/admin/dashboard.php';
        require __DIR__ . '/admin/products.php';
        require __DIR__ . '/admin/contacts.php';
        require __DIR__ . '/admin/crm.php';
        require __DIR__ . '/admin/purchases.php';
        require __DIR__ . '/admin/sales.php';
        require __DIR__ . '/admin/inventory.php';
        require __DIR__ . '/admin/reports.php';
        require __DIR__ . '/admin/notifications.php';
        require __DIR__ . '/admin/hr.php';
        require __DIR__ . '/admin/settings.php';
        require __DIR__ . '/admin/accounting.php';
    });
});

// Root redirect to frontend login
Route::get('/', function (Request $request) {
    // If workspace param exists, the HandleWorkspaceParam middleware will handle it
    // This route only handles requests without workspace
    $frontendUrl = config('app.frontend_url');
    return redirect()->away($frontendUrl . '/login');
});

// Fallback route for 404 - must be LAST
Route::fallback(function (Request $request) {
    return response()->view('errors.404', [], 404);
});
