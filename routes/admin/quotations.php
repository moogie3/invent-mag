<?php

use App\Http\Controllers\Admin\QuotationController;

// Quotation Routes - Protected by plan feature (Professional+)
Route::prefix('quotations')->middleware('plan.feature:quotations')->group(function () {
    Route::get('/', [QuotationController::class, 'index'])
        ->name('admin.quotations')
        ->middleware('can:view-quotations');

    Route::get('/create', [QuotationController::class, 'create'])
        ->name('admin.quotations.create')
        ->middleware('can:create-quotations');

    Route::post('/store', [QuotationController::class, 'store'])
        ->name('admin.quotations.store')
        ->middleware('can:create-quotations');

    Route::get('/edit/{id}', [QuotationController::class, 'edit'])
        ->name('admin.quotations.edit')
        ->middleware('can:edit-quotations');

    Route::get('/show/{id}', [QuotationController::class, 'show'])
        ->name('admin.quotations.show')
        ->middleware('can:view-quotations');

    Route::put('/update/{id}', [QuotationController::class, 'update'])
        ->name('admin.quotations.update')
        ->middleware('can:edit-quotations');

    Route::delete('/destroy/{id}', [QuotationController::class, 'destroy'])
        ->name('admin.quotations.destroy')
        ->middleware('can:delete-quotations');

    Route::post('/{id}/convert-to-sales', [QuotationController::class, 'convertToSales'])
        ->name('admin.quotations.convert-to-sales')
        ->middleware('can:create-sales');

    Route::post('/status/{id}', [QuotationController::class, 'updateStatus'])
        ->name('admin.quotations.status')
        ->middleware('can:edit-quotations');

    Route::get('/print/{id}', [QuotationController::class, 'print'])
        ->name('admin.quotations.print')
        ->middleware('can:view-quotations');

    Route::get('/product/{id}', [QuotationController::class, 'getProductDetails'])
        ->name('admin.quotations.product.details')
        ->middleware('can:view-quotations');

    Route::get('/modal-view/{id}', [QuotationController::class, 'modalViews'])
        ->name('admin.quotations.modal-view')
        ->middleware('can:view-quotations');

    Route::post('/bulk-delete', [QuotationController::class, 'bulkDelete'])
        ->name('admin.quotations.bulk-delete')
        ->middleware('can:delete-quotations');

    Route::post('/bulk-export', [QuotationController::class, 'bulkExport'])
        ->name('admin.quotations.bulk-export')
        ->middleware('can:view-quotations');
});
