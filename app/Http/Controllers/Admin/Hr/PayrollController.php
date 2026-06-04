<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AttendancePeriod;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\ActivityLogService;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    protected $service;

    public function __construct(PayrollService $service)
    {
        $this->middleware('permission:view-hr', ['only' => ['index', 'show', 'export']]);
        $this->middleware('permission:manage-payroll', ['only' => ['run', 'updateItem', 'markPaid']]);
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getIndexData();
        return view('admin.hr.payroll.index', $data);
    }

    public function show(PayrollRun $run)
    {
        $data = $this->service->getRunShowData($run);
        return view('admin.hr.payroll.show', $data);
    }

    public function exportRun(Request $request, PayrollRun $run)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $runLabel = $run->period?->name ?? "Run #{$run->id}";
            app(ActivityLogService::class)->logExport(
                ActivityLog::MODULE_PAYROLL,
                "Exported Payroll Items for {$runLabel}"
            );
            return $this->service->exportRunItems($run, $request->export_option);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting payroll items.',
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function run(AttendancePeriod $period)
    {
        try {
            $run = $this->service->runPayroll($period);
            return redirect()->route('admin.hr.payroll.show', $run)->with('success', __('messages.record_created'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(PayrollRun $run)
    {
        try {
            $this->service->deletePayrollRun($run);
            return redirect()->route('admin.hr.payroll.index')->with('success', __('messages.record_deleted'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateItem(Request $request, PayrollItem $item)
    {
        $validated = $request->validate([
            'allowances' => 'nullable|array',
            'allowances.*.label' => 'required|string',
            'allowances.*.amount' => 'required|numeric',
            'deductions' => 'nullable|array',
            'deductions.*.label' => 'required|string',
            'deductions.*.amount' => 'required|numeric',
        ]);

        try {
            $this->service->updateItem($item, $validated);
            app(ActivityLogService::class)->logUpdate(
                ActivityLog::MODULE_PAYROLL,
                "Updated payroll item #{$item->id} for employee #{$item->employee_id}",
                $item
            );
            return back()->with('success', __('messages.record_updated'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(PayrollRun $run)
    {
        try {
            $this->service->approve($run);
            return back()->with('success', __('messages.record_updated'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            app(ActivityLogService::class)->logExport(
                ActivityLog::MODULE_PAYROLL,
                'Exported Payroll Runs'
            );
            return $this->service->exportRuns($request->ids, $request->export_option);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting payroll runs.',
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function markPaid(Request $request, PayrollRun $run)
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:accounts,id',
        ]);

        try {
            $this->service->markPaid($run, $validated['account_id']);
            return back()->with('success', __('messages.record_updated'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}