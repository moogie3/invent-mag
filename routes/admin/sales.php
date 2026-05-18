<?php

use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\SalesReturnController;

// Sales Routes
Route::prefix('sales')->group(function () {
    Route::get('/', [SalesController::class, 'index'])
        ->name('admin.sales')
        ->middleware('can:view-sales');

    Route::get('/create', [SalesController::class, 'create'])
        ->name('admin.sales.create')
        ->middleware('can:create-sales');

    Route::post('/store', [SalesController::class, 'store'])
        ->name('admin.sales.store')
        ->middleware('can:create-sales');

    Route::get('/edit/{id}', [SalesController::class, 'edit'])
        ->name('admin.sales.edit')
        ->middleware('can:edit-sales');

    Route::get('/show/{id}', [SalesController::class, 'show'])
        ->name('admin.sales.show')
        ->middleware('can:view-sales');

    Route::put('/update/{id}', [SalesController::class, 'update'])
        ->name('admin.sales.update')
        ->middleware('can:edit-sales');

    Route::delete('/destroy/{id}', [SalesController::class, 'destroy'])
        ->name('admin.sales.destroy')
        ->middleware('can:delete-sales');

    Route::get('/product/{id}', [SalesController::class, 'getInvoiceDetails'])
        ->name('admin.sales.product.details')
        ->middleware('can:view-sales');

    Route::get('/modal-view/{id}', [SalesController::class, 'modalViews'])
        ->name('admin.sales.modal-view')
        ->middleware('can:view-sales');

    Route::post('/bulk-delete', [SalesController::class, 'bulkDelete'])
        ->name('sales.bulk-delete')
        ->middleware('can:delete-sales');

    Route::post('/bulk-mark-paid', [SalesController::class, 'bulkMarkPaid'])
        ->name('sales.bulk-mark-paid')
        ->middleware('can:edit-sales');

    Route::post('/bulk-export', [SalesController::class, 'bulkExport'])
        ->name('sales.bulk-export')
        ->middleware('can:view-sales');

    Route::get('/get-customer-price/{customer}/{product}', [SalesController::class, 'getCustomerPrice'])
        ->name('admin.sales.get-customer-price')
        ->middleware('can:view-sales');

    Route::get('/metrics', [SalesController::class, 'getSalesMetrics'])
        ->name('admin.sales.metrics')
        ->middleware('can:view-sales');

    Route::get('/expiring-soon', [SalesController::class, 'getExpiringSoonSales'])
        ->name('admin.sales.expiring-soon')
        ->middleware('can:view-sales');

    Route::get('/print/{id}', [SalesController::class, 'print'])
        ->name('admin.sales.print')
        ->middleware('can:view-sales');

    Route::post('/{id}/payment', [SalesController::class, 'addPayment'])
        ->name('admin.sales.add-payment')
        ->middleware('can:edit-sales');
});

// Sales Return Routes - Protected by plan feature
Route::middleware('plan.feature:sales_returns')->group(function () {
    Route::get('sales-returns/sale/{sale}', [SalesReturnController::class, 'getSalesItems'])->name('admin.sales-returns.items');
    Route::get('sales-returns/{salesReturn}/modal-view', [SalesReturnController::class, 'modalView'])->name('admin.sales-returns.modal-view');
    Route::resource('sales-returns', SalesReturnController::class)->names('admin.sales-returns');
    Route::post('sales-returns/bulk-delete', [SalesReturnController::class, 'bulkDelete'])->name('admin.sales-returns.bulk-delete');
    Route::post('sales-returns/bulk-complete', [SalesReturnController::class, 'bulkComplete'])->name('admin.sales-returns.bulk-complete');
    Route::post('sales-returns/bulk-cancel', [SalesReturnController::class, 'bulkCancel'])->name('admin.sales-returns.bulk-cancel');
    Route::post('sales-returns/bulk-export', [SalesReturnController::class, 'bulkExport'])->name('admin.sales-returns.bulk-export');
    Route::get('sales-returns/print/{id}', [SalesReturnController::class, 'print'])->name('admin.sales-returns.print');
});
