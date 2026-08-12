<?php

use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\Auth\LoginController;
use App\Http\Controllers\SuperAdmin\BackupController;
use App\Http\Controllers\SuperAdmin\BillingController;
use App\Http\Controllers\SuperAdmin\CmsController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\FeatureController;
use App\Http\Controllers\SuperAdmin\InvoiceController;
use App\Http\Controllers\SuperAdmin\PaymentOverviewController;
use App\Http\Controllers\SuperAdmin\PendingPaymentController;
use App\Http\Controllers\SuperAdmin\PermissionController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\SettingController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:super_admin')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('superadmin.login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth:super_admin')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('superadmin.logout');
    Route::get('/', [DashboardController::class, 'index'])->name('superadmin.dashboard');

    // Tenants
    Route::get('/tenants', [TenantController::class, 'index'])->name('superadmin.tenants.index');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('superadmin.tenants.show');
    Route::post('/tenants/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('superadmin.tenants.impersonate');
    Route::post('/tenants/stop-impersonating', [TenantController::class, 'stopImpersonating'])->name('superadmin.stop-impersonating');
    Route::post('/tenants/{tenant}/toggle-active', [TenantController::class, 'toggleActive'])->name('superadmin.tenants.toggle-active');
    Route::put('/tenants/{tenant}/domain', [TenantController::class, 'updateDomain'])->name('superadmin.tenants.update-domain');

    // Plans
    Route::get('/plans', [PlanController::class, 'index'])->name('superadmin.plans.index');
    Route::get('/plans/create', [PlanController::class, 'create'])->name('superadmin.plans.create');
    Route::post('/plans', [PlanController::class, 'store'])->name('superadmin.plans.store');
    Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('superadmin.plans.edit');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('superadmin.plans.update');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('superadmin.plans.destroy');

    // Subscriptions
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('superadmin.subscriptions.index');
    Route::post('/subscriptions/{tenant}/terminate', [SubscriptionController::class, 'terminate'])->name('superadmin.subscriptions.terminate');
    Route::post('/subscriptions/{tenant}/reactivate', [SubscriptionController::class, 'reactivate'])->name('superadmin.subscriptions.reactivate');

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])->name('superadmin.permissions.index');

    // Backups
    Route::get('/backups', [BackupController::class, 'index'])->name('superadmin.backups.index');
    Route::post('/backups/create', [BackupController::class, 'create'])->name('superadmin.backups.create');
    Route::post('/backups/create-tenant', [BackupController::class, 'createTenant'])->name('superadmin.backups.create-tenant');
    Route::post('/backups/restore', [BackupController::class, 'restore'])->name('superadmin.backups.restore');
    Route::get('/backups/{filename}/download', [BackupController::class, 'download'])->name('superadmin.backups.download');
    Route::delete('/backups/{filename}', [BackupController::class, 'destroy'])->name('superadmin.backups.destroy');

    // Audit Log
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('superadmin.audit-log.index');
    Route::get('/audit-log/{log}', [AuditLogController::class, 'show'])->name('superadmin.audit-log.show');

    // System Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('superadmin.settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('superadmin.settings.update');
    Route::get('/settings/preferences', [SettingController::class, 'preferences'])->name('superadmin.settings.preferences');
    Route::post('/settings/preferences', [SettingController::class, 'updatePreferences'])->name('superadmin.settings.preferences.update');
    Route::post('/settings/update-theme-mode', [SettingController::class, 'updateThemeMode'])->name('superadmin.settings.update-theme-mode');
    Route::get('/settings/platform-currency', [SettingController::class, 'platformCurrency'])->name('superadmin.settings.platform-currency');
    Route::post('/settings/platform-currency', [SettingController::class, 'updatePlatformCurrency'])->name('superadmin.settings.platform-currency.update');
    Route::get('/settings/billing-rate', [SettingController::class, 'billingRate'])->name('superadmin.settings.billing-rate');
    Route::post('/settings/billing-rate', [SettingController::class, 'updateBillingRate'])->name('superadmin.settings.billing-rate.update');

    // Features
    Route::get('/features', [FeatureController::class, 'index'])->name('superadmin.features.index');
    Route::post('/features', [FeatureController::class, 'update'])->name('superadmin.features.update');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('superadmin.billing.index');
    Route::get('/billing/{order}', [BillingController::class, 'show'])->name('superadmin.billing.show');
    Route::post('/billing/{order}/void', [BillingController::class, 'void'])->name('superadmin.billing.void');

    // Payment Overview
    Route::get('/payment-overview', [PaymentOverviewController::class, 'index'])->name('superadmin.payment-overview.index');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('superadmin.transactions.index');

    // Pending Payments
    Route::get('/pending-payments', [PendingPaymentController::class, 'index'])->name('superadmin.pending-payments.index');
    Route::post('/pending-payments/{order}/approve', [PendingPaymentController::class, 'approve'])->name('superadmin.pending-payments.approve');
    Route::post('/pending-payments/{order}/reject', [PendingPaymentController::class, 'reject'])->name('superadmin.pending-payments.reject');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('superadmin.invoices.index');
    Route::get('/invoices/{order}', [InvoiceController::class, 'show'])->name('superadmin.invoices.show');

    // CMS (Landing Page)
    Route::get('/cms', [CmsController::class, 'index'])->name('superadmin.cms.index');
    Route::post('/cms', [CmsController::class, 'update'])->name('superadmin.cms.update');

    // Redirect common tenant paths back to super admin dashboard
    Route::redirect('/admin/dashboard', '/');
    Route::redirect('/admin', '/');
    Route::redirect('/dashboard', '/');

    // Catch-all: any other unmatched path on super admin domain goes to dashboard
    Route::get('/{any}', function () {
        return redirect()->route('superadmin.dashboard');
    })->where('any', '.*');
});
