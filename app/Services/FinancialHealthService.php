<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Helpers\CurrencyHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Financial Health — one composed view of the tenant's money.
 *
 * Doctrine:
 *  - Balance-sheet indicators (cash, receivables, payables, payroll status,
 *    inventory) come from POSTED general-ledger data only.
 *  - Flow indicators (revenue, purchases) use the sales/po subledger on
 *    order_date, matching every existing chart and report, so nothing on the
 *    dashboard contradicts anything else.
 *  - "vs average" comparisons use COMPLETE months only; the in-progress
 *    month never drags a comparison down on day 2.
 *  - All thresholds live in config/financial-health.php.
 *
 * Every query is a bounded aggregate (tenant-scoped, date-windowed) — no
 * model loading, no N+1: 11 small queries + one forecast call.
 */
class FinancialHealthService
{
    /** @var array<string, mixed> per-request memo (series is reused by summary + page) */
    protected array $memo = [];

    public function __construct(
        protected SalesForecastService $forecastService
    ) {}

    // ────────────────────────────────────────────────────────────────
    // Public API
    // ────────────────────────────────────────────────────────────────

    /**
     * Dashboard strip payload: indicators + the sharpest narrative lines.
     * Deliberately skips the forecast chain (no extra queries beyond series).
     */
    public function getSummary(): array
    {
        return $this->remember('summary', function () {
            $series = $this->series();
            $indicators = $this->indicators($series);
            $attention = $this->attentionList($series);

            return [
                'overall' => $this->overallStatus($indicators),
                'indicators' => $indicators,
                'narrative' => array_slice($this->narrative($indicators, $series, null), 0, 3),
                'attention_count' => count($attention),
                'months' => $series['labels'],
            ];
        });
    }

    /**
     * Full page payload: indicators, narrative, next-month outlook chain,
     * six-month trend series and the attention list.
     */
    public function getOverview(): array
    {
        return $this->remember('overview', function () {
            $series = $this->series();
            $indicators = $this->indicators($series);
            $outlook = $this->outlook($series);

            return [
                'overall' => $this->overallStatus($indicators),
                'indicators' => $indicators,
                'narrative' => $this->narrative($indicators, $series, $outlook),
                'outlook' => $outlook,
                'trends' => $series,
                'attention' => $this->attentionList($series),
            ];
        });
    }

    // ────────────────────────────────────────────────────────────────
    // Series — the bounded aggregates everything is computed from
    // ────────────────────────────────────────────────────────────────

