<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendancePeriod;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected $service;

    public function __construct(AttendanceService $service)
    {
        $this->middleware('permission:view-hr', ['only' => ['index', 'show']]);
        $this->middleware('permission:manage-attendance', ['only' => ['openPeriod', 'closePeriod', 'bulkFill', 'upsert']]);
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getIndexData();
        return view('admin.hr.attendance.index', $data);
    }

    public function show(AttendancePeriod $period)
    {
        $data = $this->service->getPeriodShowData($period);
        return view('admin.hr.attendance.show', $data);
    }

    public function openPeriod(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $this->service->openPeriod($validated['year'], $validated['month']);
            return back()->with('success', __('messages.record_created'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function closePeriod(AttendancePeriod $period)
    {
        try {
            $this->service->closePeriod($period);
            return back()->with('success', __('messages.record_updated'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function upsert(Request $request)
    {
        try {
            $this->service->upsertRecord($request->all());
            return back()->with('success', __('messages.record_updated'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function bulkFill(Request $request, AttendancePeriod $period)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'integer',
            'status' => 'required|string|in:present,absent,sick,leave,holiday,half_day',
            'overtime_hours' => 'nullable|numeric|min:0',
        ]);

        try {
            $count = $this->service->bulkFill(
                $period,
                $validated['employee_ids'],
                $validated['status'],
                $validated['overtime_hours'] ?? null
            );
            return back()->with('success', "Filled {$count} records.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}