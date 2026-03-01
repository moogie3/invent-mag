<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed the plans table with the 3 tiers matching the frontend pricing page.
     *
     * Feature slugs correspond to config/plans.php feature definitions.
     * Limits match the pricing page exactly:
     *   Starter:      $19/mo, 3 users, 1 warehouse, 7-day trial
     *   Professional:  $49/mo, 10 users, unlimited warehouses, 7-day trial
     *   Enterprise:    $89/mo, unlimited users, unlimited warehouses, 7-day trial
     */
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'price' => 19.00,
                'billing_cycle' => 'monthly',
                'description' => 'Everything you need to manage inventory and orders.',
                'max_users' => 3,
                'max_warehouses' => 1,
                'trial_days' => 7,
                'is_active' => true,
                'sort_order' => 1,
                'features' => [
                    'products',
                    'inventory',
                    'sales',
                    'purchases',
                    'invoices',
                    'dashboard',
                    'basic_reports',
                    'customers',
                    'suppliers',
                ],
            ],
            [
                'slug' => 'professional',
                'name' => 'Professional',
                'price' => 49.00,
                'billing_cycle' => 'monthly',
                'description' => 'Full ERP with POS, accounting, CRM & pipeline.',
                'max_users' => 10,
                'max_warehouses' => -1, // unlimited
                'trial_days' => 7,
                'is_active' => true,
                'sort_order' => 2,
                'features' => [
                    // All starter features
                    'products',
                    'inventory',
                    'sales',
                    'purchases',
                    'invoices',
                    'dashboard',
                    'basic_reports',
                    'customers',
                    'suppliers',
                    // Professional features
                    'pos',
                    'accounting',
                    'crm',
                    'sales_pipeline',
                    'financial_reports',
                    'multi_currency',
                    'sales_returns',
                    'purchase_returns',
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'price' => 89.00,
                'billing_cycle' => 'monthly',
                'description' => 'Advanced intelligence, API access & full compliance.',
                'max_users' => -1, // unlimited
                'max_warehouses' => -1, // unlimited
                'trial_days' => 7,
                'is_active' => true,
                'sort_order' => 3,
                'features' => [
                    // All starter features
                    'products',
                    'inventory',
                    'sales',
                    'purchases',
                    'invoices',
                    'dashboard',
                    'basic_reports',
                    'customers',
                    'suppliers',
                    // All professional features
                    'pos',
                    'accounting',
                    'crm',
                    'sales_pipeline',
                    'financial_reports',
                    'multi_currency',
                    'sales_returns',
                    'purchase_returns',
                    // Enterprise-only features
                    'sales_forecasting',
                    'api_access',
                    'audit_compliance',
                    'custom_roles',
                    'dedicated_support',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
