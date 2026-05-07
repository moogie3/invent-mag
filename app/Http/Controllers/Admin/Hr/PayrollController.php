<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendancePeriod;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    protected $service;

    public function __construct(PayrollService $service)
    {
        $this->middleware('permission:view-hr', ['only' => ['index', 'show']]);
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

    public function run(AttendancePeriod $period)
    {
        try {
            $run = $this->service->runPayroll($period);
            return redirect()->route('admin.hr.payroll.show', $run)->with('success', __('messages.record_created'));
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
            return back()->with('success', __('messages.record_updated'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markPaid(PayrollRun $run)
    {
        try {
            $this->service->markPaid($run);
            return back()->with('success', __('messages.record_updated'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}