    protected function series(): array
    {
        return $this->remember('series', function () {
            $tenantId = app('currentTenant')->id;
            $driver = DB::connection()->getDriverName();
            $months = (int) config('financial-health.months', 6);
            $start = Carbon::now()->startOfMonth()->subMonths($months - 1);

            $labels = [];
            for ($i = 0; $i < $months; $i++) {
                $labels[] = $start->copy()->addMonths($i)->format('Y-m');
            }

            // Driver-aware expressions (tests run on sqlite, production on MySQL)
            $monthExpr = $driver === 'sqlite'
                ? "strftime('%Y-%m', je.date)"
                : "DATE_FORMAT(je.date, '%Y-%m')";
            $prefix = $driver === 'sqlite'
                ? 'CAST(substr(a.code, 1, 4) AS INTEGER)'
                : 'CAST(SUBSTRING(a.code, 1, 4) AS SIGNED)';
            $payrollCodes = implode(',', array_map('intval', config('financial-health.payroll_gl_codes', [6300])));

            // 1) GL monthly aggregates — posted entries only
            $glRows = DB::table('transactions as t')
                ->join('journal_entries as je', 'je.id', '=', 't.journal_entry_id')
                ->join('accounts as a', 'a.id', '=', 't.account_id')
                ->where('je.tenant_id', $tenantId)
                ->where('je.status', JournalEntry::STATUS_POSTED)
                ->where('je.date', '>=', $start->toDateString())
                ->groupBy('month')
                ->selectRaw("
                    {$monthExpr} AS month,
                    SUM(CASE WHEN a.type = 'revenue' THEN CASE t.type WHEN 'credit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS revenue,
                    SUM(CASE WHEN a.type = 'expense' THEN CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS expenses,
                    SUM(CASE WHEN a.type = 'expense' AND {$prefix} BETWEEN 6000 AND 6999 AND {$prefix} NOT IN ({$payrollCodes}) THEN CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS operations,
                    SUM(CASE WHEN {$prefix} BETWEEN 5000 AND 5999 THEN CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS cogs,
                    SUM(CASE WHEN {$prefix} BETWEEN 1110 AND 1120 THEN CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS cash_net,
                    SUM(CASE WHEN {$prefix} = 1130 THEN CASE t.type WHEN 'credit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS ar_collections,
                    SUM(CASE WHEN {$prefix} = 1130 THEN CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS ar_booked
                ")
                ->get()
                ->keyBy('month');

            // 2) Cash balance before the window (so the cash line is a real balance, not a delta)
            $openingCash = (float) (DB::table('transactions as t')
                ->join('journal_entries as je', 'je.id', '=', 't.journal_entry_id')
                ->join('accounts as a', 'a.id', '=', 't.account_id')
                ->where('je.tenant_id', $tenantId)
                ->where('je.status', JournalEntry::STATUS_POSTED)
                ->where('je.date', '<', $start->toDateString())
                ->whereRaw("{$prefix} BETWEEN 1110 AND 1120")
                ->selectRaw("SUM(CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END) AS balance")
                ->value('balance'));

            // 3) Inventory value on the ledger (all-time posted, account 1140)
            $inventoryBalance = (float) (DB::table('transactions as t')
                ->join('journal_entries as je', 'je.id', '=', 't.journal_entry_id')
                ->join('accounts as a', 'a.id', '=', 't.account_id')
                ->where('je.tenant_id', $tenantId)
                ->where('je.status', JournalEntry::STATUS_POSTED)
                ->whereRaw("{$prefix} BETWEEN 1140 AND 1149")
                ->selectRaw("SUM(CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END) AS balance")
                ->value('balance'));

            // 4) Sales subledger by order_date (the dashboard convention)
            $saleMonthExpr = $driver === 'sqlite'
                ? "strftime('%Y-%m', order_date)"
                : "DATE_FORMAT(order_date, '%Y-%m')";
            $salesRows = DB::table('sales')
                ->where('tenant_id', $tenantId)
                ->where('order_date', '>=', $start->toDateString())
                ->groupBy('month')
                ->selectRaw("{$saleMonthExpr} AS month, SUM(total) AS total")
                ->get()
                ->keyBy('month');

            // 5) Purchases subledger by order_date
            $purchaseRows = DB::table('po')
                ->where('tenant_id', $tenantId)
                ->where('order_date', '>=', $start->toDateString())
                ->groupBy('month')
                ->selectRaw("{$saleMonthExpr} AS month, SUM(total) AS total")
                ->get()
                ->keyBy('month');

            // 6) Payroll actually paid per month (by paid_date)
            $paidRuns = PayrollRun::withSum('items as net_total', 'net_pay')
                ->where('status', 'paid')
                ->whereNotNull('paid_date')
                ->where('paid_date', '>=', $start->toDateString())
                ->get(['id', 'paid_date'])
                ->groupBy(fn ($run) => Carbon::parse($run->paid_date)->format('Y-m'))
                ->map(fn ($group) => (float) $group->sum('net_total'));

            // 7) Payroll awaiting payment (draft or processed run)
            $pendingRun = PayrollRun::withSum('items as net_total', 'net_pay')
                ->whereIn('status', ['draft', 'processed'])
                ->orderByDesc('id')
                ->first(['id', 'status', 'run_date']);

            // 8) Committed payroll: base salaries of active employees
            $committedPayroll = (float) Employee::where('status', 'active')->sum('base_salary');

            // 9) Draft journal entries (posted-only discipline check)
            $draftJes = JournalEntry::where('status', 'draft')->count();

            // 10 + 11) AR / AP aging with overdue counts (subledger, due_date based)
            $ar = $this->aging('sales', $tenantId, $driver);
            $ap = $this->aging('po', $tenantId, $driver);

            // Align everything onto the month labels; cumulate cash into a balance
            $cash = $openingCash;
            $series = [
                'labels' => $labels,
                'revenue_gl' => [], 'expenses_gl' => [], 'operations' => [],
                'cogs' => [], 'cash_end' => [], 'sales' => [], 'purchases' => [],
                'payroll' => [],
            ];
            foreach ($labels as $month) {
                $row = $glRows->get($month);
                $cash += (float) ($row->cash_net ?? 0);
                $series['revenue_gl'][] = (float) ($row->revenue ?? 0);
                $series['expenses_gl'][] = (float) ($row->expenses ?? 0);
                $series['operations'][] = (float) ($row->operations ?? 0);
                $series['cogs'][] = (float) ($row->cogs ?? 0);
                $series['cash_end'][] = $cash;
                $series['sales'][] = (float) ($salesRows->get($month)->total ?? 0);
                $series['purchases'][] = (float) ($purchaseRows->get($month)->total ?? 0);
                $series['payroll'][] = (float) $paidRuns->get($month, 0);
            }

            $series['opening_cash'] = $openingCash;
            $series['collection_ratio'] = $this->collectionRatio($tenantId, $prefix);
            $series['ar'] = $ar;
            $series['ap'] = $ap;
            $series['pending_payroll'] = $pendingRun ? [
                'id' => $pendingRun->id,
                'status' => $pendingRun->status,
                'net' => (float) ($pendingRun->net_total ?? 0),
            ] : null;
            $series['committed_payroll'] = $committedPayroll;
            $series['draft_jes'] = $draftJes;
            $series['inventory_balance'] = $inventoryBalance;
            $series['cash_now'] = $cash;

            return $series;
        });
    }

    /**
     * AR/AP aging buckets + counts of 30+ day overdue documents.
     * Mirrors DashboardService's proven driver-aware SQL (manual tenant_id).
     */
    protected function aging(string $table, int $tenantId, string $driver): array
    {
        $diffSql = $driver === 'sqlite'
            ? "julianday('now') - julianday(due_date)"
            : 'DATEDIFF(NOW(), due_date)';

        $row = DB::table($table)
            ->selectRaw("
                SUM(CASE WHEN $diffSql < 0 THEN total ELSE 0 END) AS current_amount,
                SUM(CASE WHEN $diffSql >= 0 AND $diffSql <= 30 THEN total ELSE 0 END) AS overdue_1_30,
                SUM(CASE WHEN $diffSql > 30 AND $diffSql <= 60 THEN total ELSE 0 END) AS overdue_31_60,
                SUM(CASE WHEN $diffSql > 60 AND $diffSql <= 90 THEN total ELSE 0 END) AS overdue_61_90,
                SUM(CASE WHEN $diffSql > 90 THEN total ELSE 0 END) AS overdue_90_plus,
                SUM(CASE WHEN $diffSql >= 0 THEN total ELSE 0 END) AS total_overdue,
                COUNT(CASE WHEN $diffSql >= 30 THEN 1 END) AS overdue_30plus_count
            ")
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->whereNotNull('due_date')
            ->first();

        return [
            'current' => (float) ($row->current_amount ?? 0),
            '1-30' => (float) ($row->overdue_1_30 ?? 0),
            '31-60' => (float) ($row->overdue_31_60 ?? 0),
            '61-90' => (float) ($row->overdue_61_90 ?? 0),
            '90+' => (float) ($row->overdue_90_plus ?? 0),
            'total_overdue' => (float) ($row->total_overdue ?? 0),
            'count_30plus' => (int) ($row->overdue_30plus_count ?? 0),
        ];
    }

    /**
     * Collection ratio: what share of booked receivables turned into money
     * (GL: AR credits vs AR debits over the window). Falls back to 1.0 for
     * cash businesses with no meaningful AR activity.
     */
    protected function collectionRatio(int $tenantId, string $prefix): float
    {
        // Re-read the raw 6-month AR movement from the GL (bounded, one query)
        $row = DB::table('transactions as t')
            ->join('journal_entries as je', 'je.id', '=', 't.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 't.account_id')
            ->where('je.tenant_id', $tenantId)
            ->where('je.status', JournalEntry::STATUS_POSTED)
            ->where('je.date', '>=', Carbon::now()->startOfMonth()->subMonths(config('financial-health.months', 6) - 1)->toDateString())
            ->whereRaw("{$prefix} = 1130")
            ->selectRaw("
                SUM(CASE t.type WHEN 'debit' THEN t.amount ELSE 0 END) AS booked,
                SUM(CASE t.type WHEN 'credit' THEN t.amount ELSE 0 END) AS collected
            ")
            ->first();

        $booked = (float) ($row->booked ?? 0);
        $collected = (float) ($row->collected ?? 0);

        if ($booked <= config('financial-health.noise.amount', 100000)) {
            return (float) config('financial-health.collection_ratio_fallback', 1.0);
        }

        return max(0.0, min(1.0, $collected / $booked));
    }

    // ────────────────────────────────────────────────────────────────
    // Indicators — the red/amber/green cards
    // ────────────────────────────────────────────────────────────────

    protected function indicators(array $s): array
    {
        $cfg = config('financial-health');
        $noise = $cfg['noise'];

        // Complete months: comparisons never use the in-progress month
        $complete = fn (string $key) => array_slice($s[$key], 0, -1);

        $out = [];

        // 1) CASH — real-time balance vs months of spending it covers
        $cashNow = $s['cash_now'];
        $expense3 = $this->avg($this->lastN($complete('expenses_gl'), 3));
        $cover = $expense3 > $noise['amount'] / 12 ? $cashNow / $expense3 : null;
        $status = $cover === null ? 'neutral'
            : ($cover >= $cfg['cash']['cover_good'] ? 'good'
                : ($cover >= $cfg['cash']['cover_warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'cash', 'label' => __('messages.cash'), 'icon' => 'ti-wallet',
            'value' => $cashNow, 'display' => 'money', 'status' => $status,
            'reason' => $cover === null
                ? __('messages.fh_reason.cash_cover_none')
                : strtr(__('messages.fh_reason.cash_cover'), ['{cover}' => number_format($cover, 1)]),
            'delta' => null,
        ];

        // 2) REVENUE — last complete month vs the 3 before it (subledger)
        $salesComplete = $complete('sales');
        $cur = end($salesComplete) ?: 0.0;
        $base = $this->avg($this->lastN(array_slice($salesComplete, 0, -1), 3));
        $delta = $this->pctDelta($cur, $base, $noise);
        $status = $delta === null ? 'neutral'
            : ($delta >= ($cfg['revenue']['good'] - 1) ? 'good'
                : ($delta >= ($cfg['revenue']['warn'] - 1) ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'revenue', 'label' => __('messages.revenue'), 'icon' => 'ti-report-money',
            'value' => $cur, 'display' => 'money', 'status' => $status,
            'reason' => $delta === null
                ? __('messages.fh_reason.revenue_new')
                : strtr(__('messages.fh_reason.revenue_vs_avg'), ['{pct}' => number_format($delta * 100, 0)]),
            'delta' => $delta,
        ];

        // 3) PROFIT — GL net margin over the trailing 3 complete months
        $rev3 = array_sum($this->lastN($complete('revenue_gl'), 3));
        $exp3 = array_sum($this->lastN($complete('expenses_gl'), 3));
        $margin = $rev3 > $noise['amount'] ? ($rev3 - $exp3) / $rev3 : null;
        $status = $margin === null ? 'neutral'
            : ($margin >= $cfg['margin']['good'] ? 'good'
                : ($margin >= $cfg['margin']['warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'profit', 'label' => __('messages.profit'), 'icon' => 'ti-trending-up',
            'value' => $margin, 'display' => 'percent', 'status' => $status,
            'reason' => $margin === null
                ? __('messages.fh_reason.margin_none')
                : strtr(__('messages.fh_reason.margin_line'), ['{pct}' => number_format($margin * 100, 1)]),
            'delta' => null,
        ];

        // 4) PURCHASES — 3-month spend vs 3-month sales (spending discipline)
        $purch3 = array_sum($this->lastN($complete('purchases'), 3));
        $sales3 = array_sum($this->lastN($complete('sales'), 3));
        $ratio = $sales3 > $noise['amount'] ? $purch3 / $sales3 : null;
        $status = $ratio === null ? 'neutral'
            : ($ratio <= $cfg['purchases']['good'] ? 'good'
                : ($ratio <= $cfg['purchases']['warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'purchases', 'label' => __('messages.purchases'), 'icon' => 'ti-shopping-cart',
            'value' => $purch3, 'display' => 'money', 'status' => $status,
            'reason' => $ratio === null
                ? __('messages.fh_reason.purchases_none')
                : strtr(__('messages.fh_reason.purchases_vs_sales'), ['{ratio}' => number_format($ratio, 2)]),
            'delta' => null,
        ];

        // 5) RECEIVABLES — share of outstanding that is 31+ days overdue
        $ar = $s['ar'];
        $arTotal = array_sum([$ar['current'], $ar['1-30'], $ar['31-60'], $ar['61-90'], $ar['90+']]);
        $arLate = $ar['31-60'] + $ar['61-90'] + $ar['90+'];
        $arLateShare = $arTotal > $noise['amount'] ? $arLate / $arTotal : null;
        $status = $arLateShare === null
            ? ($ar['total_overdue'] > 0 ? 'warn' : 'good')
            : ($arLateShare >= $cfg['receivables']['danger'] ? 'danger'
                : ($arLateShare >= $cfg['receivables']['warn'] ? 'warn' : 'good'));
        $out[] = [
            'key' => 'receivables', 'label' => __('messages.receivables'), 'icon' => 'ti-receipt',
            'value' => $arTotal, 'display' => 'money', 'status' => $status,
            'reason' => $arTotal <= $noise['amount']
                ? __('messages.fh_reason.ar_none')
                : ($arLateShare === null || $arLateShare <= 0.001
                    ? __('messages.fh_reason.ar_clean')
                    : strtr(__('messages.fh_reason.ar_overdue_share'), ['{pct}' => number_format($arLateShare * 100, 0)])),
            'delta' => null,
        ];

        // 6) PAYABLES — overdue bills relative to the cash that must pay them
        $ap = $s['ap'];
        $apTotal = array_sum([$ap['current'], $ap['1-30'], $ap['31-60'], $ap['61-90'], $ap['90+']]);
        $apLate = $ap['31-60'] + $ap['61-90'] + $ap['90+'];
        $apCover = $apLate > $noise['amount']
            ? $apLate / max($cashNow, $noise['amount'] / 12)
            : null;
        $status = $apCover === null ? 'good'
            : ($apCover >= $cfg['payables']['danger'] ? 'danger'
                : ($apCover >= $cfg['payables']['warn'] ? 'warn' : 'good'));
        $out[] = [
            'key' => 'payables', 'label' => __('messages.payables'), 'icon' => 'ti-credit-card',
            'value' => $apTotal, 'display' => 'money', 'status' => $status,
            'reason' => $apCover === null
                ? __('messages.fh_reason.ap_clean')
                : strtr(__('messages.fh_reason.ap_vs_cash'), ['{ratio}' => number_format($apCover, 1)]),
            'delta' => null,
        ];

        // 7) PAYROLL — scheduled vs affordable
        $paid3 = array_sum($this->lastN($complete('payroll'), 3));
        $burden = $rev3 > $noise['amount'] ? $paid3 / $rev3 : null;
        $pending = $s['pending_payroll'];
        if ($pending !== null) {
            $status = 'warn';
            $reason = strtr(__('messages.fh_reason.payroll_pending'),
                ['{amount}' => CurrencyHelper::format($pending['net'] ?: $s['committed_payroll'])]);
        } elseif ($burden !== null && $burden >= $cfg['payroll']['burden_danger']) {
            $status = 'danger';
            $reason = strtr(__('messages.fh_reason.payroll_burden'), ['{pct}' => number_format($burden * 100, 0)]);
        } else {
            $status = $paid3 > 0 || $s['committed_payroll'] > 0 ? 'good' : 'neutral';
            $reason = strtr(__('messages.fh_reason.payroll_paid'),
                ['{amount}' => CurrencyHelper::format($s['committed_payroll'] ?: $paid3)]);
        }
        $out[] = [
            'key' => 'payroll', 'label' => __('messages.payroll'), 'icon' => 'ti-users',
            'value' => $s['committed_payroll'] ?: $paid3, 'display' => 'money', 'status' => $status,
            'reason' => $reason,
            'delta' => null,
        ];

        // 8) INVENTORY TURNOVER — annualised COGS vs stock on the books
        $completeCount = max(1, count($complete('cogs')));
        $cogsWindow = array_sum($complete('cogs'));
        $annualCogs = $cogsWindow * (12 / $completeCount);
        $inventory = $s['inventory_balance'];
        $turns = $inventory > $noise['amount'] / 12 ? $annualCogs / $inventory : null;
        $status = $turns === null ? 'neutral'
            : ($turns >= $cfg['turnover']['good'] ? 'good'
                : ($turns >= $cfg['turnover']['warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'turnover', 'label' => __('messages.turnover'), 'icon' => 'ti-rotate',
            'value' => $turns, 'display' => 'ratio', 'status' => $status,
            'reason' => $turns === null
                ? __('messages.fh_reason.turnover_none')
                : strtr(__('messages.fh_reason.turnover_line'), [
                    '{turns}' => number_format($turns, 1),
                    '{months}' => number_format(12 / max($turns, 0.1), 0),
                ]),
            'delta' => null,
        ];

        return $out;
    }

    protected function overallStatus(array $indicators): string
    {
        $rank = ['danger' => 3, 'warn' => 2, 'good' => 1, 'neutral' => 0];
        $worst = 'neutral';
        foreach ($indicators as $item) {
            if (($rank[$item['status']] ?? 0) > ($rank[$worst] ?? 0)) {
                $worst = $item['status'];
            }
        }

        return $worst;
    }

    // ────────────────────────────────────────────────────────────────
    // Narrative — plain words, varied phrasing, only what is salient
    // ────────────────────────────────────────────────────────────────

    /**
     * Builds the "what stands out" lines. Insight types with a red/amber/
     * green verdict are turned into full sentences from rotating template
     * pools (lang arrays), ordered worst-first, noise-filtered, capped —
     * and always closed with the outlook statement when one is given.
     */
    protected function narrative(array $indicators, array $s, ?array $outlook): array
    {
        $cfg = config('financial-health');
        $noise = $cfg['noise'];
        $byKey = collect($indicators)->keyBy('key');
        $salesComplete = array_slice($s['sales'], 0, -1);
        $avgSales3 = $this->avg($this->lastN(array_slice($salesComplete, 0, -1), 3));
        $lastSales = end($salesComplete) ?: 0.0;

        $insights = [];

        $push = function (string $severity, string $type, array $slots = []) use (&$insights) {
            $text = $this->template($type, $slots);
            if ($text !== null) {
                $insights[] = ['severity' => $severity, 'type' => $type, 'text' => $text];
            }
        };

        // Cash verdict
        $cash = $byKey->get('cash');
        if ($cash['status'] === 'danger') {
            $push('danger', 'cash_critical', [
                '{cover}' => number_format($cash['value'] / max($this->avg($this->lastN(array_slice($s['expenses_gl'], 0, -1), 3)), 1), 1),
                '{amount}' => CurrencyHelper::format($cash['value']),
            ]);
        } elseif ($cash['status'] === 'good') {
            $expense3 = $this->avg($this->lastN(array_slice($s['expenses_gl'], 0, -1), 3));
            $push('good', 'cash_comfortable', [
                '{cover}' => number_format($expense3 > 0 ? $cash['value'] / $expense3 : 0, 1),
                '{amount}' => CurrencyHelper::format($cash['value']),
            ]);
        }

        // Revenue move (only when the move is above the noise floor)
        $rev = $byKey->get('revenue');
        if ($rev['delta'] !== null && abs($rev['delta']) >= $noise['percent']) {
            $slots = [
                '{cur}' => CurrencyHelper::format($lastSales),
                '{avg}' => CurrencyHelper::format($avgSales3),
                '{pct}' => number_format(abs($rev['delta']) * 100, 0),
            ];
            if ($rev['status'] === 'danger' || $rev['status'] === 'warn') {
                $push($rev['status'], 'revenue_down', $slots);
            } elseif ($rev['status'] === 'good') {
                $push('good', 'revenue_up', $slots);
            }
        }

        // Margin
        $profit = $byKey->get('profit');
        if ($profit['status'] === 'danger') {
            $push('danger', 'margin_losing', ['{pct}' => number_format(abs($profit['value']) * 100, 1)]);
        } elseif ($profit['status'] === 'good') {
            $push('good', 'margin_healthy', ['{pct}' => number_format($profit['value'] * 100, 1)]);
        }

        // Purchases outpacing sales
        $purch = $byKey->get('purchases');
        if ($purch['status'] === 'danger') {
            $purch3 = array_sum($this->lastN(array_slice($s['purchases'], 0, -1), 3));
            $sales3 = array_sum($this->lastN($salesComplete, 3));
            $push('danger', 'purchases_high', ['{ratio}' => number_format($sales3 > 0 ? $purch3 / $sales3 : 0, 2)]);
        }

        // Receivables / payables
        $ar = $byKey->get('receivables');
        if (in_array($ar['status'], ['danger', 'warn'], true) && $ar['reason'] !== __('messages.fh_reason.ar_clean')) {
            $arLate = $s['ar']['31-60'] + $s['ar']['61-90'] + $s['ar']['90+'];
            $push($ar['status'], 'ar_overdue', [
                '{amount}' => CurrencyHelper::format($arLate),
                '{count}' => $s['ar']['count_30plus'],
            ]);
        }
        $ap = $byKey->get('payables');
        if (in_array($ap['status'], ['danger', 'warn'], true)) {
            $apLate = $s['ap']['31-60'] + $s['ap']['61-90'] + $s['ap']['90+'];
            $push($ap['status'], 'ap_overdue', [
                '{amount}' => CurrencyHelper::format($apLate),
                '{count}' => $s['ap']['count_30plus'],
            ]);
        }

        // Payroll
        if ($s['pending_payroll'] !== null) {
            $push('warn', 'payroll_pending', [
                '{amount}' => CurrencyHelper::format($s['pending_payroll']['net'] ?: $s['committed_payroll']),
            ]);
        }
        $payroll = $byKey->get('payroll');
        if ($payroll['status'] === 'danger') {
            $rev3 = array_sum($this->lastN(array_slice($s['revenue_gl'], 0, -1), 3));
            $paid3 = array_sum($this->lastN(array_slice($s['payroll'], 0, -1), 3));
            $push('danger', 'payroll_burden', ['{pct}' => number_format($rev3 > 0 ? ($paid3 / $rev3) * 100 : 0, 0)]);
        }

        // Turnover (only verdicts worth saying)
        $turnover = $byKey->get('turnover');
        if ($turnover['status'] === 'danger') {
            $push('danger', 'turnover_slow', ['{months}' => number_format(12 / max($turnover['value'] ?? 1, 0.1), 0)]);
        } elseif ($turnover['status'] === 'good') {
            $push('good', 'turnover_healthy', ['{turns}' => number_format($turnover['value'], 1)]);
        }

        // Worst first, then keep the cap for the body lines
        $order = ['danger' => 0, 'warn' => 1, 'good' => 2];
        usort($insights, fn ($a, $b) => ($order[$a['severity']] <=> $order[$b['severity']])
            ?: (crc32($a['type']) <=> crc32($b['type'])));
        $lines = array_map(
            fn ($insight) => ['text' => $insight['text'], 'severity' => $insight['severity']],
            array_slice($insights, 0, (int) $cfg['narrative_max'])
        );

        // The outlook always closes the narrative, in its own words
        if ($outlook !== null && ($outlook['statement'] ?? '') !== '') {
            $lines[] = [
                'text' => $outlook['statement'],
                'severity' => $outlook['tone'] === 'down' ? 'danger' : ($outlook['tone'] === 'up' ? 'good' : 'neutral'),
            ];
        }

        return $lines;
    }

    /**
     * Rotating template picker: same insight type reads differently month to
     * month (deterministic rotation — no randomness, stable within a month).
     */
    protected function template(string $type, array $slots): ?string
    {
        $pool = __('messages.fh_narrative.'.$type);
        if (! is_array($pool) || $pool === []) {
            return null;
        }
        $rotation = ((int) now()->format('Y') * 12 + (int) now()->format('m') + crc32($type)) % count($pool);

        return strtr($pool[$rotation], $slots);
    }

    // ────────────────────────────────────────────────────────────────
    // Outlook — next month, as a chain that ends in one sentence
    // ────────────────────────────────────────────────────────────────

    protected function outlook(array $s): array
    {
        $cfg = config('financial-health');
        $salesComplete = array_slice($s['sales'], 0, -1);

        // Revenue next month: Holt-Winters on 24 months (falls back to the
        // service's linear trend, then to a 3-month weighted subledger avg)
        $forecast = $this->forecastService->generateForecast(1, 'holt-winters');
        $method = 'holt_winters';
        $mape = $forecast['metrics']['mape'] ?? null;
        $revenueNext = $forecast['forecast'][0] ?? null;
        if ($revenueNext === null) {
            $method = 'weighted_trend';
            $revenueNext = $this->weighted3($salesComplete, $cfg['weights']);
        }
        $confidence = $mape === null
            ? ($method === 'holt_winters' ? 70.0 : 45.0)
            : max(30.0, min(95.0, 100 - (float) $mape));

        $cashIn = $revenueNext * $s['collection_ratio'];

        // Expenses next month
        $purchasesNext = $this->weighted3($s['purchases'], $cfg['weights']);
        $operationsNext = $this->weighted3($s['operations'], $cfg['weights']);
        $payrollNext = $s['committed_payroll'] > 0
            ? $s['committed_payroll']
            : $this->weighted3($s['payroll'], $cfg['weights']);
        $expensesNext = $purchasesNext + $operationsNext + $payrollNext;

        // The chain ends here
        $cashNow = $s['cash_now'];
        $netNext = $cashIn - $expensesNext;
        $projected = $cashNow + $netNext;
        $runway = $netNext < -$cfg['noise']['amount']
            ? $cashNow / abs($netNext)
            : null;

        // Best/worst-of-last-6 scenarios: replay the same chain on actuals
        $nets = [];
        foreach ($salesComplete as $i => $salesMonth) {
            $in = $salesMonth * $s['collection_ratio'];
            $out = ($s['purchases'][$i] ?? 0) + ($s['payroll'][$i] ?? 0) + ($s['operations'][$i] ?? 0);
            if (($in + $out) > $cfg['noise']['amount']) {
                $nets[] = $in - $out;
            }
        }
        rsort($nets);
        $best = $nets !== [] ? array_sum(array_slice($nets, 0, 3)) / min(3, count($nets)) : 0.0;
        $worst = $nets !== [] ? array_sum(array_slice($nets, -3)) / min(3, count($nets)) : 0.0;

        $tone = $netNext > max($cfg['noise']['amount'], abs($cashNow) * 0.05) ? 'up'
            : ($netNext < -max($cfg['noise']['amount'], abs($cashNow) * 0.05) ? 'down' : 'flat');
        $statement = $this->template('statement_'.$tone, [
            '{cash}' => CurrencyHelper::format($projected),
            '{delta}' => CurrencyHelper::format(abs($netNext)),
            '{burn}' => CurrencyHelper::format(abs($netNext)),
            '{month}' => Carbon::now()->addMonth()->translatedFormat('F'),
        ]);
        if ($tone === 'down' && $runway !== null && $runway < $cfg['runway']['warn']) {
            $statement .= ' '.$this->template('statement_runway', ['{runway}' => number_format($runway, 0)]);
        }

        return [
            'month' => Carbon::now()->addMonth()->translatedFormat('F Y'),
            'revenue' => $revenueNext,
            'revenue_method' => $method,
            'revenue_confidence' => $confidence,
            'collection_ratio' => $s['collection_ratio'],
            'cash_in' => $cashIn,
            'purchases' => $purchasesNext,
            'payroll' => $payrollNext,
            'operations' => $operationsNext,
            'expenses' => $expensesNext,
            'net' => $netNext,
            'cash_now' => $cashNow,
            'projected' => $projected,
            'range_low' => $cashNow + $worst,
            'range_high' => $cashNow + $best,
            'range_available' => count($nets) >= 2,
            'runway' => $runway,
            'tone' => $tone,
            'statement' => $statement,
        ];
    }

    // ────────────────────────────────────────────────────────────────
    // Attention list — what to click on, worst first
    // ────────────────────────────────────────────────────────────────

    protected function attentionList(array $s): array
    {
        $cfg = config('financial-health');
        $items = [];

        $arLate = $s['ar']['31-60'] + $s['ar']['61-90'] + $s['ar']['90+'];
        if ($s['ar']['count_30plus'] > 0 && $arLate > $cfg['noise']['amount']) {
            $items[] = [
                'status' => 'danger', 'icon' => 'ti-receipt',
                'text' => strtr(__('messages.fh_attention.ar_overdue'), ['{count}' => $s['ar']['count_30plus']]),
                'amount' => $arLate,
                'url' => route('admin.reports.aged-receivables'),
                'link_label' => __('messages.view_details'),
            ];
        }

        $apLate = $s['ap']['31-60'] + $s['ap']['61-90'] + $s['ap']['90+'];
        if ($s['ap']['count_30plus'] > 0 && $apLate > $cfg['noise']['amount']) {
            $items[] = [
                'status' => 'danger', 'icon' => 'ti-credit-card',
                'text' => strtr(__('messages.fh_attention.ap_overdue'), ['{count}' => $s['ap']['count_30plus']]),
                'amount' => $apLate,
                'url' => route('admin.reports.aged-payables'),
                'link_label' => __('messages.view_details'),
            ];
        }

        $expense3 = $this->avg($this->lastN(array_slice($s['expenses_gl'], 0, -1), 3));
        if ($expense3 > 0 && $s['cash_now'] / $expense3 < $cfg['cash']['cover_warn']) {
            $items[] = [
                'status' => 'warn', 'icon' => 'ti-wallet',
                'text' => __('messages.fh_attention.low_cash'),
                'amount' => $s['cash_now'],
                'url' => route('admin.reports.cash-flow'),
                'link_label' => __('messages.view_details'),
            ];
        }

        if ($s['pending_payroll'] !== null) {
            $items[] = [
                'status' => 'warn', 'icon' => 'ti-users',
                'text' => __('messages.fh_attention.payroll_pending'),
                'amount' => $s['pending_payroll']['net'] ?: $s['committed_payroll'],
                'url' => route('admin.hr.payroll.index'),
                'link_label' => __('messages.open_payroll'),
            ];
        }

        if ($s['draft_jes'] > 0) {
            $items[] = [
                'status' => 'warn', 'icon' => 'ti-notebook',
                'text' => strtr(__('messages.fh_attention.draft_jes'), ['{count}' => $s['draft_jes']]),
                'amount' => null,
                'url' => route('admin.accounting.journal'),
                'link_label' => __('messages.open_journal'),
            ];
        }

        return $items;
    }

    // ────────────────────────────────────────────────────────────────
    // Small numeric helpers
    // ────────────────────────────────────────────────────────────────

    protected function lastN(array $arr, int $n): array
    {
        return array_slice($arr, -$n);
    }

    protected function avg(array $arr): float
    {
        return $arr === [] ? 0.0 : array_sum($arr) / count($arr);
    }

    /** Relative change vs a base; null when the base is below the noise floor. */
    protected function pctDelta(float $cur, float $base, array $noise): ?float
    {
        if ($base <= $noise['amount']) {
            return null;
        }

        return ($cur - $base) / $base;
    }

    /** Weighted estimate over the last 3 values (weights oldest->newest). */
    protected function weighted3(array $values, array $weights): float
    {
        $last3 = array_slice($values, -3);
        if ($last3 === []) {
            return 0.0;
        }
        $weights = array_slice($weights, -count($last3));
        $total = array_sum($weights);

        return $total > 0
            ? array_sum(array_map(fn ($v, $w) => $v * $w, $last3, $weights)) / $total
            : $this->avg($last3);
    }

    protected function remember(string $key, callable $compute)
    {
        if (! array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $compute();
        }

        return $this->memo[$key];
    }
}
