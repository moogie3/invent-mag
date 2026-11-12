<?php

use App\Http\Controllers\Admin\EFakturController;

// e-Faktur Routes — PPN compliance (Phase 1: PDF + CSV export)
// Gated behind the 'ppn' plan feature — available to Professional & Enterprise only
Route::prefix('efaktur')->name('admin.efaktur.')->middleware(['plan.feature:ppn'])->group(function () {
    // List Faktur Pajak for a tax period (masa/tahun filter via query string)
    Route::get('/', [EFakturController::class, 'index'])->name('index');

    // Settings: NPWP, PKP status, PPN rate, NSFP import
    Route::get('/settings', [EFakturController::class, 'settings'])->name('settings');
    Route::post('/settings', [EFakturController::class, 'saveSettings'])->name('settings.save');
    Route::post('/nsfp/import', [EFakturController::class, 'importNsfp'])->name('nsfp.import');

    // Export CSV for DJP e-Faktur desktop application
    Route::get('/export/csv', [EFakturController::class, 'exportCsv'])->name('export.csv');

    // Generate Faktur Pajak from a sale
    Route::post('/generate/{sale}', [EFakturController::class, 'generate'])->name('generate');

    // Bulk submission: one PJAP call covers every selected draft faktur.
    // Enterprise only, same gate as the single submit below (efaktur_api).
    Route::post('/batch-submit', [EFakturController::class, 'submitBatch'])
        ->middleware('plan.feature:efaktur_api')
        ->name('batch.submit');

    // Individual Faktur Pajak actions
    Route::get('/{fakturPajak}', [EFakturController::class, 'show'])->name('show');
    Route::get('/{fakturPajak}/print', [EFakturController::class, 'print'])->name('print');
    Route::post('/{fakturPajak}/cancel', [EFakturController::class, 'cancel'])->name('cancel');

    // Phase 2 — PJAP API direct submission (DJP via Klikpajak).
    // One tier above 'ppn': Enterprise only (feature slug efaktur_api).
    Route::post('/{fakturPajak}/submit', [EFakturController::class, 'submitToDjp'])
        ->middleware('plan.feature:efaktur_api')
        ->name('submit');

    // Void a faktur DJP already accepted (Faktur Batal). Enterprise only.
    // Distinct from /cancel above, which is the Phase 1 local draft cancel.
    Route::post('/{fakturPajak}/cancel-djp', [EFakturController::class, 'cancelAtDjp'])
        ->middleware('plan.feature:efaktur_api')
        ->name('cancel.djp');
});
