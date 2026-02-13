<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AgingReportService
{
    /**
     * Generate Aged Receivables report.
     *
     * @return array
     */
    public function generateAgedReceivables(): array
    {
        $now = Carbon::now();
        
        $unpaidSales = Sales::with('customer')
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->whereNotNull('due_date')
            ->get()
            ->map(function ($sale) use ($now) {
                $daysOverdue = $now->diffInDays($sale->due_date, false);
                $sale->days_overdue = $daysOverdue < 0 ? round(abs($daysOverdue)) : 0;
                return $sale;
            });

        return $this->categorizeAging($unpaidSales);
    }

    /**
     * Generate Aged Payables report.
     *
     * @return array
     */
    public function generateAgedPayables(): array
    {
        $now = Carbon::now();
        
        $unpaidPurchases = Purchase::with('supplier')
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->whereNotNull('due_date')
            ->get()
            ->map(function ($purchase) use ($now) {
                $daysOverdue = $now->diffInDays($purchase->due_date, false);
                $purchase->days_overdue = $daysOverdue < 0 ? round(abs($daysOverdue)) : 0;
                return $purchase;
            });

        return $this->categorizeAging($unpaidPurchases);
    }

    /**
     * Categorize invoices into aging buckets.
     *
     * @param Collection $invoices
     * @return array
     */
    protected function categorizeAging(Collection $invoices): array
    {
        return [
            'current' => $invoices->where('days_overdue', 0),
            '1-30' => $invoices->where('days_overdue', '>', 0)->where('days_overdue', '<=', 30),
            '31-60' => $invoices->where('days_overdue', '>', 30)->where('days_overdue', '<=', 60),
            '61-90' => $invoices->where('days_overdue', '>', 60)->where('days_overdue', '<=', 90),
            '90+' => $invoices->where('days_overdue', '>', 90),
        ];
    }

    /**
     * Get aging bucket labels.
     *
     * @return array
     */
    public function getAgingBucketLabels(): array
    {
        return [
            'current' => 'Current',
            '1-30' => '1-30 Days Overdue',
            '31-60' => '31-60 Days Overdue',
            '61-90' => '61-90 Days Overdue',
            '90+' => 'Over 90 Days Overdue',
        ];
    }
}
