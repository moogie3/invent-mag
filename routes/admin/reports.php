<?php

use App\Http\Controllers\Admin\ReportController;

// Reports Routes
Route::prefix('reports')->group(function () {
    // Financial Reports (Income Statement, Balance Sheet, Aged) - Protected by plan.feature:financial_reports
    Route::middleware('plan.feature:financial_reports')->group(function () {
        Route::get('/income-statement', [ReportController::class, 'incomeStatement'])
            ->name('admin.reports.income-statement')
            ->middleware('can:view-financial-reports');

        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])
            ->name('admin.reports.balance-sheet')
            ->middleware('can:view-financial-reports');

        Route::get('/aged-receivables', [ReportController::class, 'agedReceivables'])
            ->name('admin.reports.aged-receivables')
            ->middleware('can:view-financial-reports');

        Route::get('/aged-payables', [ReportController::class, 'agedPayables'])
            ->name('admin.reports.aged-payables')
            ->middleware('can:view-financial-reports');

        Route::post('/income-statement/export', [ReportController::class, 'exportIncomeStatement'])
            ->name('admin.reports.income-statement.export')
            ->middleware('can:view-financial-reports');

        Route::post('/balance-sheet/export', [ReportController::class, 'exportBalanceSheet'])
            ->name('admin.reports.balance-sheet.export')
            ->middleware('can:view-financial-reports');

        Route::post('/aged-receivables/export', [ReportController::class, 'exportAgedReceivables'])
            ->name('admin.reports.aged-receivables.export')
            ->middleware('can:view-financial-reports');

        Route::post('/aged-payables/export', [ReportController::class, 'exportAgedPayables'])
            ->name('admin.reports.aged-payables.export')
            ->middleware('can:view-financial-reports');
    });

    // Cash Flow Statement - Protected by its own plan.feature:cash_flow (Professional+)
    Route::middleware('plan.feature:cash_flow')->group(function () {
        Route::get('/cash-flow', [ReportController::class, 'cashFlow'])
            ->name('admin.reports.cash-flow')
            ->middleware('can:view-financial-reports');

        Route::post('/cash-flow/export', [ReportController::class, 'exportCashFlow'])
            ->name('admin.reports.cash-flow.export')
            ->middleware('can:view-financial-reports');
    });

    // Basic reports - available on all plans
    Route::get('/adjustment-log', [ReportController::class, 'adjustmentLog'])
        ->name('admin.reports.adjustment-log')
        ->middleware('can:view-reports');

    Route::get('/recent-transactions', [ReportController::class, 'recentTransactions'])
        ->name('admin.reports.recent-transactions')
        ->middleware('can:view-reports');

    // Basic report export routes - available on all plans
    Route::post('/adjustment-log/export', [ReportController::class, 'exportAdjustmentLog'])
        ->name('admin.reports.adjustment-log.export')
        ->middleware('can:view-reports');

    Route::post('/recent-transactions/export', [ReportController::class, 'exportRecentTransactions'])
        ->name('admin.reports.recent-transactions.export')
        ->middleware('can:view-reports');

    Route::post('/recent-transactions/bulk-export', [ReportController::class, 'bulkExport'])
        ->name('admin.reports.recent-transactions.bulk-export')
        ->middleware('can:view-reports');

    Route::post('/{id}/mark-paid', [ReportController::class, 'markAsPaid'])
        ->name('admin.transactions.mark-paid')
        ->middleware('can:edit-transactions');

    Route::post('/bulk-mark-paid', [ReportController::class, 'bulkMarkAsPaid'])
        ->name('admin.transactions.bulk-mark-paid')
        ->middleware('can:edit-transactions');
});
