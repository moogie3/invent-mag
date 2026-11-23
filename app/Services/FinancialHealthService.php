<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
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

    /** Period presets the filter bar offers. Every one resolves to a plain
     *  [from..to] month range — one code path, no special cases downstream. */
    public const PERIOD_PRESETS = ['last_month', 'last_3', 'last_6', 'ytd', 'last_year'];

    /**
     * Dashboard strip payload: indicators + the sharpest narrative lines.
     * Deliberately skips the forecast chain (no extra queries beyond series).
     * The strip carries no filter of its own, so it always reports the
     * default rolling window; only the full page has the period selector.
     */
    public function getSummary(): array
    {
        return $this->remember('summary', function () {
            $series = $this->series($this->resolvePeriod(null, null, null));
            $indicators = $this->indicators($series);
            $attention = $this->attentionList($series);

            return [
                'overall' => $this->overallStatus($indicators),
                'indicators' => $indicators,
                'narrative' => array_slice($this->narrative($indicators, $series, null), 0, 3),
                'attention_count' => count($attention),
                'months' => $series['labels'],
                // Mini trend for the strip's animated chart — the same cash +
                // revenue lines the full page draws, at dashboard scale.
                'chart' => [
                    'cash' => array_map(fn ($v) => (float) $v, $series['cash_end']),
                    'revenue' => array_map(fn ($v) => (float) $v, $series['revenue_gl']),
                ],
            ];
        });
    }

    /**
     * Full page payload: indicators, narrative, next-month outlook chain,
     * trend series and the attention list — all scoped to one period.
     *
     * @param  string|null  $preset  One of PERIOD_PRESETS. Resolved server-side so a
     *                               filtered URL stays shareable without JavaScript.
     * @param  string|null  $from  Custom range start (Y-m or Y-m-d).
     * @param  string|null  $to  Custom range end (Y-m or Y-m-d).
     */
    public function getOverview(?string $preset = null, ?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod($preset, $from, $to);

        return $this->remember('overview|'.$this->periodSignature($period), function () use ($period) {
            $series = $this->series($period);
            $indicators = $this->indicators($series);
            $outlook = $this->outlook($series);

            return [
                'overall' => $this->overallStatus($indicators),
                'indicators' => $indicators,
                'narrative' => $this->narrative($indicators, $series, $outlook),
                'outlook' => $outlook,
                'trends' => $series,
                // What the shelf holds, and how far the books are from it,
                // surfaced as their own keys so a caller can render the
                // figure without digging it out of the indicator list.
                'stock_value' => $series['stock_value'],
                'inventory_drift' => $series['inventory_drift'],
                'attention' => $this->attentionList($series),
                'period' => $period,
            ];
        });
    }

    /**
     * Period-comparison payload for the per-card modal and the "Compare all"
     * table. ONE engine behind both surfaces: it reuses the same memoised
     * series() and indicators() the page already computed — no extra queries.
     *
     * With no filter it compares the LAST COMPLETE month against the month
     * before it (comparison_window = 1). With a filter it compares the whole
     * selected period against the immediately preceding window of the same
     * length — and the series is loaded one window early so the prior side
     * always has real data behind it.
     *
     * Flow metrics (cash, revenue, net/gross margin, purchases) carry a real
     * monthly series, so they get a true delta. Balance-sheet snapshots
     * (receivables, payables, payroll, turnover, working capital,
     * debt-to-equity, DSO) are point-in-time by design — they are returned
     * locked with a reason rather than a fabricated delta.
     */
    public function getComparison(?string $preset = null, ?string $from = null, ?string $to = null): array
    {
        $period = $this->resolvePeriod($preset, $from, $to);

        return $this->remember('comparison|'.$this->periodSignature($period), function () use ($period) {
            $s = $this->series($period);
            $byKey = collect($this->indicators($s))->keyBy('key');

            $labels = $s['labels'];
            // Only COMPLETE months take part: the in-progress month is never
            // compared as though it were finished.
            $completeIdx = array_keys(array_filter($s['complete']));
            $window = (int) $period['comparison_window'];
            $currentIdx = $completeIdx === [] ? [] : array_slice($completeIdx, -$window);
            $previousIdx = count($completeIdx) < $window * 2
                ? []
                : array_slice($completeIdx, -$window * 2, $window);

            $monthly = $this->monthlyFlowValues($s);

            $rows = [];
            foreach ($byKey as $key => $ind) {
                $meta = $this->comparisonMeta($key);
                $row = [
                    'key' => $key,
                    'label' => $ind['label'],
                    'icon' => $ind['icon'],
                    'display' => $ind['display'],
                    'status' => $ind['status'],
                    'value' => $ind['value'],
                    'reason' => $ind['reason'],
                    'comparable' => $meta['comparable'],
                    'higher_is_better' => $meta['higher_is_better'],
                    'lock_reason' => $meta['lock_reason'],
                    // For flow metrics this is the selected period (one window
                    // of complete months — apples-to-apples against the prior
                    // window below); for locked snapshots it is the current
                    // point-in-time value.
                    'current' => $ind['value'],
                    'previous' => null,
                    'delta' => null,
                    'trend' => null,
                    'spark' => [],
                ];

                if ($meta['comparable'] && isset($monthly[$key])) {
                    $values = $monthly[$key];
                    $row['current'] = $this->flowWindowValue($key, $currentIdx, $s);
                    $row['previous'] = $previousIdx === [] ? null : $this->flowWindowValue($key, $previousIdx, $s);
                    // Sparkline: every complete month the comparison spans
                    // (the prior window included, so the move is visible),
                    // with its month label so the chart can name each point.
                    $keepIdx = array_values(array_filter(
                        $completeIdx,
                        fn ($i) => ($values[$i] ?? null) !== null
                    ));
                    $row['spark'] = array_values(array_map(fn ($i) => $values[$i], $keepIdx));
                    $row['spark_labels'] = array_values(array_map(fn ($i) => $labels[$i], $keepIdx));
                    if ($row['current'] !== null && $row['previous'] !== null) {
                        $row['delta'] = $row['current'] - $row['previous'];
                        $tolerance = max(abs($row['previous']), 1.0) * 0.001;
                        $row['trend'] = abs($row['delta']) <= $tolerance
                            ? 'flat'
                            : ($row['delta'] > 0 ? 'up' : 'down');
                    }
                }

                $rows[] = $row;
            }

            return [
                'rows' => $rows,
                'labels' => $labels,
                'period' => $period,
                'compared' => [
                    // End month of each window (single month when the window
                    // is one month, which is what the unfiltered page shows).
                    'current' => $labels[end($currentIdx)] ?? null,
                    'previous' => $labels[end($previousIdx)] ?? null,
                    // Start month of each window, so the UI can render a range
                    // like "Jan 2026 – Sep 2026" when a longer period is picked.
                    'current_from' => $labels[$currentIdx[0]] ?? null,
                    'previous_from' => $labels[$previousIdx[0]] ?? null,
                ],
            ];
        });
    }

    // ────────────────────────────────────────────────────────────────
    // Period — one [from..to] range behind every preset and custom range
    // ────────────────────────────────────────────────────────────────

    /**
     * Turn any filter request into a single validated month range.
     *
     * Presets and custom input both reduce to the same shape, so everything
     * downstream sees exactly one thing. The rules, in order:
     *  - unparseable input falls back to the default rolling window;
     *  - the range never reaches into the future or the in-progress month
     *    (the page's whole doctrine is complete months only);
     *  - `from` can never sit after `to`;
     *  - the range is capped at `period_max_months`.
     *
     * It also computes the two windows everything else reads from:
     *  - `window` — complete months the indicator cards aggregate;
     *  - `comparison_window` — months on each side of the modal's delta (1 when
     *    unfiltered, so the default view keeps comparing month to month);
     *  - `series_from` — how far back the series must load for the PRIOR
     *    window to have real data (equal to `from` when nothing extra is needed).
     *
     * @return array{from: Carbon, to: Carbon, months: int, window: int, comparison_window: int, series_from: Carbon, series_months: int, preset: string|null, default: bool}
     */
    protected function resolvePeriod(?string $preset, ?string $from, ?string $to): array
    {
        $maxMonths = max(1, (int) config('financial-health.period_max_months', 24));
        $currentStart = Carbon::now()->startOfMonth();
        // The most recent month whose books are closed.
        $lastComplete = $currentStart->copy()->subMonth();

        $start = $this->parseMonth($from);
        $end = $this->parseMonth($to);
        $usedPreset = in_array($preset, self::PERIOD_PRESETS, true) ? $preset : null;

        if ($usedPreset !== null) {
            [$start, $end] = match ($usedPreset) {
                'last_month' => [$lastComplete->copy(), $lastComplete->copy()],
                'last_3' => [$lastComplete->copy()->subMonths(2), $lastComplete->copy()],
                'last_6' => [$lastComplete->copy()->subMonths(5), $lastComplete->copy()],
                // Year-to-date stops at the last COMPLETE month, never at the
                // partial one; in January that leaves a single month to report.
                'ytd' => [$currentStart->copy()->startOfYear(), $lastComplete->copy()],
                'last_year' => [
                    $currentStart->copy()->subYear()->startOfYear(),
                    $currentStart->copy()->subYear()->endOfYear(),
                ],
            };
        }

        if ($start === null && $end === null && $usedPreset === null) {
            // Default: the rolling window the page has always opened on.
            $months = (int) config('financial-health.months', 6);

            return [
                'from' => $currentStart->copy()->subMonths($months - 1),
                'to' => $currentStart->copy(),
                'months' => $months,
                'window' => max(1, (int) config('financial-health.window_months', 3)),
                'comparison_window' => 1,
                'series_from' => $currentStart->copy()->subMonths($months - 1),
                'series_months' => $months,
                'preset' => null,
                'default' => true,
            ];
        }

        // A one-sided custom range implies the other side (same month), and a
        // preset that overshoots (YTD in January) is pulled back into order.
        $start ??= $end->copy();
        $end ??= $start->copy();
        if ($start->gt($end)) {
            $start = $end->copy();
        }

        // Never the future, never the month still in progress.
        if ($end->gt($lastComplete)) {
            $end = $lastComplete->copy();
        }
        if ($start->gt($end)) {
            $start = $end->copy();
        }

        // Cap the range (the series loads a second window on top for the
        // comparison, so keep the user-facing range itself bounded).
        $span = (int) $start->diffInMonths($end) + 1;
        if ($span > $maxMonths) {
            $start = $end->copy()->subMonths($maxMonths - 1);
            $span = $maxMonths;
        }

        $comparisonWindow = $span;
        // Load far enough back that the window BEFORE the selection exists.
        $priorStart = $end->copy()->subMonths($comparisonWindow * 2 - 1);
        $seriesFrom = $start->lt($priorStart) ? $start : $priorStart;
        $seriesMonths = (int) $seriesFrom->diffInMonths($end) + 1;

        return [
            'from' => $start,
            'to' => $end,
            'months' => $span,
            'window' => $span,
            'comparison_window' => $comparisonWindow,
            'series_from' => $seriesFrom,
            'series_months' => $seriesMonths,
            'preset' => $usedPreset,
            'default' => false,
        ];
    }

    /** Parse a Y-m / Y-m-d filter value to the 1st of that month, or null. */
    protected function parseMonth(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        if (! preg_match('/^(\d{4})-(\d{1,2})/', trim($value), $m)) {
            return null;
        }
        $year = (int) $m[1];
        $month = (int) $m[2];
        if ($month < 1 || $month > 12) {
            return null;
        }

        return Carbon::create($year, $month, 1);
    }

    /** Memo key fragment: two periods never share a cached payload. */
    protected function periodSignature(array $period): string
    {
        return $period['from']->format('Y-m').':'.$period['to']->format('Y-m');
    }

    /** Translated period phrasing for reason/narrative strings. */
    protected function periodSlots(int $months): array
    {
        return [
            '{period}' => $months === 1
                ? __('messages.period_span_1')
                : strtr(__('messages.period_span'), ['{n}' => number_format($months, 0)]),
            '{period_adj}' => $months === 1
                ? __('messages.period_adj_1')
                : strtr(__('messages.period_adj'), ['{n}' => number_format($months, 0)]),
        ];
    }

    // ────────────────────────────────────────────────────────────────
    // Period comparison — one engine for the modal + "Compare all"
    // ────────────────────────────────────────────────────────────────

    /**
     * Per-month values for the flow metrics that carry a real monthly
     * series. These are the only indicators whose "last complete month vs
     * the month before" comparison is exact — the rest are balance-sheet
     * snapshots (see comparisonMeta).
     *
     * @return array<string, array<int, float|null>> key => value per month
     */
    protected function monthlyFlowValues(array $s): array
    {
        $labels = $s['labels'];
        $cashEnd = $s['cash_end'];
        $out = [];

        // Cash: real balance at each month end (already a series).
        $out['cash'] = array_map(fn ($v) => (float) $v, $cashEnd);

        // Revenue: the last COMPLETE month's GL revenue (matches the card's
        // "vs average" window, read as a single-month point).
        $rev = $s['revenue_gl'];
        $out['revenue'] = array_map(fn ($v) => (float) $v, $rev);

        // Net margin per month = (revenue − all expenses) / revenue.
        $out['net_margin'] = array_map(
            fn ($i) => $rev[$i] > 0 ? ($rev[$i] - $s['expenses_gl'][$i]) / $rev[$i] : null,
            array_keys($labels)
        );

        // Gross margin per month = (revenue − COGS) / revenue.
        $out['gross_margin'] = array_map(
            fn ($i) => $rev[$i] > 0 ? ($rev[$i] - $s['cogs'][$i]) / $rev[$i] : null,
            array_keys($labels)
        );

        // Purchases: subledger spend per month.
        $out['purchases'] = array_map(fn ($v) => (float) $v, $s['purchases']);

        return $out;
    }

    /**
     * A flow metric aggregated over ONE window of complete months.
     *
     * Balances are read at the window's end (not summed), flows are summed,
     * and margins are recomputed from the window's own revenue and cost —
     * so a period is never compared against a single month. With a
     * one-month window this is exactly the monthly value, which is why the
     * unfiltered page behaves as it always has.
     *
     * @param  array<int>  $idx  Month indexes the window covers.
     */
    protected function flowWindowValue(string $key, array $idx, array $s): ?float
    {
        if ($idx === []) {
            return null;
        }

        $sum = fn (string $seriesKey) => array_sum(array_map(
            fn ($i) => (float) ($s[$seriesKey][$i] ?? 0.0),
            $idx
        ));

        switch ($key) {
            case 'cash':
                // A balance, not a flow: the position at the window's end.
                return (float) ($s['cash_end'][end($idx)] ?? 0.0);

            case 'revenue':
                return $sum('revenue_gl');

            case 'purchases':
                return $sum('purchases');

            case 'net_margin':
            case 'gross_margin':
                $revenue = $sum('revenue_gl');
                if ($revenue <= (float) config('financial-health.noise.amount', 100000)) {
                    return null;
                }
                $cost = $sum($key === 'net_margin' ? 'expenses_gl' : 'cogs');

                return ($revenue - $cost) / $revenue;
        }

        return null;
    }

    /**
     * Whether a metric can be compared month-over-month, and if not, why it
     * is locked. Flow metrics are comparable; balance-sheet snapshots are
     * point-in-time by design (reconstructing a past aging would mean
     * replaying payment history — easy to get subtly wrong).
     *
     * @param  string  $key  Indicator key.
     * @return array{comparable: bool, higher_is_better: bool|null, lock_reason: string|null}
     */
    protected function comparisonMeta(string $key): array
    {
        // higher_is_better drives the delta arrow's tone (null = neutral).
        $flow = [
            'cash' => true,
            'revenue' => true,
            'net_margin' => true,
            'gross_margin' => true,
            'purchases' => null, // spend level alone is not "better" up or down
        ];

        if (array_key_exists($key, $flow)) {
            return ['comparable' => true, 'higher_is_better' => $flow[$key], 'lock_reason' => null];
        }

        return [
            'comparable' => false,
            'higher_is_better' => null,
            'lock_reason' => __('messages.comparison_point_in_time'),
        ];
    }

    // ────────────────────────────────────────────────────────────────
    // Series — the bounded aggregates everything is computed from
    // ────────────────────────────────────────────────────────────────

    /**
     * The bounded aggregates everything else computes from.
     *
     * The month labels follow the requested period (including the extra
     * window the comparison needs), and `complete` marks which of them are
     * finished — every consumer reads that mask instead of guessing that the
     * final bucket is the in-progress month.
     */
    protected function series(array $period): array
    {
        return $this->remember('series|'.$this->periodSignature($period), function () use ($period) {
            $tenantId = app('currentTenant')->id;
            $driver = DB::connection()->getDriverName();
            $start = $period['series_from'];
            $months = (int) $period['series_months'];

            $labels = [];
            for ($i = 0; $i < $months; $i++) {
                $labels[] = $start->copy()->addMonths($i)->format('Y-m');
            }
            // A month is complete once the next month has begun.
            $currentMonth = Carbon::now()->format('Y-m');
            $complete = array_map(fn ($label) => $label < $currentMonth, $labels);

            // Driver-aware expressions (tests run on sqlite, production on MySQL)
            $monthExpr = $driver === 'sqlite'
                ? "strftime('%Y-%m', je.date)"
                : "DATE_FORMAT(je.date, '%Y-%m')";
            $prefix = $driver === 'sqlite'
                ? 'CAST(substr(a.code, 1, 4) AS INTEGER)'
                : 'CAST(SUBSTRING(a.code, 1, 4) AS SIGNED)';
            $payrollCodes = implode(',', array_map('intval', config('financial-health.payroll_gl_codes', [6300])));
            // COGS is the tenant's COGS account (5200 in the seeded chart),
            // the same account sales and POS entries debit. The whole
            // 5000-5999 cost group also swallowed purchases, freight and
            // the contra-expenses, so stock counted as sold the moment it
            // was bought rather than when it left the warehouse.
            $cogsAccountId = (int) app('currentTenant')->getAccountingSetting('cost_of_goods_sold_account_id');

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
                    SUM(CASE WHEN t.account_id = {$cogsAccountId} THEN CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS cogs,
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

            // 3b) Balance-sheet snapshot from the GL, all-time posted:
            // current assets (11xx) vs current liabilities (21xx) for
            // working capital; total liabilities, total equity (equity
            // accounts + revenue − expense, exactly what the balance sheet
            // reports) for debt-to-equity; and the AR balance (1130) for
            // days-sales-outstanding. One bounded query feeds all four.
            $liquidityRow = DB::table('transactions as t')
                ->join('journal_entries as je', 'je.id', '=', 't.journal_entry_id')
                ->join('accounts as a', 'a.id', '=', 't.account_id')
                ->where('je.tenant_id', $tenantId)
                ->where('je.status', JournalEntry::STATUS_POSTED)
                ->selectRaw("
                    SUM(CASE WHEN a.type = 'asset' AND {$prefix} BETWEEN 1100 AND 1199 THEN CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS current_assets,
                    SUM(CASE WHEN a.type = 'liability' AND {$prefix} BETWEEN 2100 AND 2199 THEN CASE t.type WHEN 'credit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS current_liabilities,
                    SUM(CASE WHEN a.type = 'liability' THEN CASE t.type WHEN 'credit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS total_liabilities,
                    SUM(CASE WHEN a.type = 'equity' THEN CASE t.type WHEN 'credit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS equity_accounts,
                    SUM(CASE WHEN a.type = 'revenue' THEN CASE t.type WHEN 'credit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS revenue_all_time,
                    SUM(CASE WHEN a.type = 'expense' THEN CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS expense_all_time,
                    SUM(CASE WHEN {$prefix} = 1130 THEN CASE t.type WHEN 'debit' THEN t.amount ELSE -t.amount END ELSE 0 END) AS ar_balance
                ")
                ->first();

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
            $ar = $this->aging('sales');
            $ap = $this->aging('po');

            // Align everything onto the month labels; cumulate cash into a balance
            $cash = $openingCash;
            $series = [
                'labels' => $labels,
                'complete' => $complete,
                'period' => $period,
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
            $collection = $this->collectionRatio($tenantId, $prefix);
            $series['collection_ratio'] = $collection['ratio'];
            $series['collection_measured'] = $collection['measured'];
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
            // Stock value and its drift from the ledger are point-in-time
            // like every balance-sheet figure: one valueOfStock() and one
            // settings-account balance, both bounded aggregates, and never
            // re-derived per indicator.
            $valuation = app(StockValuationService::class);
            $stockValue = $valuation->valueOfStock();
            $glInventory = $valuation->glInventoryBalance();
            $series['stock_value'] = $stockValue;
            $series['inventory_drift'] = $glInventory === null ? null : $glInventory - $stockValue;
            $series['current_assets'] = (float) ($liquidityRow->current_assets ?? 0);
            $series['current_liabilities'] = (float) ($liquidityRow->current_liabilities ?? 0);
            $series['total_liabilities'] = (float) ($liquidityRow->total_liabilities ?? 0);
            // Equity = equity accounts + accumulated profit (revenue − expense)
            $series['total_equity'] = (float) ($liquidityRow->equity_accounts ?? 0)
                + (float) ($liquidityRow->revenue_all_time ?? 0)
                - (float) ($liquidityRow->expense_all_time ?? 0);
            $series['ar_balance'] = (float) ($liquidityRow->ar_balance ?? 0);
            $series['cash_now'] = $cash;

            return $series;
        });
    }

    /**
     * AR/AP aging buckets + counts of 30+ day overdue documents.
     *
     * Read out of the same AgingReportService the report page uses, against
     * outstanding balances rather than the gross `total` column, so this
     * screen cannot quote a different figure for the same debt.
     */
    protected function aging(string $table): array
    {
        $aging = app(AgingReportService::class);

        $buckets = $table === 'sales'
            ? $aging->generateAgedReceivables()
            : $aging->generateAgedPayables();

        return $aging->summarize($buckets, true);
    }

    /**
     * Collection ratio: what share of booked receivables turned into money
     * (GL: AR credits vs AR debits over the window). Falls back to 1.0 for
     * cash businesses with no meaningful AR activity; the measured flag
     * tells the card that 1.0 is a fallback, not a flawless score, so an
     * empty tenant reads neutral instead of inventing a perfect result.
     *
     * @return array{ratio: float, measured: bool}
     */
    protected function collectionRatio(int $tenantId, string $prefix): array
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
            return [
                'ratio' => (float) config('financial-health.collection_ratio_fallback', 1.0),
                'measured' => false,
            ];
        }

        return [
            'ratio' => max(0.0, min(1.0, $collected / $booked)),
            'measured' => true,
        ];
    }

    // ────────────────────────────────────────────────────────────────
    // Indicators — the red/amber/green cards
    // ────────────────────────────────────────────────────────────────

    protected function indicators(array $s): array
    {
        $cfg = config('financial-health');
        $noise = $cfg['noise'];

        // Complete months: comparisons never use the in-progress month
        $complete = fn (string $key) => $this->completeValues($s, $key);

        // How many complete months this period spans. The default window is
        // the trailing 3; a filter makes it however many months were picked,
        // so every card below re-windows itself with no special cases.
        $window = max(1, (int) ($s['period']['window'] ?? 3));
        // {period} / {period_adj} slots so no reason string claims "3 months"
        // when the page is showing a different span.
        $pslots = $this->periodSlots($window);
        $why = fn (string $key, array $slots = []) => strtr(
            __('messages.fh_reason.'.$key),
            $slots + $pslots
        );

        // The period's revenue and expense — shared by the net-margin,
        // gross-margin, payroll-burden and DSO cards below.
        $rev3 = array_sum($this->lastN($complete('revenue_gl'), $window));
        $exp3 = array_sum($this->lastN($complete('expenses_gl'), $window));

        $out = [];

        // 1) CASH — real-time balance vs months of spending it covers
        $cashNow = $s['cash_now'];
        $expense3 = $this->avg($this->lastN($complete('expenses_gl'), $window));
        $cover = $expense3 > $noise['amount'] / 12 ? $cashNow / $expense3 : null;
        $status = $cover === null ? 'neutral'
            : ($cover >= $cfg['cash']['cover_good'] ? 'good'
                : ($cover >= $cfg['cash']['cover_warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'cash', 'label' => __('messages.cash'), 'icon' => 'ti-wallet',
            'value' => $cashNow, 'display' => 'money', 'status' => $status,
            'reason' => $cover === null
                ? __('messages.fh_reason.cash_cover_none')
                : $why('cash_cover', ['{cover}' => number_format($cover, 1)]),
            'delta' => null,
        ];

        // 2) REVENUE — final month of the period vs the window before it (subledger)
        $salesComplete = $complete('sales');
        $cur = end($salesComplete) ?: 0.0;
        $base = $this->avg($this->lastN(array_slice($salesComplete, 0, -1), $window));
        $delta = $this->pctDelta($cur, $base, $noise);
        $status = $delta === null ? 'neutral'
            : ($delta >= ($cfg['revenue']['good'] - 1) ? 'good'
                : ($delta >= ($cfg['revenue']['warn'] - 1) ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'revenue', 'label' => __('messages.revenue'), 'icon' => 'ti-report-money',
            'value' => $cur, 'display' => 'money', 'status' => $status,
            'reason' => $delta === null
                ? __('messages.fh_reason.revenue_new')
                : $why('revenue_vs_avg', ['{pct}' => number_format($delta * 100, 0)]),
            'delta' => $delta,
        ];

        // 3) PURCHASES — period spend vs period sales (spending discipline)
        $purch3 = array_sum($this->lastN($complete('purchases'), $window));
        $sales3 = array_sum($this->lastN($complete('sales'), $window));
        $ratio = $sales3 > $noise['amount'] ? $purch3 / $sales3 : null;
        $status = $ratio === null ? 'neutral'
            : ($ratio <= $cfg['purchases']['good'] ? 'good'
                : ($ratio <= $cfg['purchases']['warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'purchases', 'label' => __('messages.purchases'), 'icon' => 'ti-shopping-cart',
            'value' => $purch3, 'display' => 'money', 'status' => $status,
            'reason' => $ratio === null
                ? __('messages.fh_reason.purchases_none')
                : $why('purchases_vs_sales', ['{ratio}' => number_format($ratio, 2)]),
            'delta' => null,
        ];

        // 4) RECEIVABLES — share of outstanding that is 31+ days overdue
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
                    : $why('ar_overdue_share', ['{pct}' => number_format($arLateShare * 100, 0)])),
            'delta' => null,
        ];

        // 5) PAYABLES — overdue bills relative to the cash that must pay them
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
                : $why('ap_vs_cash', ['{ratio}' => number_format($apCover, 1)]),
            'delta' => null,
        ];

        // 6) PAYROLL — scheduled vs affordable
        $paid3 = array_sum($this->lastN($complete('payroll'), $window));
        $burden = $rev3 > $noise['amount'] ? $paid3 / $rev3 : null;
        $pending = $s['pending_payroll'];
        if ($pending !== null) {
            $status = 'warn';
            $reason = $why('payroll_pending',
                ['{amount}' => CurrencyHelper::format($pending['net'] ?: $s['committed_payroll'])]);
        } elseif ($burden !== null && $burden >= $cfg['payroll']['burden_danger']) {
            $status = 'danger';
            $reason = $why('payroll_burden', ['{pct}' => number_format($burden * 100, 0)]);
        } else {
            $status = $paid3 > 0 || $s['committed_payroll'] > 0 ? 'good' : 'neutral';
            $reason = $why('payroll_paid',
                ['{amount}' => CurrencyHelper::format($s['committed_payroll'] ?: $paid3)]);
        }
        $out[] = [
            'key' => 'payroll', 'label' => __('messages.payroll'), 'icon' => 'ti-users',
            'value' => $s['committed_payroll'] ?: $paid3, 'display' => 'money', 'status' => $status,
            'reason' => $reason,
            'delta' => null,
        ];

        // 7) INVENTORY TURNOVER — annualised COGS vs stock on the books.
        // The window drives the annualisation: 12/window scales whatever
        // span was picked back up to a year.
        $cogsWindow = array_sum($this->lastN($complete('cogs'), $window));
        $annualCogs = $cogsWindow * (12 / $window);
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
                : $why('turnover_line', [
                    '{turns}' => number_format($turns, 1),
                    '{months}' => number_format(12 / max($turns, 0.1), 0),
                ]),
            'delta' => null,
        ];

        // 8) DEBT-TO-EQUITY — total liabilities / total equity from the GL.
        // Equity is equity accounts + retained profit (revenue − expense),
        // exactly what the balance sheet reports. At 1.0 lenders and owners
        // hold equal claims; past 2.0 the business is mostly debt-funded.
        $equity = $s['total_equity'];
        $liabilities = $s['total_liabilities'];
        $de = $equity > $noise['amount'] / 12 ? $liabilities / $equity : null;
        $status = $de === null ? 'neutral'
            : ($de <= $cfg['debt_to_equity']['good'] ? 'good'
                : ($de <= $cfg['debt_to_equity']['warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'debt_to_equity', 'label' => __('messages.debt_to_equity'), 'icon' => 'ti-scale',
            'value' => $de, 'display' => 'ratio', 'status' => $status,
            'reason' => $de === null
                ? __('messages.fh_reason.de_none')
                : $why($de <= $cfg['debt_to_equity']['good'] ? 'de_low' : ($de <= $cfg['debt_to_equity']['warn'] ? 'de_watch' : 'de_high'), ['{ratio}' => number_format($de, 2)]),
            'delta' => null,
        ];

        // 9) GROSS MARGIN — (revenue − COGS) / revenue across the period.
        // The unit economics BEFORE overheads (distinct from the net margin
        // below, which is after every expense).
        $cogs3 = array_sum($this->lastN($complete('cogs'), $window));
        $grossMargin = $rev3 > $noise['amount'] ? ($rev3 - $cogs3) / $rev3 : null;
        $status = $grossMargin === null ? 'neutral'
            : ($grossMargin >= $cfg['gross_margin']['good'] ? 'good'
                : ($grossMargin >= $cfg['gross_margin']['warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'gross_margin', 'label' => __('messages.gross_margin'), 'icon' => 'ti-percentage',
            'value' => $grossMargin, 'display' => 'percent', 'status' => $status,
            'reason' => $grossMargin === null
                ? __('messages.fh_reason.gross_margin_none')
                : $why('gross_margin_line', ['{pct}' => number_format($grossMargin * 100, 1)]),
            'delta' => null,
        ];

        // 10) NET MARGIN — (revenue − every expense, incl. tax) / revenue
        // across the period. The true bottom line, shown beside gross margin
        // so the two can be read against each other.
        $margin = $rev3 > $noise['amount'] ? ($rev3 - $exp3) / $rev3 : null;
        $status = $margin === null ? 'neutral'
            : ($margin >= $cfg['margin']['good'] ? 'good'
                : ($margin >= $cfg['margin']['warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'net_margin', 'label' => __('messages.net_margin'), 'icon' => 'ti-trending-up',
            'value' => $margin, 'display' => 'percent', 'status' => $status,
            'reason' => $margin === null
                ? __('messages.fh_reason.margin_none')
                : $why('margin_line', ['{pct}' => number_format($margin * 100, 1)]),
            'delta' => null,
        ];

        // 11) LIQUIDITY — current assets (GL 11xx) vs current liabilities
        // (GL 21xx). Headline is working capital; the reason carries the
        // current ratio (how many times short-term bills are covered).
        $ca = $s['current_assets'];
        $cl = $s['current_liabilities'];
        $workingCapital = $ca - $cl;
        $currentRatio = $cl > $noise['amount'] / 12 ? $ca / $cl : null;
        $status = $currentRatio === null
            ? ($ca >= 0 ? 'good' : 'danger')
            : ($currentRatio >= $cfg['liquidity']['current_ratio_good'] ? 'good'
                : ($currentRatio >= $cfg['liquidity']['current_ratio_warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'liquidity', 'label' => __('messages.liquidity'), 'icon' => 'ti-scale',
            'value' => $workingCapital, 'display' => 'money', 'status' => $status,
            'reason' => $currentRatio === null
                ? $why('liquidity_no_bills', ['{capital}' => CurrencyHelper::format($workingCapital)])
                : $why('liquidity_line', [
                    '{capital}' => CurrencyHelper::format($workingCapital),
                    '{ratio}' => number_format($currentRatio, 1),
                ]),
            'delta' => null,
        ];

        // 12) DAYS SALES OUTSTANDING — how long today's invoices take to
        // become cash: AR balance ÷ period revenue, scaled to that window's
        // real day count. Needs booked revenue above the noise floor,
        // otherwise there is no pace to measure against.
        $arBalance = $s['ar_balance'];
        $windowDays = $this->spanInDays($s, $window);
        $dso = $rev3 > $noise['amount'] ? $arBalance / $rev3 * $windowDays : null;
        $status = $dso === null ? 'neutral'
            : ($dso <= $cfg['dso']['good'] ? 'good'
                : ($dso <= $cfg['dso']['warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'dso', 'label' => __('messages.dso'), 'icon' => 'ti-hourglass',
            'value' => $dso, 'display' => 'days', 'status' => $status,
            'reason' => $dso === null
                ? __('messages.fh_reason.dso_none')
                : $why($dso <= $cfg['dso']['good'] ? 'dso_fast' : ($dso <= $cfg['dso']['warn'] ? 'dso_watch' : 'dso_slow'), ['{days}' => number_format($dso, 0)]),
            'delta' => null,
        ];

        // 13) DAYS PAYABLE OUTSTANDING: how long open bills sit before the
        // money leaves. AP total ÷ purchases over the window, scaled to
        // that window's day count. Purchases below the noise floor give no
        // pace to measure (neutral); no open bills at all is a clean zero
        // (good, nothing outstanding to time).
        $dpoPurchases = array_sum($this->lastN($s['purchases'], $window));
        $dpo = null;
        if ($dpoPurchases > $noise['amount']) {
            $dpo = $apTotal > 0 ? $apTotal / $dpoPurchases * $windowDays : 0.0;
        }
        $status = $dpo === null ? 'neutral'
            : ($dpo <= $cfg['dpo']['good'] ? 'good'
                : ($dpo <= $cfg['dpo']['warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'dpo', 'label' => __('messages.dpo'), 'icon' => 'ti-calendar-dollar',
            'value' => $dpo, 'display' => 'days', 'status' => $status,
            'reason' => $dpo === null
                ? __('messages.fh_reason.dpo_none')
                : ($apTotal > 0
                    ? $why($dpo <= $cfg['dpo']['good'] ? 'dpo_good' : ($dpo <= $cfg['dpo']['warn'] ? 'dpo_watch' : 'dpo_late'), ['{days}' => number_format($dpo, 0)])
                    : __('messages.fh_reason.dpo_paid')),
            'delta' => null,
        ];

        // 14) COLLECTION RATIO: of everything invoiced over the trailing
        // ledger window, how much has actually been collected (GL 1130
        // credits ÷ debits). No invoiced activity above the noise floor
        // means there is nothing to convert, so it reads neutral instead
        // of a flawless 100% the fallback would claim.
        $collection = $s['collection_ratio'];
        $measured = (bool) ($s['collection_measured'] ?? false);
        $status = ! $measured ? 'neutral'
            : ($collection >= $cfg['collection']['good'] ? 'good'
                : ($collection >= $cfg['collection']['warn'] ? 'warn' : 'danger'));
        $out[] = [
            'key' => 'collection_ratio', 'label' => __('messages.collection_ratio'), 'icon' => 'ti-coins',
            'value' => $collection, 'display' => 'percent', 'status' => $status,
            'reason' => ! $measured
                ? __('messages.fh_reason.collection_none')
                : $why($collection >= $cfg['collection']['good'] ? 'collection_strong' : ($collection >= $cfg['collection']['warn'] ? 'collection_slow' : 'collection_bad'), ['{pct}' => number_format($collection * 100, 0)]),
            'delta' => null,
        ];

        // 15) STOCK VALUE: what the warehouse holds, checked against the
        // Inventory account. This is the only card whose whole job is to
        // say whether the two records of the same goods agree: the shelf
        // (batches plus unbatched units at buying price) and the posted
        // ledger. No Inventory account configured means there is nothing to
        // reconcile against, so it reads neutral rather than accusing a
        // tenant that keeps no books of being wrong.
        $stockValue = $s['stock_value'] ?? 0;
        $drift = $s['inventory_drift'] ?? null;
        $status = $drift === null ? 'neutral'
            : (abs($drift) > $noise['amount'] ? 'danger' : 'good');
        $out[] = [
            'key' => 'stock_value', 'label' => __('messages.stock_value'), 'icon' => 'ti-box',
            'value' => $stockValue, 'display' => 'money', 'status' => $status,
            'reason' => $drift === null
                ? __('messages.fh_reason.stock_no_books')
                : ($status === 'danger'
                    ? $why('stock_drift', ['{amount}' => CurrencyHelper::format(abs($drift))])
                    : $why('stock_matched')),
            'delta' => null,
        ];

        // 16) UNPOSTED ENTRIES: draft journal entries waiting to be posted.
        // Every GL-derived card above reads final only at zero: one draft
        // and the ledger numbers are still provisional, which is exactly
        // what the attention list already warns about.
        $draftJes = (int) ($s['draft_jes'] ?? 0);
        $status = $draftJes >= $cfg['draft_jes']['warn'] ? 'warn' : 'good';
        $out[] = [
            'key' => 'draft_jes', 'label' => __('messages.draft_entries'), 'icon' => 'ti-notebook',
            'value' => $draftJes, 'display' => 'count', 'status' => $status,
            'reason' => $draftJes === 0
                ? __('messages.fh_reason.draft_none')
                : $why('draft_pending', ['{count}' => number_format($draftJes, 0)]),
            'delta' => null,
        ];

        return $out;
    }

    /**
     * Day count of the LAST $window complete months — 92 for a trailing
     * quarter, 31 for a single month, and whatever the selected range adds
     * up to otherwise. Read from the labels so a filtered period measures
     * itself, not "the last three months".
     */
    protected function spanInDays(array $s, int $window): int
    {
        $completeLabels = array_values(array_filter(
            $s['labels'],
            fn ($label, $offset) => (bool) ($s['complete'][$offset] ?? false),
            ARRAY_FILTER_USE_BOTH
        ));
        $span = array_slice($completeLabels, -$window);
        if ($span === []) {
            $span = [Carbon::now()->startOfMonth()->subMonth()->format('Y-m')];
        }

        $start = Carbon::parse($span[0].'-01');
        $end = Carbon::parse(end($span).'-01')->endOfMonth();

        return max(1, (int) round($start->diffInDays($end)) + 1);
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
        $complete = fn (string $key) => $this->completeValues($s, $key);
        $window = max(1, (int) ($s['period']['window'] ?? 3));
        $salesComplete = $complete('sales');
        $avgSales3 = $this->avg($this->lastN(array_slice($salesComplete, 0, -1), $window));
        $lastSales = end($salesComplete) ?: 0.0;
        // Every line that names a span says the span actually on screen.
        $pslots = $this->periodSlots($window);

        $insights = [];

        $push = function (string $severity, string $type, array $slots = []) use (&$insights, $pslots) {
            $text = $this->template($type, $slots + $pslots);
            if ($text !== null) {
                $insights[] = ['severity' => $severity, 'type' => $type, 'text' => $text];
            }
        };

        // Cash verdict
        $cash = $byKey->get('cash');
        if ($cash['status'] === 'danger') {
            $push('danger', 'cash_critical', [
                '{cover}' => number_format($cash['value'] / max($this->avg($this->lastN($complete('expenses_gl'), $window)), 1), 1),
                '{amount}' => CurrencyHelper::format($cash['value']),
            ]);
        } elseif ($cash['status'] === 'good') {
            $expense3 = $this->avg($this->lastN($complete('expenses_gl'), $window));
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

        // Net margin
        $profit = $byKey->get('net_margin');
        if ($profit['status'] === 'danger') {
            $push('danger', 'margin_losing', ['{pct}' => number_format(abs($profit['value']) * 100, 1)]);
        } elseif ($profit['status'] === 'good') {
            $push('good', 'margin_healthy', ['{pct}' => number_format($profit['value'] * 100, 1)]);
        }

        // Purchases outpacing sales
        $purch = $byKey->get('purchases');
        if ($purch['status'] === 'danger') {
            $purch3 = array_sum($this->lastN($complete('purchases'), $window));
            $sales3 = array_sum($this->lastN($salesComplete, $window));
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
            $rev3 = array_sum($this->lastN($complete('revenue_gl'), $window));
            $paid3 = array_sum($this->lastN($complete('payroll'), $window));
            $push('danger', 'payroll_burden', ['{pct}' => number_format($rev3 > 0 ? ($paid3 / $rev3) * 100 : 0, 0)]);
        }

        // Turnover (only verdicts worth saying)
        $turnover = $byKey->get('turnover');
        if ($turnover['status'] === 'danger') {
            $push('danger', 'turnover_slow', ['{months}' => number_format(12 / max($turnover['value'] ?? 1, 0.1), 0)]);
        } elseif ($turnover['status'] === 'good') {
            $push('good', 'turnover_healthy', ['{turns}' => number_format($turnover['value'], 1)]);
        }

        // Gross margin (unit economics — only when it has a verdict)
        $gross = $byKey->get('gross_margin');
        if ($gross['status'] === 'danger' || $gross['status'] === 'warn') {
            $push($gross['status'] === 'danger' ? 'danger' : 'warn', 'gross_margin_thin',
                ['{pct}' => number_format(($gross['value'] ?? 0) * 100, 1)]);
        } elseif ($gross['status'] === 'good') {
            $push('good', 'gross_margin_healthy', ['{pct}' => number_format(($gross['value'] ?? 0) * 100, 1)]);
        }

        // Liquidity (working capital / current ratio) — ratio recomputed
        // straight from the series, like every other narrative figure.
        $liq = $byKey->get('liquidity');
        $cl = $s['current_liabilities'] ?? 0.0;
        $ratio = $cl > 0 ? ($s['current_assets'] ?? 0.0) / $cl : null;
        if ($liq['status'] === 'danger' || $liq['status'] === 'warn') {
            $push($liq['status'] === 'danger' ? 'danger' : 'warn', 'liquidity_tight',
                ['{ratio}' => number_format($ratio ?? 0, 1)]);
        } elseif ($liq['status'] === 'good' && $ratio !== null) {
            $push('good', 'liquidity_healthy', [
                '{ratio}' => number_format($ratio, 1),
                '{capital}' => CurrencyHelper::format($liq['value']),
            ]);
        }

        // Debt-to-equity — only the two verdicts worth saying; a middling
        // ratio is not an insight.
        $de = $byKey->get('debt_to_equity');
        if ($de['status'] === 'danger') {
            $push('danger', 'debt_heavy', ['{ratio}' => number_format($de['value'] ?? 0, 2)]);
        } elseif ($de['status'] === 'good' && ($de['value'] ?? 1) <= 0.5) {
            // Only call out genuinely light debt — a ratio near the good
            // threshold is not worth a line.
            $push('good', 'debt_light', ['{ratio}' => number_format($de['value'], 2)]);
        }

        // Days sales outstanding — only when collection is slow enough to
        // be the story; a fast pace is the expected default.
        $dso = $byKey->get('dso');
        if (in_array($dso['status'], ['danger', 'warn'], true) && ($dso['value'] ?? 0) > 0) {
            $push($dso['status'], 'dso_slow', [
                '{days}' => number_format($dso['value'], 0),
                '{amount}' => CurrencyHelper::format($s['ar_balance'] ?? 0),
            ]);
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
        // The forecast always reads CLOSED months — under a filter that is the
        // selected range plus its lead-in, unfiltered it is the usual series.
        $salesComplete = $this->completeValues($s, 'sales');
        // The projection looks forward from the period, not from the wall
        // clock, so a past range still names the month it actually predicts.
        $nextMonth = Carbon::parse(($s['period']['to'] ?? Carbon::now()->startOfMonth())->format('Y-m-01'))->addMonth();

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
            '{month}' => $nextMonth->translatedFormat('F'),
        ]);
        if ($tone === 'down' && $runway !== null && $runway < $cfg['runway']['warn']) {
            $statement .= ' '.$this->template('statement_runway', ['{runway}' => number_format($runway, 0)]);
        }

        return [
            'month' => $nextMonth->copy()->translatedFormat('F Y'),
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

        $window = max(1, (int) ($s['period']['window'] ?? 3));
        $expense3 = $this->avg($this->lastN($this->completeValues($s, 'expenses_gl'), $window));
        if ($expense3 > 0 && $cfg['cash']['cover_warn'] > $s['cash_now'] / $expense3) {
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

        // The books and the shelf disagreeing is not a rounding difference:
        // it means stock left or arrived without the ledger hearing about
        // it, which is exactly what the adjustment log records.
        $drift = $s['inventory_drift'] ?? null;
        if ($drift !== null && abs($drift) > $cfg['noise']['amount']) {
            $items[] = [
                'status' => 'danger', 'icon' => 'ti-box',
                'text' => __('messages.fh_attention.inventory_drift'),
                'amount' => abs($drift),
                'url' => route('admin.reports.adjustment-log'),
                'link_label' => __('messages.view_details'),
            ];
        }

        return $items;
    }

    // ────────────────────────────────────────────────────────────────
    // Small numeric helpers
    // ────────────────────────────────────────────────────────────────

    /**
     * The series values that fall in COMPLETE months only.
     *
     * Reads the mask series() computed; falls back to dropping the final
     * bucket for synthetic series that predate it (the threshold tests
     * drive indicators() straight from a hand-built array).
     */
    protected function completeValues(array $s, string $key): array
    {
        $values = $s[$key] ?? [];
        $mask = $s['complete'] ?? null;

        if (! is_array($mask) || count($mask) !== count($values)) {
            return array_slice($values, 0, -1);
        }

        return array_values(array_filter(
            $values,
            fn ($offset) => (bool) ($mask[$offset] ?? false),
            ARRAY_FILTER_USE_KEY
        ));
    }

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
