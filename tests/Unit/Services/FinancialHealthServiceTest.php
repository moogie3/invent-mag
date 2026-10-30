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

    public function test_indicators_cover_all_eight_health_metrics()
    {
        $overview = $this->service->getOverview();
        $keys = collect($overview['indicators'])->pluck('key')->all();

        $expected = ['cash', 'revenue', 'profit', 'purchases', 'receivables', 'payables', 'payroll', 'turnover'];
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
            $this->assertContains($ind['display'], ['money', 'percent', 'ratio']);
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

        $this->assertCount(8, $overview['indicators']);
        foreach ($overview['indicators'] as $ind) {
            $this->assertNotSame('danger', $ind['status'], "empty tenant should not flag {$ind['key']} critical");
        }
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
        // profit indicator is the GL-fed one (margin = revenue_gl vs
        // expenses_gl), so this posting should drive it to a full margin.
        $je = JournalEntry::create([
            'tenant_id' => $tenantId,
            'date' => now()->subMonth()->startOfMonth()->addDays(10)->toDateString(),
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

        $profit = collect($overview['indicators'])->firstWhere('key', 'profit');
        $this->assertNotNull($profit);
        // Revenue 5,000,000 with no expenses => 100% net margin.
        $this->assertEqualsWithDelta(1.0, $profit['value'], 0.001);
        $this->assertSame('good', $profit['status']);
    }
}
