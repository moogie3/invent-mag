<?php

use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\UnitController;

Route::get('api/settings', [SettingsController::class, 'getSettings'])->name('admin.api.settings');

// Settings Routes
Route::prefix('settings')->group(function () {
    // Currency Settings
    Route::prefix('currency')->middleware(['role:superuser'])->group(function () {
        Route::get('/', [CurrencyController::class, 'edit'])->name('admin.setting.currency.edit');
        Route::post('/update', [CurrencyController::class, 'update'])->name('admin.setting.currency.update');
    });

    // Profile Settings
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('admin.setting.profile.edit');
        Route::put('/update', [ProfileController::class, 'update'])->name('admin.setting.profile.update');
        Route::put('/update-password', [ProfileController::class, 'updatePassword'])->name('admin.setting.profile.update-password');
        Route::delete('/delete-avatar', [ProfileController::class, 'deleteAvatar'])->name('admin.setting.profile.delete-avatar');
        Route::post('/token', [ProfileController::class, 'generateApiToken'])->name('admin.setting.profile.token');
    });

    // Tax Settings - Full CRUD
    Route::prefix('tax')->middleware('role:superuser')->group(function () {
        Route::get('/', [TaxController::class, 'index'])->name('admin.setting.tax');
        Route::post('/store', [TaxController::class, 'store'])->name('admin.setting.tax.store');
        Route::put('/{tax}', [TaxController::class, 'update'])->name('admin.setting.tax.update');
        Route::delete('/{tax}', [TaxController::class, 'destroy'])->name('admin.setting.tax.destroy');
        Route::post('/{tax}/default', [TaxController::class, 'setDefault'])->name('admin.setting.tax.default');
        Route::get('/get', [TaxController::class, 'getTaxRate'])->name('admin.setting.tax.get');
    });

    // Category Settings
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('admin.setting.category');
        Route::post('/store', [CategoryController::class, 'store'])->name('admin.setting.category.store');
        Route::put('/update/{id}', [CategoryController::class, 'update'])->name('admin.setting.category.update');
        Route::delete('/destroy/{id}', [CategoryController::class, 'destroy'])->name('admin.setting.category.destroy');
    });

    // Unit Settings
    Route::prefix('units')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('admin.setting.unit');
        Route::post('/store', [UnitController::class, 'store'])->name('admin.setting.unit.store');
        Route::put('/update/{id}', [UnitController::class, 'update'])->name('admin.setting.unit.update');
        Route::delete('/destroy/{id}', [UnitController::class, 'destroy'])->name('admin.setting.unit.destroy');
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.setting.notifications');

    Route::get('/', [SettingsController::class, 'index'])->name('admin.setting.index');
    Route::put('/', [SettingsController::class, 'update'])->name('admin.setting.update');
    Route::put('/update-theme-mode', [SettingsController::class, 'updateThemeMode'])->name('admin.setting.update-theme-mode');
    Route::put('/update-tax-jurisdiction', [SettingsController::class, 'updateTaxJurisdiction'])->name('admin.setting.update-tax-jurisdiction');

    Route::get('/accounting', [AccountingController::class, 'accounting'])->name('admin.setting.accounting');
    Route::post('/accounting', [AccountingController::class, 'updateAccounting'])->name('admin.setting.accounting.update');
    Route::post('/reset-coa-default', [AccountingController::class, 'resetToDefault'])->name('admin.setting.reset-coa-default');

    // Backup Settings
    Route::prefix('backup')->middleware('role:superuser')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('admin.setting.backup.index');
        Route::post('/run', [BackupController::class, 'run'])->name('admin.setting.backup.run');
        Route::get('/download/{filename}', [BackupController::class, 'download'])->name('admin.setting.backup.download');
        Route::delete('/delete/{filename}', [BackupController::class, 'delete'])->name('admin.setting.backup.delete');
        Route::post('/restore', [BackupController::class, 'restore'])->name('admin.setting.backup.restore');
        Route::post('/password', [BackupController::class, 'updatePassword'])->name('admin.setting.backup.password');
    });
});
