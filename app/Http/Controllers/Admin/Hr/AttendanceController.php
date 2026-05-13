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
        $this->middleware('permission:view-hr', ['only' => ['index', 'show', 'export']]);
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

    public function exportPeriod(Request $request, AttendancePeriod $period)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            return $this->service->exportPeriodRecords($period, $request->export_option);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting attendance records.',
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function openPeriod(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'working_days' => 'required|integer|min:1|max:31',
        ]);

        try {
            $this->service->openPeriod($validated['year'], $validated['month'], $validated['working_days']);
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

    public function destroy(AttendancePeriod $period)
    {
        try {
            $this->service->deletePeriod($period);
            return redirect()->route('admin.hr.attendance.index')->with('success', __('messages.record_deleted'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function upsert(Request $request)
    {
        try {
            $record = $this->service->upsertRecord($request->all());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('messages.record_updated'),
                    'record' => $record
                ]);
            }
            return back()->with('success', __('messages.record_updated'));
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
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
            return $this->service->exportPeriods($request->ids, $request->export_option);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting attendance periods.',
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function bulkFill(Request $request, AttendancePeriod $period)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'integer',
            'status' => 'required|string|in:present,absent,sick,leave,holiday,half_day',
            'overtime_hours' => 'nullable|numeric|min:0',
            'skip_days' => 'nullable|array',
            'skip_days.*' => 'integer|min:1|max:7',
        ]);

        try {
            $count = $this->service->bulkFill(
                $period,
                $validated['employee_ids'],
                $validated['status'],
                $validated['overtime_hours'] ?? null,
                $validated['skip_days'] ?? []
            );
            return back()->with('success', "Filled {$count} records.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}