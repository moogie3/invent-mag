<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Stop-impersonating route — accessible from the tenant subdomain while logged in as a tenant user.
// Uses the 'auth' middleware (web guard) since we are logged in as a tenant user at this point.
Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    Route::post('/stop-impersonating', function (Request $request) {
        $returnUrl = session('superadmin_return_url');

        session()->forget([
            'impersonating_tenant_id',
            'impersonating_tenant_name',
            'impersonating_user_name',
            'superadmin_return_url',
        ]);

        Auth::guard('web')->logout();
        $request->session()->regenerate();

        // Redirect to superadmin if we have the URL, otherwise to the app root
        if ($returnUrl) {
            return redirect()->away($returnUrl)->with('success', 'Impersonation ended. Welcome back.');
        }

        return redirect()->to(config('app.superadmin_url', '/superadmin'))->with('success', 'Impersonation ended.');
    })->name('admin.stop-impersonating');
});

// Admin routes - all under /admin prefix with web middleware
Route::middleware('web')->prefix('admin')->group(function () {
    // Auth routes (login, logout, password reset, email verification)
    require __DIR__.'/admin/auth.php';

    // Protected routes (requires auth + verified + active subscription)
    Route::middleware(['auth', 'verified', 'subscription.active'])->group(function () {
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

// Root redirect to frontend login
Route::get('/', function (Request $request) {
    // If workspace param exists, the HandleWorkspaceParam middleware will handle it
    // This route only handles requests without workspace
    $frontendUrl = config('app.frontend_url');

    return redirect()->away($frontendUrl.'/login');
});

// Fallback route for 404 - must be LAST
Route::fallback(function (Request $request) {
    return response()->view('errors.404', [], 404);
});
