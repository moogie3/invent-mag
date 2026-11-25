<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\UserController;

// Notification Routes
Route::prefix('notifications')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications');
    Route::get('/list', [NotificationController::class, 'getNotifications'])->name('admin.notifications.list');
    Route::post('/mark-read/{id}', [NotificationController::class, 'markAsRead'])->name('admin.notifications.mark-read');
    Route::post('/clear-all', [NotificationController::class, 'clearAll'])->name('admin.notifications.clear-all');
    Route::get('/count', [NotificationController::class, 'count'])->name('admin.notifications.count');
    Route::get('/view/{id}', [NotificationController::class, 'view'])->name('admin.notifications.view');
});

// User Management Routes
// create is excluded: user creation happens via the modal on the index page
Route::middleware(['role:superuser'])->group(function () {
    Route::resource('users', UserController::class)->except(['create'])->names('admin.users');
});

// Audit Log - superuser only
Route::middleware(['role:superuser'])->group(function () {
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('admin.audit-log.index');
    Route::get('/audit-log/{auditLog}', [AuditLogController::class, 'show'])->name('admin.audit-log.show');
});
