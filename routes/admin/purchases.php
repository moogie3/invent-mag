<?php

use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\PurchaseReturnController;

// Purchase Order Routes
Route::prefix('po')->group(function () {
    Route::get('/', [PurchaseController::class, 'index'])
        ->name('admin.po')
        ->middleware('can:view-purchase-orders');

    Route::get('/create', [PurchaseController::class, 'create'])
        ->name('admin.po.create')
        ->middleware('can:create-purchase-orders');

    Route::post('/store', [PurchaseController::class, 'store'])
        ->name('admin.po.store')
        ->middleware('can:create-purchase-orders');

    Route::get('/edit/{id}', [PurchaseController::class, 'edit'])
        ->name('admin.po.edit')
        ->middleware('can:edit-purchase-orders');

    Route::get('/show/{id}', [PurchaseController::class, 'show'])
        ->name('admin.po.show')
        ->middleware('can:view-purchase-orders');

    Route::put('/update/{id}', [PurchaseController::class, 'update'])
        ->name('admin.po.update')
        ->middleware('can:edit-purchase-orders');

    Route::delete('/destroy/{id}', [PurchaseController::class, 'destroy'])
        ->name('admin.po.destroy')
        ->middleware('can:delete-purchase-orders');

    Route::get('/product/{id}', [PurchaseController::class, 'getProductDetails'])
        ->name('admin.po.product.details')
        ->middleware('can:view-purchase-orders');

    Route::get('/modal-view/{id}', [PurchaseController::class, 'modalView'])
        ->name('admin.po.modal-view')
        ->middleware('can:view-purchase-orders');

    Route::post('/bulk-delete', [PurchaseController::class, 'bulkDelete'])
        ->name('po.bulk-delete')
        ->middleware('can:delete-purchase-orders');

    Route::post('/bulk-mark-paid', [PurchaseController::class, 'bulkMarkPaid'])
        ->name('po.bulk-mark-paid')
        ->middleware('can:edit-purchase-orders');

    Route::post('/bulk-export', [PurchaseController::class, 'bulkExport'])
        ->name('po.bulk-export')
        ->middleware('can:view-purchase-orders');

    Route::get('/metrics', [PurchaseController::class, 'getPurchaseMetrics'])
        ->name('admin.po.metrics')
        ->middleware('can:view-purchase-orders');

    Route::get('/expiring-soon', [PurchaseController::class, 'getExpiringSoonPurchases'])
        ->name('admin.po.expiring-soon')
        ->middleware('can:view-purchase-orders');

    Route::get('/print/{id}', [PurchaseController::class, 'print'])
        ->name('admin.po.print')
        ->middleware('can:view-purchase-orders');

    Route::post('/{id}/payment', [PurchaseController::class, 'addPayment'])
        ->name('admin.po.add-payment')
        ->middleware('can:edit-purchase-orders');
});

// Purchase Return Routes - Protected by plan feature
Route::middleware('plan.feature:purchase_returns')->group(function () {
    Route::resource('por', PurchaseReturnController::class)->names('admin.por');
    Route::get('por/{por}/modal-view', [PurchaseReturnController::class, 'modalView'])->name('admin.por.modal-view');
    Route::get('por/purchase/{purchase}', [PurchaseReturnController::class, 'getPurchaseItems'])->name('admin.por.items');
    Route::post('por/bulk-delete', [PurchaseReturnController::class, 'bulkDelete'])->name('admin.por.bulk-delete');
    Route::post('por/bulk-complete', [PurchaseReturnController::class, 'bulkComplete'])->name('admin.por.bulk-complete');
    Route::post('por/bulk-cancel', [PurchaseReturnController::class, 'bulkCancel'])->name('admin.por.bulk-cancel');
    Route::post('por/bulk-export', [PurchaseReturnController::class, 'bulkExport'])->name('admin.por.bulk-export');
    Route::get('por/print/{id}', [PurchaseReturnController::class, 'print'])->name('admin.por.print');
});
