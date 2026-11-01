<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\FinancialHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class FinancialHealthServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected FinancialHealthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->service = app(FinancialHealthService::class);
    }

    // ── Structure ───────────────────────────────────────────────────

    public function test_get_summary_returns_expected_keys()
    {
        $summary = $this->service->getSummary();

        $this->assertArrayHasKey('overall', $summary);
        $this->assertArrayHasKey('indicators', $summary);
        $this->assertArrayHasKey('narrative', $summary);
        $this->assertArrayHasKey('attention_count', $summary);
        $this->assertArrayHasKey('months', $summary);

        // The strip is capped at 3 narrative lines
        $this->assertLessThanOrEqual(3, count($summary['narrative']));
        $this->assertCount(6, $summary['months']);
    }

    public function test_summary_carries_chart_series_for_the_dashboard_strip()
    {
        $summary = $this->service->getSummary();

        $this->assertArrayHasKey('chart', $summary);
        // Same span as the month labels, so the chart's axis always lines up.
        $this->assertCount(count($summary['months']), $summary['chart']['cash']);
        $this->assertCount(count($summary['months']), $summary['chart']['revenue']);
    }

    public function test_get_overview_returns_expected_keys()
    {
        $overview = $this->service->getOverview();

        $this->assertArrayHasKey('overall', $overview);
        $this->assertArrayHasKey('indicators', $overview);
        $this->assertArrayHasKey('narrative', $overview);
        $this->assertArrayHasKey('outlook', $overview);
        $this->assertArrayHasKey('trends', $overview);
        $this->assertArrayHasKey('attention', $overview);
    }

    public function test_overall_is_one_of_the_valid_statuses()
    {
        $overview = $this->service->getOverview();

        $this->assertContains($overview['overall'], ['good', 'warn', 'danger', 'neutral']);
    }

    // ── Indicators ──────────────────────────────────────────────────

    public function test_indicators_cover_all_twelve_health_metrics()
    {
        $overview = $this->service->getOverview();
        $keys = collect($overview['indicators'])->pluck('key')->all();

        $expected = ['cash', 'revenue', 'purchases', 'receivables', 'payables', 'payroll', 'turnover', 'debt_to_equity', 'gross_margin', 'net_margin', 'liquidity', 'dso'];
        $this->assertEqualsCanonicalizing($expected, $keys);
    }

    public function test_each_indicator_has_the_shape_the_view_needs()
    {
        $overview = $this->service->getOverview();

        foreach ($overview['indicators'] as $ind) {
            $this->assertArrayHasKey('key', $ind);
            $this->assertArrayHasKey('label', $ind);
            $this->assertArrayHasKey('icon', $ind);
            $this->assertArrayHasKey('value', $ind);
            $this->assertArrayHasKey('display', $ind);
            $this->assertArrayHasKey('status', $ind);
            $this->assertArrayHasKey('reason', $ind);
            $this->assertArrayHasKey('delta', $ind);

            $this->assertContains($ind['status'], ['good', 'warn', 'danger', 'neutral']);
            $this->assertContains($ind['display'], ['money', 'percent', 'ratio', 'days']);
            $this->assertNotEmpty($ind['label']);
            $this->assertNotEmpty($ind['reason']);
        }
    }

    // ── Threshold logic ─────────────────────────────────────────────

    public function test_overall_reflects_the_worst_indicator()
    {
        // Build a synthetic indicator set and drive overallStatus directly.
        $service = new class extends FinancialHealthService
        {
            public function __construct() {}

            public function overall(array $indicators): string
            {
                return $this->overallStatus($indicators);
            }
        };

        $this->assertSame('danger', $service->overall([
            ['status' => 'good'], ['status' => 'danger'], ['status' => 'warn'],
        ]));
        $this->assertSame('warn', $service->overall([
            ['status' => 'good'], ['status' => 'warn'], ['status' => 'neutral'],
        ]));
        $this->assertSame('good', $service->overall([
            ['status' => 'good'], ['status' => 'neutral'],
        ]));
        $this->assertSame('neutral', $service->overall([]));
    }

    public function test_empty_tenant_yields_neutral_or_safe_statuses_not_crash()
    {
        // A brand-new tenant has no sales/GL history. The service must
        // degrade to neutral/good statuses and still return a full payload
        // (every "vs average" is null-guarded, not a division by zero).
        $overview = $this->service->getOverview();

        $this->assertCount(12, $overview['indicators']);
        foreach ($overview['indicators'] as $ind) {
            $this->assertNotSame('danger', $ind['status'], "empty tenant should not flag {$ind['key']} critical");
        }
    }

    public function test_gross_margin_and_liquidity_have_the_view_shape()
    {
        $overview = $this->service->getOverview();

        $gross = collect($overview['indicators'])->firstWhere('key', 'gross_margin');
        $liq = collect($overview['indicators'])->firstWhere('key', 'liquidity');

        $this->assertNotNull($gross, 'gross_margin indicator missing');
        $this->assertNotNull($liq, 'liquidity indicator missing');

        // Gross margin is a percentage; liquidity is a money figure.
        $this->assertSame('percent', $gross['display']);
        $this->assertSame('money', $liq['display']);
        $this->assertContains($gross['status'], ['good', 'warn', 'danger', 'neutral']);
        $this->assertContains($liq['status'], ['good', 'warn', 'danger', 'neutral']);
    }

    public function test_liquidity_working_capital_equals_assets_minus_liabilities()
    {
        $overview = $this->service->getOverview();
        $liq = collect($overview['indicators'])->firstWhere('key', 'liquidity');

        // On this empty tenant both GL buckets are zero, so working capital
        // must be exactly zero (not null, not negative).
        $this->assertEqualsWithDelta(0.0, $liq['value'], 0.01);
    }

    public function test_gross_margin_is_never_above_one()
    {
        $overview = $this->service->getOverview();
        $gross = collect($overview['indicators'])->firstWhere('key', 'gross_margin');

        if ($gross['value'] !== null) {
            $this->assertLessThanOrEqual(1.0, $gross['value']);
            $this->assertGreaterThanOrEqual(-1.0, $gross['value']);
        }
    }

    public function test_debt_to_equity_and_dso_have_the_view_shape()
    {
        $overview = $this->service->getOverview();

        $de = collect($overview['indicators'])->firstWhere('key', 'debt_to_equity');
        $dso = collect($overview['indicators'])->firstWhere('key', 'dso');

        $this->assertNotNull($de, 'debt_to_equity indicator missing');
        $this->assertNotNull($dso, 'dso indicator missing');

        // D/E is a ratio; DSO is a day count.
        $this->assertSame('ratio', $de['display']);
        $this->assertSame('days', $dso['display']);
        $this->assertContains($de['status'], ['good', 'warn', 'danger', 'neutral']);
        $this->assertContains($dso['status'], ['good', 'warn', 'danger', 'neutral']);
    }

    public function test_debt_to_equity_is_liabilities_over_equity()
    {
        // Drive indicators() directly with a synthetic series so the ratio
        // and its status mapping are both pinned to known numbers. Equity
        // must clear the noise floor (~8.3k) or the block reports null.
        $indicators = $this->indicatorsFrom([
            'total_liabilities' => 50000.0,
            'total_equity' => 100000.0,
        ]);
        $de = collect($indicators)->firstWhere('key', 'debt_to_equity');

        $this->assertEqualsWithDelta(0.5, $de['value'], 0.001);
        $this->assertSame('good', $de['status']);

        // At the good/warn boundary (exactly 1.0) it is still good; above it
        // turns warn; past 2.0 it is danger.
        $this->assertSame('good', collect($this->indicatorsFrom([
            'total_liabilities' => 100000.0, 'total_equity' => 100000.0,
        ]))->firstWhere('key', 'debt_to_equity')['status']);
        $this->assertSame('warn', collect($this->indicatorsFrom([
            'total_liabilities' => 150000.0, 'total_equity' => 100000.0,
        ]))->firstWhere('key', 'debt_to_equity')['status']);
        $this->assertSame('danger', collect($this->indicatorsFrom([
            'total_liabilities' => 300000.0, 'total_equity' => 100000.0,
        ]))->firstWhere('key', 'debt_to_equity')['status']);
    }

    public function test_dso_scales_ar_by_recent_revenue_and_window()
    {
        // Trailing 3-month revenue (in the last COMPLETE month's slot — the
        // series drops the in-progress month) of 200k with an AR balance of
        // 200k gives a day count equal to the window length (~92 days).
        $indicators = $this->indicatorsFrom([
            'revenue_gl' => [0, 0, 0, 0, 200000.0, 0],
            'ar_balance' => 200000.0,
        ]);
        $dso = collect($indicators)->firstWhere('key', 'dso');

        $this->assertNotNull($dso['value']);
        $this->assertEqualsWithDelta(92.0, $dso['value'], 1.5);
        $this->assertSame('danger', $dso['status']); // > 90 warn threshold

        // Quadrupling recent revenue cuts the days outstanding to a quarter.
        $fast = collect($this->indicatorsFrom([
            'revenue_gl' => [0, 0, 0, 0, 800000.0, 0],
            'ar_balance' => 200000.0,
        ]))->firstWhere('key', 'dso');
        $this->assertEqualsWithDelta($dso['value'] / 4, $fast['value'], 1.0);
        $this->assertSame('good', $fast['status']);
    }

    /**
     * Build a full zeroed series, apply overrides, and run indicators()
     * against it — the same seam the threshold tests use for overallStatus.
     */
    private function indicatorsFrom(array $overrides): array
    {
        $service = new class extends FinancialHealthService
        {
            public function __construct() {}

            public function withSeries(array $s): array
            {
                return $this->indicators($s);
            }
        };

        $bucket = ['current' => 0.0, '1-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0, 'total_overdue' => 0.0, 'count_30plus' => 0];
        $base = [
            'cash_now' => 0.0, 'revenue_gl' => [0, 0, 0, 0, 0, 0], 'expenses_gl' => [0, 0, 0, 0, 0, 0],
            'purchases' => [0, 0, 0, 0, 0, 0], 'sales' => [0, 0, 0, 0, 0, 0], 'cogs' => [0, 0, 0, 0, 0, 0],
            'payroll' => [0, 0, 0, 0, 0, 0], 'ar' => $bucket, 'ap' => $bucket,
            'pending_payroll' => null, 'committed_payroll' => 0.0, 'inventory_balance' => 0.0,
            'current_assets' => 0.0, 'current_liabilities' => 0.0,
            'total_liabilities' => 0.0, 'total_equity' => 0.0, 'ar_balance' => 0.0,
            // spanInDays() reads the real month labels: five complete months
            // plus the in-progress one (last slot), like the live series.
            'labels' => collect(range(5, 0))->map(
                fn ($back) => now()->startOfMonth()->subMonths($back)->format('Y-m')
            )->all(),
            'complete' => [true, true, true, true, true, false],
            'period' => ['window' => 3],
        ];

        return $service->withSeries(array_merge($base, $overrides));
    }

    // ── Outlook chain ───────────────────────────────────────────────

    public function test_outlook_chain_is_internally_consistent()
    {
        $outlook = $this->service->getOverview()['outlook'];

        $expected = [
            'month', 'revenue', 'revenue_method', 'revenue_confidence', 'collection_ratio',
            'cash_in', 'purchases', 'payroll', 'operations', 'expenses', 'net',
            'cash_now', 'projected', 'range_low', 'range_high', 'range_available',
            'runway', 'tone', 'statement',
        ];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $outlook, "outlook missing {$key}");
        }

        // projected = cash_now + net, and net = cash_in − expenses
        $this->assertEqualsWithDelta(
            $outlook['cash_now'] + $outlook['net'],
            $outlook['projected'],
            0.01
        );
        $this->assertEqualsWithDelta(
            $outlook['cash_in'] - $outlook['expenses'],
            $outlook['net'],
            0.01
        );
        // cash_in = revenue × collection ratio
        $this->assertEqualsWithDelta(
            $outlook['revenue'] * $outlook['collection_ratio'],
            $outlook['cash_in'],
            0.01
        );

        $this->assertContains($outlook['tone'], ['up', 'down', 'flat']);
        $this->assertContains($outlook['revenue_method'], ['holt_winters', 'weighted_trend']);
        $this->assertNotEmpty($outlook['statement']);
        $this->assertGreaterThanOrEqual(0, $outlook['revenue_confidence']);
        $this->assertLessThanOrEqual(95, $outlook['revenue_confidence']);
    }

    public function test_outlook_range_is_hidden_until_two_scenario_months_exist()
    {
        $outlook = $this->service->getOverview()['outlook'];

        // On an empty tenant there are no complete scenario months, so the
        // best/worst range must be suppressed rather than shown as a
        // false-precision band.
        $this->assertFalse($outlook['range_available']);
    }

    // ── Attention list ──────────────────────────────────────────────

    public function test_attention_items_carry_a_drilldown_url()
    {
        $attention = $this->service->getOverview()['attention'];

        $this->assertIsArray($attention);
        foreach ($attention as $item) {
            $this->assertArrayHasKey('status', $item);
            $this->assertArrayHasKey('text', $item);
            $this->assertArrayHasKey('amount', $item);
            $this->assertArrayHasKey('url', $item);
            $this->assertNotEmpty($item['url']);
        }
    }

    // ── Period comparison (modal + "Compare all") ───────────────────

    public function test_comparison_returns_twelve_rows_split_flow_and_locked()
    {
        $comparison = $this->service->getComparison();

        $this->assertCount(12, $comparison['rows']);

        $flow = collect($comparison['rows'])->where('comparable', true);
        $locked = collect($comparison['rows'])->where('comparable', false);

        // Flow metrics carry a real monthly series; the balance-sheet
        // snapshots are point-in-time and must come back locked.
        $this->assertEqualsCanonicalizing(
            ['cash', 'revenue', 'net_margin', 'gross_margin', 'purchases'],
            $flow->pluck('key')->all()
        );
        $this->assertCount(7, $locked);
        $this->assertContains('dso', $locked->pluck('key')->all());
        $this->assertContains('debt_to_equity', $locked->pluck('key')->all());

        // Every locked row explains WHY it does not move.
        foreach ($locked as $row) {
            $this->assertNotNull($row['lock_reason']);
            $this->assertNull($row['delta']);
            $this->assertNull($row['trend']);
            $this->assertSame([], $row['spark']);
        }
    }

    public function test_comparison_is_apples_to_apples_single_complete_months()
    {
        $comparison = $this->service->getComparison();
        $labels = $comparison['labels'];

        // Compared months must be the last COMPLETE month and the one
        // before it — never the in-progress final bucket.
        $this->assertSame($labels[count($labels) - 2], $comparison['compared']['current']);
        $this->assertSame($labels[count($labels) - 3], $comparison['compared']['previous']);

        $revenue = collect($comparison['rows'])->firstWhere('key', 'revenue');
        $this->assertTrue($revenue['comparable']);
        // Both sides are single months drawn from the same monthly series,
        // and the sparkline stops at the last complete month.
        $this->assertNotNull($revenue['current']);
        $this->assertNotNull($revenue['previous']);
        $this->assertCount(count($labels) - 1, $revenue['spark']);
        $this->assertEqualsWithDelta(
            $revenue['current'] - $revenue['previous'],
            $revenue['delta'],
            0.001
        );
        $this->assertContains($revenue['trend'], ['up', 'down', 'flat']);
    }

    public function test_comparison_margins_are_monthly_not_trailing_averages()
    {
        // Seed two COMPLETE months with deliberately different margins so
        // the modal's two sides can be pinned to known numbers. If these
        // were the card's trailing-3-month figure, both sides would match.
        $tenantId = app('currentTenant')->id;
        $revAccount = Account::create(['code' => '9001', 'name' => 'R', 'type' => 'revenue', 'tenant_id' => $tenantId]);
        $cashAccount = Account::create(['code' => '9002', 'name' => 'C', 'type' => 'asset', 'tenant_id' => $tenantId]);
        $expAccount = Account::create(['code' => '9003', 'name' => 'E', 'type' => 'expense', 'tenant_id' => $tenantId]);

        // Month −2: revenue 400k, expenses 300k → 25% net margin.
        // Month −1: revenue 400k, expenses 100k → 75% net margin.
        // Anchor to the 1st before subtracting so both land in complete
        // months even when "today" is the last day of the current month.
        $plan = [
            [now()->startOfMonth()->subMonths(2)->addDays(10), 300000],
            [now()->startOfMonth()->subMonth()->addDays(10), 100000],
        ];
        foreach ($plan as [$date, $expense]) {
            $je = JournalEntry::create([
                'tenant_id' => $tenantId, 'date' => $date->toDateString(),
                'description' => 'margin seeding', 'status' => 'posted',
            ]);
            DB::table('transactions')->insert([
                ['journal_entry_id' => $je->id, 'tenant_id' => $tenantId, 'account_id' => $cashAccount->id, 'type' => 'debit', 'amount' => 400000, 'created_at' => now(), 'updated_at' => now()],
                ['journal_entry_id' => $je->id, 'tenant_id' => $tenantId, 'account_id' => $revAccount->id, 'type' => 'credit', 'amount' => 400000, 'created_at' => now(), 'updated_at' => now()],
                ['journal_entry_id' => $je->id, 'tenant_id' => $tenantId, 'account_id' => $expAccount->id, 'type' => 'debit', 'amount' => $expense, 'created_at' => now(), 'updated_at' => now()],
                ['journal_entry_id' => $je->id, 'tenant_id' => $tenantId, 'account_id' => $cashAccount->id, 'type' => 'credit', 'amount' => $expense, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $this->app->forgetInstance(FinancialHealthService::class);
        $comparison = app(FinancialHealthService::class)->getComparison();

        $net = collect($comparison['rows'])->firstWhere('key', 'net_margin');
        $this->assertTrue($net['comparable']);
        // Each side is ONE month: 75% last month vs 25% the month before —
        // not the blended trailing-3-month margin the card itself shows.
        $this->assertEqualsWithDelta(0.75, $net['current'], 0.001);
        $this->assertEqualsWithDelta(0.25, $net['previous'], 0.001);
        $this->assertEqualsWithDelta(0.50, $net['delta'], 0.001);
        $this->assertSame('up', $net['trend']);

        // Revenue is flat (400k both months) while margins diverge, which
        // only holds if margins are computed per month, not from revenue.
        $rev = collect($comparison['rows'])->firstWhere('key', 'revenue');
        $this->assertEqualsWithDelta(400000, $rev['current'], 0.001);
        $this->assertSame('flat', $rev['trend']);
    }

    // ── Driver awareness ────────────────────────────────────────────

    public function test_service_computes_on_the_sqlite_test_driver()
    {
        // The whole series() query is driver-aware (strftime vs
        // DATE_FORMAT). Reaching this far on sqlite proves the branches
        // are exercised, not just the MySQL path.
        $this->assertSame('sqlite', \DB::connection()->getDriverName());

        $summary = $this->service->getSummary();

        $this->assertCount(6, $summary['months']);
        $this->assertNotEmpty($summary['indicators']);
    }

    // ── Revenue/GL sanity with real postings ────────────────────────

    public function test_posted_gl_revenue_drives_the_profit_margin()
    {
        $tenantId = app('currentTenant')->id;

        $revAccount = Account::create([
            'code' => '9001', 'name' => 'Test Revenue', 'type' => 'revenue', 'tenant_id' => $tenantId,
        ]);
        $cashAccount = Account::create([
            'code' => '9002', 'name' => 'Test Cash', 'type' => 'asset', 'tenant_id' => $tenantId,
        ]);

        // Post a large revenue entry in a COMPLETE month (last month). The
        // net-margin indicator is the GL-fed one (margin = revenue_gl vs
        // expenses_gl), so this posting should drive it to a full margin.
        // Anchor to the 1st before subtracting so this stays in a complete
        // month even when "today" is the last day of the current month.
        $je = JournalEntry::create([
            'tenant_id' => $tenantId,
            'date' => now()->startOfMonth()->subMonth()->addDays(10)->toDateString(),
            'description' => 'Financial health test revenue',
            'status' => 'posted',
        ]);
        DB::table('transactions')->insert([
            ['journal_entry_id' => $je->id, 'tenant_id' => $tenantId, 'account_id' => $cashAccount->id, 'type' => 'debit', 'amount' => 5000000, 'created_at' => now(), 'updated_at' => now()],
            ['journal_entry_id' => $je->id, 'tenant_id' => $tenantId, 'account_id' => $revAccount->id, 'type' => 'credit', 'amount' => 5000000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Clear any memoised payload so the new posting is picked up.
        $this->app->forgetInstance(FinancialHealthService::class);
        $overview = app(FinancialHealthService::class)->getOverview();

        $profit = collect($overview['indicators'])->firstWhere('key', 'net_margin');
        $this->assertNotNull($profit);
        // Revenue 5,000,000 with no expenses => 100% net margin.
        $this->assertEqualsWithDelta(1.0, $profit['value'], 0.001);
        $this->assertSame('good', $profit['status']);
    }

    // ── Period filter ───────────────────────────────────────────────

    public function test_default_period_is_the_rolling_window()
    {
        $period = $this->service->getOverview()['period'];

        $this->assertTrue($period['default']);
        $this->assertNull($period['preset']);
        $this->assertSame(6, $period['months']);
        $this->assertSame(3, $period['window']);
        // Default view compares month to month — not the whole window.
        $this->assertSame(1, $period['comparison_window']);
    }

    public function test_preset_resolves_to_a_real_window_and_is_never_the_future()
    {
        $period = $this->service->getOverview('last_3')['period'];

        $this->assertFalse($period['default']);
        $this->assertSame('last_3', $period['preset']);
        $this->assertSame(3, $period['months']);
        $this->assertSame(3, $period['window']);
        $this->assertSame(3, $period['comparison_window']);
        // Ends at the last COMPLETE month, never the one in progress.
        $this->assertTrue($period['to']->equalTo(now()->startOfMonth()->subMonth()));
        $this->assertTrue($period['from']->equalTo($period['to']->copy()->subMonths(2)));
        // The prior window loads one extra window early, so both sides of
        // the comparison always have real data behind them.
        $this->assertTrue($period['series_from']->equalTo($period['from']->copy()->subMonths(3)));
        $this->assertSame(6, $period['series_months']);
    }

    public function test_custom_range_is_ordered_and_capped()
    {
        // Reversed input is pulled into order (from can never sit after to).
        $period = $this->service->getOverview(null, '2026-08', '2026-05')['period'];
        $this->assertTrue($period['from']->equalTo($period['to']));
        $this->assertSame('2026-05', $period['to']->format('Y-m'));

        // A future "to" is clamped to the last complete month.
        $future = $this->service->getOverview(null, '2026-01', now()->format('Y-m'))['period'];
        $this->assertTrue($future['to']->equalTo(now()->startOfMonth()->subMonth()));

        // Oversized ranges are capped at period_max_months.
        $cap = (int) config('financial-health.period_max_months');
        $huge = $this->service->getOverview(null, '2000-01', '2026-12')['period'];
        $this->assertSame($cap, $huge['months']);
    }

    public function test_unknown_preset_falls_back_to_the_default_window()
    {
        $period = $this->service->getOverview('definitely_not_a_preset')['period'];

        $this->assertTrue($period['default']);
        $this->assertNull($period['preset']);
    }

    public function test_reasons_name_the_span_actually_on_screen()
    {
        // Seed enough posted revenue for the margin reason to exist at all
        // (empty books answer with the "not enough activity" variant).
        $tenantId = $this->tenant->id;
        $cashAccount = Account::create([
            'code' => '9101', 'name' => 'Period Test Cash', 'type' => 'asset', 'tenant_id' => $tenantId,
        ]);
        $revAccount = Account::create([
            'code' => '9102', 'name' => 'Period Test Revenue', 'type' => 'revenue', 'tenant_id' => $tenantId,
        ]);
        $je = JournalEntry::create([
            'tenant_id' => $tenantId,
            'date' => now()->startOfMonth()->subMonth()->addDays(10)->toDateString(),
            'description' => 'Financial health period test revenue',
            'status' => 'posted',
        ]);
        DB::table('transactions')->insert([
            ['journal_entry_id' => $je->id, 'tenant_id' => $tenantId, 'account_id' => $cashAccount->id, 'type' => 'debit', 'amount' => 2500000, 'created_at' => now(), 'updated_at' => now()],
            ['journal_entry_id' => $je->id, 'tenant_id' => $tenantId, 'account_id' => $revAccount->id, 'type' => 'credit', 'amount' => 2500000, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->app->forgetInstance(FinancialHealthService::class);

        // With the 3-month window the reason says "3 months"; with the
        // one-month preset it must say "a single month" instead — the
        // number in the copy is never hardcoded.
        $three = collect(app(FinancialHealthService::class)->getOverview('last_3')['indicators'])
            ->firstWhere('key', 'net_margin');
        $this->app->forgetInstance(FinancialHealthService::class);
        $single = collect(app(FinancialHealthService::class)->getOverview('last_month')['indicators'])
            ->firstWhere('key', 'net_margin');

        $this->assertStringContainsString('3', $three['reason']);
        $this->assertStringNotContainsString('3', $single['reason']);
    }

    public function test_filtered_comparison_compares_equal_windows()
    {
        $comparison = $this->service->getComparison('last_3');

        // 5 flow rows carry a real delta; 7 balance-sheet snapshots stay
        // locked with a reason, whatever the filter says.
        $rows = collect($comparison['rows']);
        $this->assertSame(5, $rows->where('comparable', true)->count());
        $this->assertSame(7, $rows->where('comparable', false)->count());
        $rows->where('comparable', false)->each(function ($row) {
            $this->assertNotNull($row['lock_reason']);
            $this->assertNull($row['delta']);
        });

        // Each side spans the full selected window: 3 months back-to-back.
        $compared = $comparison['compared'];
        $current = \Illuminate\Support\Carbon::parse($compared['current']);
        $currentFrom = \Illuminate\Support\Carbon::parse($compared['current_from']);
        $previous = \Illuminate\Support\Carbon::parse($compared['previous']);
        $previousFrom = \Illuminate\Support\Carbon::parse($compared['previous_from']);
        $this->assertSame(2, (int) $currentFrom->diffInMonths($current));
        $this->assertSame(2, (int) $previousFrom->diffInMonths($previous));
        // And the prior window ends exactly where the current one begins.
        $this->assertSame(1, (int) $previous->diffInMonths($currentFrom));
    }

    public function test_filtered_comparison_carries_month_labels_for_the_sparkline()
    {
        $comparison = $this->service->getComparison('last_3');

        $cash = collect($comparison['rows'])->firstWhere('key', 'cash');
        $this->assertNotEmpty($cash['spark']);
        // One label per plotted point, so the chart can name each month.
        $this->assertCount(count($cash['spark']), $cash['spark_labels']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', end($cash['spark_labels']));
    }

    public function test_indonesian_strings_fill_every_period_placeholder()
    {
        // The locale lives on the user, not the URL, so the Indonesian side
        // is asserted here: every period-bearing string must translate AND
        // leave no raw {placeholder} behind once the service fills it.
        \Illuminate\Support\Facades\App::setLocale('id');

        // Seed posted revenue so the margin reasons (the period-bearing
        // ones) actually render instead of the "no activity" variants.
        $tenantId = $this->tenant->id;
        $cashAccount = Account::create([
            'code' => '9201', 'name' => 'Locale Test Cash', 'type' => 'asset', 'tenant_id' => $tenantId,
        ]);
        $revAccount = Account::create([
            'code' => '9202', 'name' => 'Locale Test Revenue', 'type' => 'revenue', 'tenant_id' => $tenantId,
        ]);
        $je = JournalEntry::create([
            'tenant_id' => $tenantId,
            'date' => now()->startOfMonth()->subMonths(2)->addDays(10)->toDateString(),
            'description' => 'Financial health locale test revenue',
            'status' => 'posted',
        ]);
        DB::table('transactions')->insert([
            ['journal_entry_id' => $je->id, 'tenant_id' => $tenantId, 'account_id' => $cashAccount->id, 'type' => 'debit', 'amount' => 3000000, 'created_at' => now(), 'updated_at' => now()],
            ['journal_entry_id' => $je->id, 'tenant_id' => $tenantId, 'account_id' => $revAccount->id, 'type' => 'credit', 'amount' => 3000000, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->app->forgetInstance(FinancialHealthService::class);

        $reasons = collect(app(FinancialHealthService::class)->getOverview('last_3')['indicators'])
            ->pluck('reason')
            ->implode(' | ');
        $this->assertStringContainsString('3 bulan', $reasons);
        $this->assertStringNotContainsString('{', $reasons);

        // The filter UI strings translate too (chip + toggle + presets).
        $this->assertStringContainsString('Menampilkan', __('messages.period_showing', ['range' => 'Jul 2026 — Sep 2026']));
        $this->assertStringNotContainsString('{', __('messages.period_showing', ['range' => 'Jul 2026 — Sep 2026']));
        $this->assertNotSame('Default (rolling window)', __('messages.fh_period_default'));
        $this->assertNotSame('Hide', __('messages.hide'));

        \Illuminate\Support\Facades\App::setLocale('en');
    }
}
