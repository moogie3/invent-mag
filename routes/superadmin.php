<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\Auth\LoginController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\PermissionController;
use App\Http\Controllers\SuperAdmin\BackupController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\SettingController;
use App\Http\Controllers\SuperAdmin\FeatureController;
use App\Http\Controllers\SuperAdmin\BillingController;

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
    Route::post('/tenants/{tenant}/toggle-active', [TenantController::class, 'toggleActive'])->name('superadmin.tenants.toggle-active');

    // Plans
    Route::get('/plans', [PlanController::class, 'index'])->name('superadmin.plans.index');
    Route::get('/plans/create', [PlanController::class, 'create'])->name('superadmin.plans.create');
    Route::post('/plans', [PlanController::class, 'store'])->name('superadmin.plans.store');
    Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('superadmin.plans.edit');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('superadmin.plans.update');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('superadmin.plans.destroy');

    // Subscriptions
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('superadmin.subscriptions.index');

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])->name('superadmin.permissions.index');

    // Backups
    Route::get('/backups', [BackupController::class, 'index'])->name('superadmin.backups.index');

    // Audit Log
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('superadmin.audit-log.index');
    Route::get('/audit-log/{log}', [AuditLogController::class, 'show'])->name('superadmin.audit-log.show');

    // System Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('superadmin.settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('superadmin.settings.update');

    // Features
    Route::get('/features', [FeatureController::class, 'index'])->name('superadmin.features.index');
    Route::post('/features', [FeatureController::class, 'update'])->name('superadmin.features.update');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('superadmin.billing.index');
    Route::get('/billing/{order}', [BillingController::class, 'show'])->name('superadmin.billing.show');

    // Redirect common tenant paths back to super admin dashboard
    Route::redirect('/admin/dashboard', '/');
    Route::redirect('/admin', '/');
    Route::redirect('/dashboard', '/');

    // Catch-all: any other unmatched path on super admin domain goes to dashboard
    Route::get('/{any}', function () {
        return redirect()->route('superadmin.dashboard');
    })->where('any', '.*');
});
