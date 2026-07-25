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

    // Individual Faktur Pajak actions
    Route::get('/{fakturPajak}', [EFakturController::class, 'show'])->name('show');
    Route::get('/{fakturPajak}/print', [EFakturController::class, 'print'])->name('print');
    Route::post('/{fakturPajak}/cancel', [EFakturController::class, 'cancel'])->name('cancel');
});
