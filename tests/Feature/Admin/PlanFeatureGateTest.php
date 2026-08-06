<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PlanFeatureGateTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private const PROFESSIONAL_TIER_FEATURES = [
        'stock_opname',
        'accounting',
        'crm',
        'sales_pipeline',
        'financial_reports',
        'multi_currency',
        'cash_flow',
        'hr',
        'quotations',
        'ppn',
        'bank_reconciliation',
    ];

    private const ENTERPRISE_ONLY_FEATURES = [
        'sales_forecasting',
        'api_access',
        'audit_compliance',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    private function assignPlan(string $slug): void
    {
        $plan = Plan::findBySlug($slug);
        $this->assertNotNull($plan, "Plan '{$slug}' not found");

        $this->tenant->update([
            'plan_id' => $plan->id,
            'plan_status' => 'active',
            'plan_expires_at' => null,
            'trial_ends_at' => null,
            'billing_period_start' => null,
            'billing_period_end' => null,
        ]);

        $this->tenant->refresh();
        $this->tenant->makeCurrent();
    }

    private function assertBlockedOnPlan(string $planSlug, string $url): void
    {
        $this->assignPlan($planSlug);

        $response = $this->get($url);

        $response->assertStatus(302);
        $this->assertStringContainsString('admin/settings/plan/upgrade', $response->headers->get('Location'));
    }

    private function assertAllowedOnPlan(string $planSlug, string $url): void
    {
        $this->assignPlan($planSlug);

        $this->get($url)->assertStatus(200);
    }

    public function test_plan_feature_matrix_gates_new_features_by_tier(): void
    {
        $starter = Plan::findBySlug('starter');
        $professional = Plan::findBySlug('professional');
        $enterprise = Plan::findBySlug('enterprise');

        $this->assertNotNull($starter);
        $this->assertNotNull($professional);
        $this->assertNotNull($enterprise);

        foreach (self::PROFESSIONAL_TIER_FEATURES as $feature) {
            $this->assertFalse($starter->hasFeature($feature), "Starter should NOT include '{$feature}'");
            $this->assertTrue($professional->hasFeature($feature), "Professional should include '{$feature}'");
            $this->assertTrue($enterprise->hasFeature($feature), "Enterprise should include '{$feature}'");
        }

        foreach (self::ENTERPRISE_ONLY_FEATURES as $feature) {
            $this->assertFalse($starter->hasFeature($feature), "Starter should NOT include '{$feature}'");
            $this->assertFalse($professional->hasFeature($feature), "Professional should NOT include '{$feature}'");
            $this->assertTrue($enterprise->hasFeature($feature), "Enterprise should include '{$feature}'");
        }
    }

    public function test_stock_opname_is_gated_by_plan(): void
    {
        $this->assertBlockedOnPlan('starter', route('admin.stock-opname.index'));
        $this->assertAllowedOnPlan('professional', route('admin.stock-opname.index'));
    }

    public function test_accounting_is_gated_by_plan(): void
    {
        $this->assertBlockedOnPlan('starter', route('admin.accounting.trial_balance'));
        $this->assertAllowedOnPlan('professional', route('admin.accounting.trial_balance'));
    }

    public function test_sales_pipeline_is_gated_by_plan(): void
    {
        $this->assertBlockedOnPlan('starter', route('admin.sales_pipeline.index'));
        $this->assertAllowedOnPlan('professional', route('admin.sales_pipeline.index'));
    }

    public function test_financial_reports_are_gated_by_plan(): void
    {
        $this->assertBlockedOnPlan('starter', route('admin.reports.income-statement'));
        $this->assertAllowedOnPlan('professional', route('admin.reports.income-statement'));
    }

    public function test_cash_flow_is_gated_by_plan(): void
    {
        $this->assertBlockedOnPlan('starter', route('admin.reports.cash-flow'));
        $this->assertAllowedOnPlan('professional', route('admin.reports.cash-flow'));
    }

    public function test_multi_currency_is_gated_by_plan(): void
    {
        $this->assertBlockedOnPlan('starter', route('admin.setting.currency.edit'));
        $this->assertAllowedOnPlan('professional', route('admin.setting.currency.edit'));
    }

    public function test_hr_module_is_gated_by_plan(): void
    {
        $this->assertBlockedOnPlan('starter', route('admin.hr.employee.index'));
        $this->assertAllowedOnPlan('professional', route('admin.hr.employee.index'));
    }

    public function test_quotations_are_gated_by_plan(): void
    {
        $this->assertBlockedOnPlan('starter', route('admin.quotations'));
        $this->assertAllowedOnPlan('professional', route('admin.quotations'));
    }

    public function test_efaktur_ppn_is_gated_by_plan(): void
    {
        $this->assertBlockedOnPlan('starter', route('admin.efaktur.index'));
        $this->assertAllowedOnPlan('professional', route('admin.efaktur.index'));
    }

    public function test_bank_reconciliation_is_gated_by_plan(): void
    {
        $this->assertBlockedOnPlan('starter', route('admin.bank-reconciliation.index'));
        $this->assertAllowedOnPlan('professional', route('admin.bank-reconciliation.index'));
    }

    public function test_audit_log_is_gated_to_enterprise(): void
    {
        $this->assertBlockedOnPlan('professional', route('admin.audit-log.index'));
        $this->assertAllowedOnPlan('enterprise', route('admin.audit-log.index'));
    }

    public function test_sales_forecasting_is_gated_to_enterprise(): void
    {
        $this->assignPlan('professional');
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $this->assertNull($response->viewData('salesForecast'));

        $this->assignPlan('enterprise');
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $this->assertNotNull($response->viewData('salesForecast'));
    }

    public function test_quotations_blocked_returns_json_error_for_api_clients(): void
    {
        $this->assignPlan('starter');

        $response = $this->getJson(route('admin.quotations'));

        $response->assertStatus(403)
            ->assertJsonPath('error', 'plan_feature_required')
            ->assertJsonPath('required_feature', 'quotations')
            ->assertJsonPath('suggested_plan', 'professional');
    }

    public function test_tenant_without_plan_has_full_access(): void
    {
        $this->get(route('admin.quotations'))->assertStatus(200);
    }
}
