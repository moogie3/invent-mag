<?php

use App\Http\Controllers\Admin\Hr\EmployeeController;
use App\Http\Controllers\Admin\Hr\AttendanceController;
use App\Http\Controllers\Admin\Hr\PayrollController;
use App\Http\Controllers\Admin\Hr\PayslipController;

// HR Routes - requires hr plan feature
Route::middleware('plan.feature:hr')->prefix('hr')->name('admin.hr.')->group(function () {
    Route::resource('employee', EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('employee/export', [EmployeeController::class, 'export'])->name('employee.export');
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance/{period}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('attendance/period', [AttendanceController::class, 'openPeriod'])->name('attendance.open-period');
    Route::delete('attendance/{period}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
    Route::post('attendance/{period}/close', [AttendanceController::class, 'closePeriod'])->name('attendance.close-period');
    Route::post('attendance/{period}/export', [AttendanceController::class, 'exportPeriod'])->name('attendance.export-period');
    Route::post('attendance/record', [AttendanceController::class, 'upsert'])->name('attendance.upsert');
    Route::post('attendance/{period}/bulk-fill', [AttendanceController::class, 'bulkFill'])->name('attendance.bulk-fill');
    Route::post('attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');
    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll/{run}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::delete('payroll/{run}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
    Route::post('payroll/run/{period}', [PayrollController::class, 'run'])->name('payroll.run');
    Route::put('payroll/item/{item}', [PayrollController::class, 'updateItem'])->name('payroll.item.update');
    Route::post('payroll/{run}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
    Route::post('payroll/{run}/pay', [PayrollController::class, 'markPaid'])->name('payroll.pay');
    Route::post('payroll/{run}/export', [PayrollController::class, 'exportRun'])->name('payroll.export-run');
    Route::post('payroll/export', [PayrollController::class, 'export'])->name('payroll.export');
    Route::post('payslip/{item}/generate', [PayslipController::class, 'generate'])->name('payslip.generate');
    Route::get('payslip/{item}/download', [PayslipController::class, 'download'])->name('payslip.download');
});
