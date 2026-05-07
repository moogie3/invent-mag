<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    protected $service;

    public function __construct(PayrollService $service)
    {
        $this->middleware('permission:view-hr', ['only' => ['download', 'generate']]);
        $this->service = $service;
    }

    public function generate(Request $request, PayrollItem $item)
    {
        try {
            $pdfPath = $this->service->generatePayslip($item);
            return back()->with('success', __('messages.record_created'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function download(PayrollItem $item)
    {
        if (! $item->pdf_path || ! $item->pdf_generated_at) {
            return back()->with('error', 'Payslip not generated yet.');
        }

        $pdf = Pdf::loadView('admin.hr.payslip.print', [
            'item' => $item,
            'employee' => $item->employee,
            'period' => $item->payrollRun->period,
        ]);

        return $pdf->download("payslip-{$item->employee->employee_code}-{$item->payrollRun->period->year}-{$item->payrollRun->period->month}.pdf");
    }
}