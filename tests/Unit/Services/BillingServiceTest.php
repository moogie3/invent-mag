<?php

namespace Tests\Unit\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;
use Carbon\Carbon;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class BillingServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected BillingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->service = new BillingService();
        
        (new PlanSeeder)->run();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(BillingService::class, $this->service);
    }

    #[Test]
    public function get_next_billing_period_returns_next_month()
    {
        $period = $this->service->getNextBillingPeriod();

        $this->assertArrayHasKey('start', $period);
        $this->assertArrayHasKey('end', $period);

        $start = Carbon::parse($period['start']);
        $this->assertTrue($start->isFuture() || $start->isToday());
    }

    #[Test]
    public function get_current_billing_period_returns_current_month()
    {
        $period = $this->service->getCurrentBillingPeriod();

        $this->assertArrayHasKey('start', $period);
        $this->assertArrayHasKey('end', $period);

        $start = Carbon::parse($period['start']);
        $end = Carbon::parse($period['end']);

        $this->assertTrue($start->isToday() || $start->isPast());
        $this->assertTrue($end->gte($start));
    }

    #[Test]
    public function get_days_remaining_in_period_returns_positive_integer()
    {
        $days = $this->service->getDaysRemainingInPeriod($this->tenant);

        $this->assertIsInt($days);
        $this->assertGreaterThanOrEqual(0, $days);
    }

    #[Test]
    public function get_total_days_in_period_returns_valid_month_length()
    {
        $days = $this->service->getTotalDaysInPeriod($this->tenant);

        $this->assertIsInt($days);
        $this->assertGreaterThanOrEqual(28, $days);
        $this->assertLessThanOrEqual(31, $days);
    }

    #[Test]
    public function calculate_upgrade_proration_calculates_correct_amount()
    {
        $starter = Plan::findBySlug('starter');
        $professional = Plan::findBySlug('professional');

        $daysRemaining = 15;
        $proration = $this->service->calculateUpgradeProration($starter, $professional, $daysRemaining);

        $starterDaily = $starter->getDailyPrice();
        $professionalDaily = $professional->getDailyPrice();
        $expected = ($professionalDaily - $starterDaily) * $daysRemaining;

        $this->assertEquals(round($expected, 2), $proration);
    }

    #[Test]
    public function calculate_upgrade_proration_returns_zero_for_downgrade()
    {
        $professional = Plan::findBySlug('professional');
        $starter = Plan::findBySlug('starter');

        $daysRemaining = 15;
        $proration = $this->service->calculateUpgradeProration($professional, $starter, $daysRemaining);

        $this->assertEquals(0, $proration);
    }

    #[Test]
    public function calculate_downgrade_credit_calculates_correct_amount()
    {
        $professional = Plan::findBySlug('professional');
        $starter = Plan::findBySlug('starter');

        $daysRemaining = 15;
        $credit = $this->service->calculateDowngradeCredit($professional, $starter, $daysRemaining);

        $professionalDaily = $professional->getDailyPrice();
        $starterDaily = $starter->getDailyPrice();
        $expected = ($professionalDaily - $starterDaily) * $daysRemaining;

        $this->assertEquals(round($expected, 2), $credit);
    }

    #[Test]
    public function calculate_downgrade_credit_returns_zero_for_upgrade()
    {
        $starter = Plan::findBySlug('starter');
        $professional = Plan::findBySlug('professional');

        $daysRemaining = 15;
        $credit = $this->service->calculateDowngradeCredit($starter, $professional, $daysRemaining);

        $this->assertEquals(0, $credit);
    }

    #[Test]
    public function get_plan_change_type_returns_upgrade_for_higher_tier()
    {
        $starter = Plan::findBySlug('starter');
        $professional = Plan::findBySlug('professional');

        $changeType = $this->service->getPlanChangeType($starter, $professional);

        $this->assertEquals('upgrade', $changeType);
    }

    #[Test]
    public function get_plan_change_type_returns_downgrade_for_lower_tier()
    {
        $professional = Plan::findBySlug('professional');
        $starter = Plan::findBySlug('starter');

        $changeType = $this->service->getPlanChangeType($professional, $starter);

        $this->assertEquals('downgrade', $changeType);
    }

    #[Test]
    public function get_plan_change_type_returns_same_for_equal_tier()
    {
        $starter = Plan::findBySlug('starter');
        $starter2 = Plan::findBySlug('starter');

        $changeType = $this->service->getPlanChangeType($starter, $starter2);

        $this->assertEquals('same', $changeType);
    }

    #[Test]
    public function process_plan_change_returns_same_for_identical_plan()
    {
        $starter = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starter);

        $result = $this->service->processPlanChange($this->tenant, $starter);

        $this->assertEquals('same', $result['type']);
        $this->assertEquals('none', $result['action']);
    }

    #[Test]
    public function process_plan_change_handles_immediate_upgrade()
    {
        $starter = Plan::findBySlug('starter');
        $professional = Plan::findBySlug('professional');

        $this->tenant->assignPlan($starter);

        $result = $this->service->processPlanChange($this->tenant, $professional);

        $this->tenant->refresh();

        $this->assertEquals('upgrade', $result['type']);
        $this->assertEquals('immediate', $result['action']);
        $this->assertEquals($professional->id, $this->tenant->plan_id);
        $this->assertArrayHasKey('proration_amount', $result);
    }

    #[Test]
    public function process_plan_change_schedules_downgrade()
    {
        $professional = Plan::findBySlug('professional');
        $starter = Plan::findBySlug('starter');

        $this->tenant->assignPlan($professional);

        $result = $this->service->processPlanChange($this->tenant, $starter);

        $this->tenant->refresh();

        $this->assertEquals('downgrade', $result['type']);
        $this->assertEquals('scheduled', $result['action']);
        $this->assertNotNull($this->tenant->pending_plan_id);
        $this->assertArrayHasKey('effective_date', $result);
        $this->assertArrayHasKey('credit_amount', $result);
    }

    #[Test]
    public function process_plan_change_clears_trial_on_upgrade()
    {
        $starter = Plan::findBySlug('starter');

        $this->tenant->update(['trial_ends_at' => now()->addDays(10)]);
        $this->tenant->assignPlan($starter);

        $professional = Plan::findBySlug('professional');

        $this->service->processPlanChange($this->tenant, $professional);

        $this->tenant->refresh();
        $this->assertNull($this->tenant->trial_ends_at);
    }

    #[Test]
    public function process_plan_change_sets_billing_period_if_not_set()
    {
        $starter = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starter);
        $this->tenant->update(['billing_period_start' => null, 'billing_period_end' => null]);

        $professional = Plan::findBySlug('professional');

        $this->service->processPlanChange($this->tenant, $professional);

        $this->tenant->refresh();
        $this->assertNotNull($this->tenant->billing_period_start);
        $this->assertNotNull($this->tenant->billing_period_end);
    }

    #[Test]
    public function cancel_scheduled_downgrade_cancels_pending_downgrade()
    {
        $professional = Plan::findBySlug('professional');
        $starter = Plan::findBySlug('starter');

        $this->tenant->assignPlan($professional);
        $this->tenant->update([
            'pending_plan_id' => $starter->id,
            'pending_plan_effective_at' => now()->addMonth()->toDateString() . ' 00:00:00',
            'proration_credit' => 50.00,
        ]);

        $result = $this->service->cancelScheduledDowngrade($this->tenant);

        $this->tenant->refresh();

        $this->assertTrue($result);
        $this->assertNull($this->tenant->pending_plan_id);
        $this->assertNull($this->tenant->pending_plan_effective_at);
        $this->assertNull($this->tenant->proration_credit);
    }

    #[Test]
    public function cancel_scheduled_downgrade_returns_false_when_no_pending_downgrade()
    {
        $starter = Plan::findBySlug('starter');
        $this->tenant->assignPlan($starter);

        $result = $this->service->cancelScheduledDowngrade($this->tenant);

        $this->assertFalse($result);
    }

    #[Test]
    public function calculate_upgrade_proration_uses_daily_pricing()
    {
        $starter = Plan::findBySlug('starter');
        $professional = Plan::findBySlug('professional');

        $proration30 = $this->service->calculateUpgradeProration($starter, $professional, 30);
        $proration15 = $this->service->calculateUpgradeProration($starter, $professional, 15);

        // 30 days should be approximately 2x 15 days (within rounding)
        $this->assertEquals($proration30, $proration15 * 2, 0.01);
    }
}