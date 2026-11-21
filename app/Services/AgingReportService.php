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
     */
    public function generateAgedReceivables(): array
    {
        $now = Carbon::now();

        // Eager load everything `balance` recomputes from, or every row in
        // the report would fire its own item, return and payment queries.
        $unpaidSales = Sales::with(['customer', 'salesItems', 'salesReturns.items', 'payments'])
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->whereNotNull('due_date')
            ->get()
            ->filter(fn (Sales $sale) => $sale->balance > 0)
            ->map(function ($sale) use ($now) {
                $sale->days_overdue = $this->daysOverdue($now, $sale->due_date);

                return $sale;
            });

        return $this->categorizeAging($unpaidSales);
    }

    /**
     * Generate Aged Payables report.
     */
    public function generateAgedPayables(): array
    {
        $now = Carbon::now();

        $unpaidPurchases = Purchase::with(['supplier', 'items', 'purchaseReturns.items', 'payments'])
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->whereNotNull('due_date')
            ->get()
            ->filter(fn (Purchase $purchase) => $purchase->balance > 0)
            ->map(function ($purchase) use ($now) {
                $purchase->days_overdue = $this->daysOverdue($now, $purchase->due_date);

                return $purchase;
            });

        return $this->categorizeAging($unpaidPurchases);
    }

    /**
     * Fold the buckets into the totals the dashboard and the financial
     * health page show. They used to run their own SQL over the gross
     * `total` column, so the same debt read a different amount depending
     * on which screen it came from; one table, one number.
     */
    public function summarize(array $buckets, bool $withOverdueCount = false): array
    {
        $sum = fn (string $bucket): float => (float) $buckets[$bucket]->sum('balance');

        $summary = [
            'current' => $sum('current'),
            '1-30' => $sum('1-30'),
            '31-60' => $sum('31-60'),
            '61-90' => $sum('61-90'),
            '90+' => $sum('90+'),
            'total_overdue' => $sum('1-30') + $sum('31-60') + $sum('61-90') + $sum('90+'),
        ];

        if ($withOverdueCount) {
            $summary['count_30plus'] = collect($buckets)
                ->flatten()
                ->filter(fn ($document) => (int) $document->days_overdue >= 30)
                ->count();
        }

        return $summary;
    }

    /**
     * Whole calendar days past due. due_date carries a time of day, so the
     * fractional difference would call an invoice due yesterday "current"
     * until that same clock time came round again, which is not how a due
     * date is read anywhere else.
     */
    private function daysOverdue(Carbon $now, $dueDate): int
    {
        if (! $dueDate) {
            return 0;
        }

        $today = $now->copy()->startOfDay();
        $due = Carbon::parse($dueDate)->startOfDay();

        return $due->lt($today) ? (int) abs($today->diffInDays($due)) : 0;
    }

    /**
     * Categorize invoices into aging buckets.
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
