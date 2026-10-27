<?php

use App\Http\Controllers\Admin\POSController;
use App\Http\Controllers\Admin\StockOpnameController;
use App\Http\Controllers\Admin\StockTransferController;
use App\Http\Controllers\Admin\WarehouseController;

// Stock Opname Routes
Route::prefix('stock-opname')->middleware('plan.feature:stock_opname')->group(function () {
    Route::get('/', [StockOpnameController::class, 'index'])->name('admin.stock-opname.index')->middleware('can:view-stock-opname');
    Route::post('/', [StockOpnameController::class, 'store'])->name('admin.stock-opname.store')->middleware('can:manage-stock-opname');
    Route::get('/create', [StockOpnameController::class, 'create'])->name('admin.stock-opname.create')->middleware('can:manage-stock-opname');
    Route::get('/{stockOpname}', [StockOpnameController::class, 'show'])->name('admin.stock-opname.show')->middleware('can:view-stock-opname');
    Route::post('/{stockOpname}/counts', [StockOpnameController::class, 'updateCounts'])->name('admin.stock-opname.update-counts')->middleware('can:manage-stock-opname');
    Route::post('/{stockOpname}/complete', [StockOpnameController::class, 'complete'])->name('admin.stock-opname.complete')->middleware('can:manage-stock-opname');
    Route::post('/{stockOpname}/cancel', [StockOpnameController::class, 'cancel'])->name('admin.stock-opname.cancel')->middleware('can:manage-stock-opname');
});

// Warehouse Routes
Route::prefix('warehouses')->group(function () {
    Route::get('/', [WarehouseController::class, 'index'])
        ->name('admin.warehouse')
        ->middleware('can:view-warehouses');

    Route::post('/store', [WarehouseController::class, 'store'])
        ->name('admin.warehouse.store')
        ->middleware(['can:create-warehouses', 'plan.limit:warehouses']);

    Route::get('/edit/{id}', [WarehouseController::class, 'edit'])
        ->name('admin.warehouse.edit')
        ->middleware('can:edit-warehouses');

    Route::put('/update/{id}', [WarehouseController::class, 'update'])
        ->name('admin.warehouse.update')
        ->middleware('can:edit-warehouses');

    Route::post('/destroy/{id}', [WarehouseController::class, 'destroy'])
        ->name('admin.warehouse.destroy')
        ->middleware('can:delete-warehouses');

    Route::post('/{id}/set-main', [WarehouseController::class, 'setMain'])
        ->name('admin.warehouse.set-main')
        ->middleware('can:edit-warehouses');

    Route::post('/{id}/unset-main', [WarehouseController::class, 'unsetMain'])
        ->name('admin.warehouse.unset-main')
        ->middleware('can:edit-warehouses');

    Route::post('/export', [WarehouseController::class, 'exportAll'])
        ->name('admin.warehouse.export')
        ->middleware('can:view-warehouses');
});

// POS Routes - Protected by plan feature
Route::prefix('pos')->middleware('plan.feature:pos')->group(function () {
    Route::get('/', [POSController::class, 'index'])
        ->name('admin.pos')
        ->middleware('can:access-pos');

    Route::get('/products', [POSController::class, 'searchProducts'])
        ->name('admin.pos.products')
        ->middleware('can:access-pos');

    Route::get('/create', [POSController::class, 'create'])
        ->name('admin.pos.create')
        ->middleware('can:access-pos');

    Route::post('/store', [POSController::class, 'store'])
        ->name('admin.pos.store')
        ->middleware('can:access-pos');

    Route::get('/edit/{id}', [POSController::class, 'edit'])
        ->name('admin.pos.edit')
        ->middleware('can:access-pos');

    Route::put('/update/{id}', [POSController::class, 'update'])
        ->name('admin.pos.update')
        ->middleware('can:access-pos');

    Route::delete('/destroy/{id}', [POSController::class, 'destroy'])
        ->name('admin.pos.destroy')
        ->middleware('can:delete-pos-transactions');

    Route::get('/receipt/{id}', [POSController::class, 'receipt'])
        ->name('admin.pos.receipt')
        ->middleware('can:access-pos');

    Route::get('/print-receipt/{id}', [POSController::class, 'printReceipt'])
        ->name('admin.pos.print-receipt')
        ->middleware('can:access-pos');

    Route::post('/validate-stock', [POSController::class, 'validateStock'])
        ->name('admin.pos.validate-stock')
        ->middleware('can:access-pos');
});

// Stock Transfer Routes (under reports prefix in original)
Route::prefix('reports')->group(function () {
    Route::get('/stock-transfer', [StockTransferController::class, 'index'])
        ->name('admin.reports.stock-transfer.page')
        ->middleware('can:adjust-stock');

    Route::post('/stock-transfer', [StockTransferController::class, 'store'])
        ->name('admin.reports.stock-transfer')
        ->middleware('can:adjust-stock');

    Route::get('/stock-transfer/products/{warehouseId}', [StockTransferController::class, 'getProducts'])
        ->name('admin.reports.stock-transfer.products')
        ->middleware('can:adjust-stock');
});
