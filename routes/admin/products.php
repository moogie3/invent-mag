<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductStockController;

// Product Routes
Route::prefix('product')->group(function () {
    Route::get('/', [ProductController::class, 'index'])
        ->name('admin.product')
        ->middleware('can:view-products');

    Route::get('/create', [ProductController::class, 'create'])
        ->name('admin.product.create')
        ->middleware('can:create-products');

    Route::post('/store', [ProductController::class, 'store'])
        ->name('admin.product.store')
        ->middleware('can:create-products', 'plan.limit:products');

    Route::get('/edit/{id}', [ProductController::class, 'edit'])
        ->name('admin.product.edit')
        ->middleware('can:edit-products');

    Route::put('/update/{id}', [ProductController::class, 'update'])
        ->name('admin.product.update')
        ->middleware('can:edit-products');

    Route::delete('/destroy/{id}', [ProductController::class, 'destroy'])
        ->name('admin.product.destroy')
        ->middleware('can:delete-products');

    Route::get('/modal-view/{id}', [ProductController::class, 'modalView'])
        ->name('admin.product.modal-view')
        ->middleware('can:view-products');

    Route::get('/{id}/adjustment-log', [ProductController::class, 'getAdjustmentLog'])
        ->name('admin.product.adjustment-log')
        ->middleware('can:view-products');

    Route::post('/quick-create', [ProductController::class, 'quickCreate'])
        ->name('admin.product.quickCreate')
        ->middleware('can:create-products', 'plan.limit:products');

    Route::post('/bulk-delete', [ProductController::class, 'bulkDelete'])
        ->name('product.bulk-delete')
        ->middleware('can:delete-products');

    Route::post('/bulk-export', [ProductController::class, 'bulkExport'])
        ->name('product.bulk-export')
        ->middleware('can:view-products');

    Route::post('/bulk-stock-details', [ProductController::class, 'bulkStockDetails'])
        ->name('product.bulk-stock-details')
        ->middleware('can:view-products');

    Route::post('/bulk-update-stock', [ProductController::class, 'bulkUpdateStock'])
        ->name('product.bulk-update-stock')
        ->middleware('can:edit-products');

    Route::post('/adjust-stock', [ProductController::class, 'adjustStock'])
        ->name('admin.product.adjust-stock')
        ->middleware('can:edit-products');

    Route::get('/search', [ProductController::class, 'search'])
        ->name('admin.product.search')
        ->middleware('can:view-products');

    Route::get('/search-by-barcode', [ProductController::class, 'searchByBarcode'])
        ->name('admin.product.search-by-barcode')
        ->middleware('can:view-products');

    Route::get('/metrics', [ProductController::class, 'getProductMetrics'])
        ->name('admin.product.metrics')
        ->middleware('can:view-products');

    Route::get('/expiring-soon', [ProductController::class, 'getExpiringSoonProducts'])
        ->name('admin.product.expiring-soon')
        ->middleware('can:view-products');

    // Stock API Routes
    Route::get('/{id}/main-stock', [ProductStockController::class, 'mainStock'])
        ->name('admin.product.main-stock')
        ->middleware('can:view-products');

    Route::get('/{id}/warehouse-stock/{warehouseId}', [ProductStockController::class, 'warehouseStock'])
        ->name('admin.product.warehouse-stock')
        ->middleware('can:view-products');

    Route::get('/{id}/all-warehouse-stock', [ProductStockController::class, 'allWarehouseStock'])
        ->name('admin.product.all-warehouse-stock')
        ->middleware('can:view-products');

    Route::post('/stock-check', [ProductStockController::class, 'checkStock'])
        ->name('admin.product.stock-check')
        ->middleware('can:view-products');

    Route::post('/bulk-main-stock', [ProductStockController::class, 'bulkMainStock'])
        ->name('admin.product.bulk-main-stock')
        ->middleware('can:view-products');
});
