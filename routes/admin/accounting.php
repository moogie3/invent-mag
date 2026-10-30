<?php

use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\FinancialHealthController;
use App\Http\Controllers\Admin\JournalEntryController;
use App\Http\Controllers\Admin\OpeningBalanceController;

// Accounting Routes - Protected by plan feature
Route::prefix('accounting')->middleware(['plan.feature:accounting', 'can:view-accounting'])->group(function () {
    Route::get('/', fn () => redirect()->route('admin.accounting.ledger'));

    // Financial Health — the accounting landing view (red/green money health,
    // next-month outlook); reachable from the dashboard strip.
    Route::get('/financial-health', [FinancialHealthController::class, 'index'])
        ->name('admin.accounting.health');

    Route::get('/chart-of-accounts', [AccountingController::class, 'chartOfAccounts'])
        ->name('admin.accounting.chart')
        ->middleware('can:view-chart-of-accounts');

    Route::get('/journal', [AccountingController::class, 'journal'])
        ->name('admin.accounting.journal')
        ->middleware('can:view-journal');

    Route::get('/general-ledger', [AccountingController::class, 'generalLedger'])
        ->name('admin.accounting.ledger')
        ->middleware('can:view-general-ledger');

    Route::get('/trial-balance', [AccountingController::class, 'trialBalance'])
        ->name('admin.accounting.trial_balance')
        ->middleware('can:view-trial-balance');

    // Export routes
    Route::post('/journal/export', [AccountingController::class, 'exportJournal'])
        ->name('admin.accounting.journal.export')
        ->middleware('can:view-journal');

    Route::post('/general-ledger/export', [AccountingController::class, 'exportGeneralLedger'])
        ->name('admin.accounting.ledger.export')
        ->middleware('can:view-general-ledger');

    Route::post('/trial-balance/export', [AccountingController::class, 'exportTrialBalance'])
        ->name('admin.accounting.trial_balance.export')
        ->middleware('can:view-trial-balance');

    // Opening Balance Setup
    Route::get('/opening-balance', [OpeningBalanceController::class, 'index'])
        ->name('admin.accounting.opening-balance.index')
        ->middleware('can:manage-opening-balance');

    Route::post('/opening-balance/validate', [OpeningBalanceController::class, 'validate'])
        ->name('admin.accounting.opening-balance.validate')
        ->middleware('can:manage-opening-balance');

    Route::post('/opening-balance', [OpeningBalanceController::class, 'store'])
        ->name('admin.accounting.opening-balance.store')
        ->middleware('can:manage-opening-balance');

    Route::delete('/opening-balance', [OpeningBalanceController::class, 'destroy'])
        ->name('admin.accounting.opening-balance.destroy')
        ->middleware('can:manage-opening-balance');

    Route::get('/opening-balance/summary', [OpeningBalanceController::class, 'summary'])
        ->name('admin.accounting.opening-balance.summary')
        ->middleware('can:manage-opening-balance');

    // COA Management
    Route::get('/accounts', [AccountingController::class, 'accountsIndex'])
        ->name('admin.accounting.accounts.index')
        ->middleware('can:view-chart-of-accounts');

    Route::get('/accounts/create', [AccountingController::class, 'accountsCreate'])
        ->name('admin.accounting.accounts.create')
        ->middleware('can:edit-chart-of-accounts');

    Route::post('/accounts', [AccountingController::class, 'accountsStore'])
        ->name('admin.accounting.accounts.store')
        ->middleware('can:edit-chart-of-accounts');

    Route::get('/accounts/{account}/edit', [AccountingController::class, 'accountsEdit'])
        ->name('admin.accounting.accounts.edit')
        ->middleware('can:edit-chart-of-accounts');

    Route::put('/accounts/{account}', [AccountingController::class, 'accountsUpdate'])
        ->name('admin.accounting.accounts.update')
        ->middleware('can:edit-chart-of-accounts');

    Route::delete('/accounts/{account}', [AccountingController::class, 'accountsDestroy'])
        ->name('admin.accounting.accounts.destroy')
        ->middleware('can:delete-chart-of-accounts');

    Route::post('/accounts/export', [AccountingController::class, 'exportAll'])
        ->name('admin.accounting.accounts.export')
        ->middleware('can:view-chart-of-accounts');

    // Manual Journal Entry Routes
    Route::prefix('journal-entries')->group(function () {
        Route::get('/', [JournalEntryController::class, 'index'])->name('admin.accounting.journal-entries.index');
        Route::get('/create', [JournalEntryController::class, 'create'])->name('admin.accounting.journal-entries.create');
        Route::post('/', [JournalEntryController::class, 'store'])->name('admin.accounting.journal-entries.store');
        Route::get('/{journalEntry}', [JournalEntryController::class, 'show'])->name('admin.accounting.journal-entries.show');
        Route::get('/{journalEntry}/edit', [JournalEntryController::class, 'edit'])->name('admin.accounting.journal-entries.edit');
        Route::put('/{journalEntry}', [JournalEntryController::class, 'update'])->name('admin.accounting.journal-entries.update');
        Route::post('/{journalEntry}/post', [JournalEntryController::class, 'post'])->name('admin.accounting.journal-entries.post');
        Route::post('/{journalEntry}/void', [JournalEntryController::class, 'void'])->name('admin.accounting.journal-entries.void');
        Route::post('/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('admin.accounting.journal-entries.reverse');
        Route::delete('/{journalEntry}', [JournalEntryController::class, 'destroy'])
            ->name('admin.accounting.journal-entries.destroy')
            ->middleware('can:delete-manual-journal');
        Route::post('/{journalEntry}/duplicate', [JournalEntryController::class, 'duplicate'])->name('admin.accounting.journal-entries.duplicate');
        Route::get('/search/accounts', [JournalEntryController::class, 'searchAccounts'])->name('admin.accounting.journal-entries.search-accounts');
        Route::post('/validate', [JournalEntryController::class, 'validateEntry'])->name('admin.accounting.journal-entries.validate');
    });
});
