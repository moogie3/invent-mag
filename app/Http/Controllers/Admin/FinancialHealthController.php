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
     * Route group already enforces plan.feature:accounting + view-accounting.
     */
    public function index(FinancialHealthService $financialHealth)
    {
        return view('admin.accounting.financial-health', $financialHealth->getOverview());
    }
}
