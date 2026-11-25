<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FinancialHealthService;

class FinancialHealthController extends Controller
{
    /**
     * Accounting › Financial Health — the full red/green money view:
     * indicators, what stands out, the next-month outlook chain with its
     * best/worst range, six-month trends and the attention list.
     *
     * Route group enforces view-accounting permission.
     */
    public function index(FinancialHealthService $financialHealth)
    {
        // The period filter: a preset ("last 6 months") or a custom month
        // range ("2026-05".."2026-08"). Both reduce to one [from..to] window
        // inside resolvePeriod(), which validates the preset name, rejects
        // future/in-progress months and caps the span — so the controller
        // can pass the raw query values through untouched.
        return view('admin.accounting.financial-health', [
            ...$financialHealth->getOverview(request('preset'), request('from'), request('to')),
            'comparison' => $financialHealth->getComparison(request('preset'), request('from'), request('to')),
        ]);
    }
}
