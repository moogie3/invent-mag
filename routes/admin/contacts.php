<?php

use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerCrmController;
use App\Http\Controllers\Admin\SupplierCrmController;

// Supplier Routes
Route::prefix('supplier')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('admin.supplier')->middleware('can:view-supplier');
    Route::get('/create', [SupplierController::class, 'create'])->name('admin.supplier.create')->middleware('can:create-supplier');
    Route::post('/store', [SupplierController::class, 'store'])->name('admin.supplier.store')->middleware('can:create-supplier');
    Route::get('/edit/{id}', [SupplierController::class, 'edit'])->name('admin.supplier.edit')->middleware('can:edit-supplier');
    Route::put('/update/{id}', [SupplierController::class, 'update'])->name('admin.supplier.update')->middleware('can:edit-supplier');
    Route::delete('/destroy/{id}', [SupplierController::class, 'destroy'])->name('admin.supplier.destroy')->middleware('can:delete-supplier');
    Route::get('/metrics', [SupplierController::class, 'getMetrics'])->name('admin.supplier.metrics')->middleware('can:view-supplier');
    Route::post('/export', [SupplierController::class, 'exportAll'])
        ->name('admin.supplier.export')
        ->middleware('can:view-supplier');
});

// Customer Routes
Route::prefix('customer')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('admin.customer')->middleware('can:view-customer');
    Route::get('/create', [CustomerController::class, 'create'])->name('admin.customer.create')->middleware('can:create-customer');
    Route::post('/store', [CustomerController::class, 'store'])->name('admin.customer.store')->middleware('can:create-customer');
    Route::get('/edit/{id}', [CustomerController::class, 'edit'])->name('admin.customer.edit')->middleware('can:edit-customer');
    Route::put('/update/{id}', [CustomerController::class, 'update'])->name('admin.customer.update')->middleware('can:edit-customer');
    Route::delete('/destroy/{id}', [CustomerController::class, 'destroy'])->name('admin.customer.destroy')->middleware('can:delete-customer');
    Route::post('/quick-create', [CustomerController::class, 'quickCreate'])->name('admin.customer.quickCreate')->middleware('can:create-customer');
    Route::get('/metrics', [CustomerController::class, 'getMetrics'])->name('admin.customer.metrics')->middleware('can:view-customer');
    Route::get('/{customer}/historical-purchases', [CustomerCrmController::class, 'getHistoricalPurchases'])->name('admin.customer.historical-purchases')->middleware('can:view-customer');
    Route::get('/{customer}/credits', [CustomerController::class, 'getCredits'])->name('admin.customer.credits')->middleware('can:view-customer');
    Route::post('/export', [CustomerController::class, 'exportAll'])
        ->name('admin.customer.export')
        ->middleware('can:view-customer');
});

// CRM Routes - Protected by customer permissions
Route::middleware('can:view-customer')->group(function () {
    Route::get('/customers/{id}/crm-details', [CustomerCrmController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/customers/{id}/historical-purchases', [CustomerCrmController::class, 'getHistoricalPurchases'])->where('id', '[0-9]+');
    Route::get('/customers/{id}/product-history', [CustomerCrmController::class, 'getProductHistory'])->where('id', '[0-9]+');
});

Route::middleware('can:create-customer-interactions')->group(function () {
    Route::post('/customers/{id}/interactions', [CustomerCrmController::class, 'storeInteraction'])->where('id', '[0-9]+');
});

// SRM Routes - Protected by supplier permissions
Route::middleware('can:view-supplier')->group(function () {
    Route::get('/suppliers/{id}/srm-details', [SupplierCrmController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/suppliers/{id}/historical-purchases', [SupplierCrmController::class, 'getHistoricalPurchases'])->where('id', '[0-9]+');
    Route::get('/suppliers/{id}/product-history', [SupplierCrmController::class, 'getProductHistory'])->where('id', '[0-9]+');
});

Route::middleware('can:edit-supplier')->group(function () {
    Route::post('/suppliers/{id}/interactions', [SupplierCrmController::class, 'storeInteraction'])->where('id', '[0-9]+');
});
