<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Services\ActivityLogService;
use App\Services\EmployeeService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    protected $service;

    public function __construct(EmployeeService $service)
    {
        $this->middleware('permission:view-hr', ['only' => ['index', 'show', 'export']]);
        $this->middleware('permission:manage-employees', ['only' => ['create', 'store', 'edit', 'update', 'destroy']]);
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getIndexData();

        return view('admin.hr.employee.index', $data);
    }

    public function store(Request $request)
    {
        try {
            $this->service->create($request->all());

            return redirect()->route('admin.hr.employee.index')->with('success', __('messages.record_created'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, Employee $employee)
    {
        try {
            $this->service->update($employee, $request->all());

            return redirect()->route('admin.hr.employee.index')->with('success', __('messages.record_updated'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(Employee $employee)
    {
        try {
            $this->service->delete($employee);

            return redirect()->route('admin.hr.employee.index')->with('success', __('messages.record_deleted'));
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
                ActivityLog::MODULE_EMPLOYEE,
                'Exported Employees'
            );

            return $this->service->exportEmployees($request->ids, $request->export_option);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting employees.',
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
