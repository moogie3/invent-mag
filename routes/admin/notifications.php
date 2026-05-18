<?php

use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AuditLogController;

// Notification Routes
Route::prefix('notifications')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications');
    Route::get('/list', [NotificationController::class, 'getNotifications'])->name('admin.notifications.list');
    Route::post('/mark-read/{id}', [NotificationController::class, 'markAsRead'])->name('admin.notifications.mark-read');
    Route::post('/clear-all', [NotificationController::class, 'clearAll'])->name('admin.notifications.clear-all');
    Route::get('/count', [NotificationController::class, 'count'])->name('admin.notifications.count');
    Route::get('/view/{id}', [NotificationController::class, 'view'])->name('admin.notifications.view');
});

// User Management Routes - with plan limit on user creation
Route::middleware(['role:superuser', 'plan.limit:users'])->group(function () {
    Route::resource('users', UserController::class)->names('admin.users');
});

// Audit Log - superuser only + Enterprise plan required
Route::middleware(['role:superuser', 'plan.feature:audit_compliance'])->group(function () {
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('admin.audit-log.index');
    Route::get('/audit-log/{auditLog}', [AuditLogController::class, 'show'])->name('admin.audit-log.show');
});
