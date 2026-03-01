<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Log;

class PlanService
{
    /**
     * Get the current tenant from the application context.
     */
    protected function getCurrentTenant(): ?Tenant
    {
        try {
            $tenant = app('currentTenant');
            return $tenant instanceof Tenant ? $tenant : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Feature Checks
    // -------------------------------------------------------------------------

    /**
     * Check if the current tenant has access to a specific feature.
     * Returns true if:
     *   - No tenant context (non-tenant route)
     *   - Tenant has no plan assigned (backward compatibility)
     *   - Tenant's plan includes the feature
     */
    public function tenantHasFeature(string $feature): bool
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant === null) {
            return true; // No tenant context, allow access
        }

        return $tenant->hasFeature($feature);
    }

    /**
     * Check if a specific tenant has access to a feature.
     */
    public function checkFeature(Tenant $tenant, string $feature): bool
    {
        return $tenant->hasFeature($feature);
    }

    // -------------------------------------------------------------------------
    // Limit Checks
    // -------------------------------------------------------------------------

    /**
     * Check if the current tenant can add more users.
     */
    public function canAddUser(): bool
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant === null) {
            return true;
        }

        $currentCount = User::count();
        return $tenant->canAddUsers($currentCount);
    }

    /**
     * Check if the current tenant can add more warehouses.
     */
    public function canAddWarehouse(): bool
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant === null) {
            return true;
        }

        $currentCount = Warehouse::count();
        return $tenant->canAddWarehouses($currentCount);
    }

    /**
     * Get the user limit for the current tenant.
     * Returns -1 for unlimited.
     */
    public function getUserLimit(): int
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant === null) {
            return -1;
        }

        $plan = $tenant->getEffectivePlan();
        return $plan ? $plan->max_users : -1;
    }

    /**
     * Get the warehouse limit for the current tenant.
     * Returns -1 for unlimited.
     */
    public function getWarehouseLimit(): int
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant === null) {
            return -1;
        }

        $plan = $tenant->getEffectivePlan();
        return $plan ? $plan->max_warehouses : -1;
    }

    // -------------------------------------------------------------------------
    // Plan Info
    // -------------------------------------------------------------------------

    /**
     * Get the current tenant's plan details.
     */
    public function getCurrentPlan(): ?Plan
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant === null) {
            return null;
        }

        return $tenant->getEffectivePlan();
    }

    /**
     * Get all available plans for display.
     */
    public function getAvailablePlans()
    {
        return Plan::active()->ordered()->get();
    }

    /**
     * Get the suggested upgrade plan for a given feature.
     */
    public function getSuggestedUpgrade(string $feature): ?Plan
    {
        return Plan::active()
            ->ordered()
            ->get()
            ->first(function (Plan $plan) use ($feature) {
                return $plan->hasFeature($feature);
            });
    }

    // -------------------------------------------------------------------------
    // Plan Management
    // -------------------------------------------------------------------------

    /**
     * Assign a plan to a tenant during registration.
     */
    public function assignPlanToTenant(Tenant $tenant, string $planSlug, bool $startTrial = true): void
    {
        $plan = Plan::findBySlug($planSlug);

        if ($plan === null) {
            Log::warning("Plan '{$planSlug}' not found, falling back to default plan.");
            $plan = Plan::getDefault();
        }

        if ($plan === null) {
            Log::error('No default plan found. Tenant will operate without a plan (backward-compat mode).');
            return;
        }

        $tenant->assignPlan($plan, $startTrial);

        Log::info("Plan '{$plan->name}' assigned to tenant '{$tenant->name}'.", [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'plan_slug' => $plan->slug,
            'trial' => $startTrial && $plan->hasTrial(),
            'trial_ends_at' => $tenant->trial_ends_at?->toDateTimeString(),
        ]);
    }

    /**
     * Upgrade a tenant's plan.
     */
    public function upgradePlan(Tenant $tenant, string $newPlanSlug): array
    {
        $newPlan = Plan::findBySlug($newPlanSlug);

        if ($newPlan === null) {
            return ['success' => false, 'message' => 'Plan not found.'];
        }

        $currentPlan = $tenant->getEffectivePlan();

        if ($currentPlan && $currentPlan->id === $newPlan->id) {
            return ['success' => false, 'message' => 'You are already on this plan.'];
        }

        $tenant->changePlan($newPlan);

        Log::info("Tenant '{$tenant->name}' upgraded to '{$newPlan->name}'.", [
            'tenant_id' => $tenant->id,
            'old_plan' => $currentPlan?->slug,
            'new_plan' => $newPlan->slug,
        ]);

        return [
            'success' => true,
            'message' => "Successfully upgraded to {$newPlan->name}.",
            'plan' => $newPlan,
        ];
    }

    /**
     * Get plan usage statistics for the current tenant.
     */
    public function getUsageStats(): array
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant === null) {
            return [];
        }

        $plan = $tenant->getEffectivePlan();
        $userCount = User::count();
        $warehouseCount = Warehouse::count();

        return [
            'plan' => $plan,
            'tenant' => $tenant,
            'users' => [
                'current' => $userCount,
                'limit' => $plan ? $plan->max_users : -1,
                'unlimited' => $plan ? $plan->max_users === -1 : true,
                'percentage' => ($plan && $plan->max_users > 0)
                    ? round(($userCount / $plan->max_users) * 100)
                    : 0,
            ],
            'warehouses' => [
                'current' => $warehouseCount,
                'limit' => $plan ? $plan->max_warehouses : -1,
                'unlimited' => $plan ? $plan->max_warehouses === -1 : true,
                'percentage' => ($plan && $plan->max_warehouses > 0)
                    ? round(($warehouseCount / $plan->max_warehouses) * 100)
                    : 0,
            ],
            'trial' => [
                'on_trial' => $tenant->onTrial(),
                'days_remaining' => $tenant->trialDaysRemaining(),
                'trial_ends_at' => $tenant->trial_ends_at,
            ],
        ];
    }
}
