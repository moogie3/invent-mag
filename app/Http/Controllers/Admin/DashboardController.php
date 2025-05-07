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
        })->count(); //counting invoice in supplier table where location is out

        $outCountUnpaid = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })->where('status', 'Unpaid')->count(); //counting invoice in supplier table where location is out and status is unpaid


        $inCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })->count(); //counting invoice in supplier table where location is in

        $inCountUnpaid = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })->where('status', 'Unpaid')->count(); //counting invoice in supplier table where location is out and status is unpaid

        //CUSTOMER STATUS
        $totalRevenue = Sales::whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->sum('total'); //counting total revenue in sales table where month is current month and year is current year
        $countRevenue = Sales::all()->where('status','Unpaid')->sum('total'); //summing total revenue in sales table where status is unpaid
        $paidDebtMonthly = Sales::whereMonth('updated_at',now()->month)
        ->whereYear('updated_at',now()->year)
        ->where('status', 'Paid')
        ->sum('total'); //summing total receivable in sales table where month is current month and year is current year and status is paid

        //LIABILITIES
        $countliability = Purchase::where('status', 'Unpaid')->sum('total'); //summing total liability (invoice purchase order) where status is unpaid
        $totalliability = Purchase::all()->sum('total'); //summing total liability
        $liabilitypaymentMonthly = Purchase::whereMonth('updated_at', now()->month)
        ->whereYear('updated_at', now()->year)
        ->where('status','Paid')
        ->sum('total'); //summing total liability (invoice purchase order) where status is paid in the current month and year


        // CHART
        $chartData = Purchase::selectRaw('DATE(created_at) as date, COUNT(id) as invoice_count, SUM(total) as total_amount')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date','asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('d-M-y'),
                    'invoice_count' => $item->invoice_count,
                    'total_amount' => CurrencyHelper::format($item->total_amount), // format currency
                    'total_amount_raw' => $item->total_amount // raw value for JavaScript
                ];
            })
            ->toArray();

        $chartDataEarning = DailySales::selectRaw('DATE(created_at) as date, SUM(total) as total_amount')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date','asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' =>  Carbon::parse($item->date)->format('d-M-y'),
                    'total_amount' => CurrencyHelper::format($item->total_amount), // format currency
                    'total_amount_raw' => $item->total_amount // raw value for JavaScript
                ];
            })
            ->toArray();

        //DAILY SALES
        $totalDailySales = DailySales::all()->sum('total');

        return view('admin.dashboard', compact('chartDataEarning','totalDailySales','totalliability','paidDebtMonthly','countRevenue','liabilitypaymentMonthly','inCount', 'outCount', 'inCountUnpaid', 'outCountUnpaid', 'totalRevenue', 'countliability', 'chartData'));
    }
}
