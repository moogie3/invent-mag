<?php

use App\Http\Controllers\Admin\BankReconciliationController;

// Bank Reconciliation Routes - Protected by plan feature
Route::prefix('bank-reconciliation')->middleware('plan.feature:bank_reconciliation')->group(function () {
    Route::get('/', [BankReconciliationController::class, 'index'])
        ->name('admin.bank-reconciliation.index')
        ->middleware('can:view-accounting');

    Route::get('/new', [BankReconciliationController::class, 'create'])
        ->name('admin.bank-reconciliation.create')
        ->middleware('can:view-accounting');

    Route::post('/', [BankReconciliationController::class, 'store'])
        ->name('admin.bank-reconciliation.store')
        ->middleware('can:view-accounting');

    Route::get('/{reconciliation}', [BankReconciliationController::class, 'show'])
        ->name('admin.bank-reconciliation.show')
        ->middleware('can:view-accounting');

    Route::post('/{reconciliation}/match/{item}', [BankReconciliationController::class, 'matchItem'])
        ->name('admin.bank-reconciliation.match')
        ->middleware('can:view-accounting');

    Route::post('/{reconciliation}/unmatch/{item}', [BankReconciliationController::class, 'unmatchItem'])
        ->name('admin.bank-reconciliation.unmatch')
        ->middleware('can:view-accounting');

    Route::post('/{reconciliation}/complete', [BankReconciliationController::class, 'complete'])
        ->name('admin.bank-reconciliation.complete')
        ->middleware('can:view-accounting');

    Route::delete('/{reconciliation}', [BankReconciliationController::class, 'destroy'])
        ->name('admin.bank-reconciliation.destroy')
        ->middleware('can:view-accounting');
});
