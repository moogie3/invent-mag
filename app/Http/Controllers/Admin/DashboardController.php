<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $inCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })->count();

        $inCountUnpaid = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })->where('status', 'Unpaid')->count();

        $outCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })->count();

        $outCountUnpaid = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })->where('status', 'Unpaid')->count();

        $countcustrevenue = Sales::sum('total');
        $countliability = Purchase::where('status', 'Unpaid')->sum('total');

        // Fetch daily invoice count and total amount
        $dailyInvoices = Purchase::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(id) as invoice_count'), DB::raw('SUM(total) as total_amount'))
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Fetch daily invoices and their total amount
        $chartData = Purchase::selectRaw('DATE(created_at) as date, COUNT(id) as invoice_count, SUM(total) as total_amount')
            ->groupBy('date')
            ->orderBy('date')
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


        return view('admin.dashboard', compact('inCount', 'outCount', 'inCountUnpaid', 'outCountUnpaid', 'countcustrevenue', 'countliability', 'chartData'));
    }
}