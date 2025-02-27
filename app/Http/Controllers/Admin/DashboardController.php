<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\DailySales;
use App\Models\Purchase;
use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        //INVOICE STATUS
        $outCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })->count();

        $outCountUnpaid = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })->where('status', 'Unpaid')->count();


        $inCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })->count();

        $inCountUnpaid = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })->where('status', 'Unpaid')->count();

        //CUSTOMER STATUS
        $totalRevenue = Sales::whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->sum('total');
        $countRevenue = Sales::all()->where('status','Unpaid')->sum('total');
        $paidDebtMonthly = Sales::whereMonth('created_at',now()->month)
        ->whereYear('created_at',now()->year)
        ->where('status', 'Paid')
        ->sum('total');

        //LIABILITIES
        $countliability = Purchase::where('status', 'Unpaid')->sum('total');
        $totalliability = Purchase::all()->sum('total');
        $liabilitypaymentMonthly = Purchase::whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->where('status','Paid')
        ->sum('total');


        // CHART
        $chartData = Purchase::selectRaw('DATE(created_at) as date, COUNT(id) as invoice_count, SUM(total) as total_amount')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date','asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'invoice_count' => $item->invoice_count,
                    'total_amount' => CurrencyHelper::format($item->total_amount), // Format currency
                    'total_amount_raw' => $item->total_amount // Raw value for JavaScript
                ];
            })
            ->toArray();

        $chartDataEarning = DailySales::selectRaw('DATE(created_at) as date, SUM(total) as total_amount')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date','asc')
            ->get()
            ->map(function ($dss) {
                return [
                    'date' => $dss->date,
                    'total_amount' => CurrencyHelper::format($dss->total_amount), // Format currency
                    'total_amount_raw' => $dss->total_amount // Raw value for JavaScript
                ];
            })
            ->toArray();

        //DAILY SALES
        $totalDailySales = DailySales::all()->sum('total');

        return view('admin.dashboard', compact('chartDataEarning','totalDailySales','totalliability','paidDebtMonthly','countRevenue','liabilitypaymentMonthly','inCount', 'outCount', 'inCountUnpaid', 'outCountUnpaid', 'totalRevenue', 'countliability', 'chartData'));
    }
}