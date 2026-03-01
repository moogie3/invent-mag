<?php

namespace App\Models;

use Carbon\Carbon;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Tenant extends BaseTenant
{
    use UsesTenantConnection;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'plan_expires_at' => 'datetime',
            'plan_changed_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Get the plan associated with this tenant.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // -------------------------------------------------------------------------
    // Plan Status Helpers
    // -------------------------------------------------------------------------

    /**
     * Check if the tenant has a plan assigned.
     * Backward compatible: tenants without a plan are treated as enterprise (full access).
     */
    public function hasPlan(): bool
    {
        return $this->plan_id !== null;
    }

    /**
     * Get the effective plan for this tenant.
     * Returns null only if no plan exists AND no default plan is configured.
     * Backward compatible: existing tenants without a plan get enterprise access.
     */
    public function getEffectivePlan(): ?Plan
    {
        if ($this->hasPlan()) {
            return $this->plan;
        }

        // Backward compatibility: existing tenants without a plan
        // get enterprise-level access (all features, no limits)
        return Plan::findBySlug('enterprise');
    }

    /**
     * Check if the tenant is currently on a trial period.
     */
    public function onTrial(): bool
    {
        return $this->plan_status === 'trialing'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the tenant's trial has expired.
     */
    public function trialExpired(): bool
    {
        return $this->plan_status === 'trialing'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    /**
     * Get the number of days remaining in the trial.
     * Returns 0 if not on trial or trial has expired.
     */
    public function trialDaysRemaining(): int
    {
        if (! $this->onTrial()) {
            return 0;
        }

        return (int) max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Check if the tenant has an active subscription (paid or trialing).
     */
    public function hasActiveSubscription(): bool
    {
        // Backward compatibility: no plan assigned = full access
        if (! $this->hasPlan()) {
            return true;
        }

        return in_array($this->plan_status, ['active', 'trialing'])
            && ! $this->trialExpired();
    }

    /**
     * Check if the tenant's plan is expired or cancelled.
     */
    public function planInactive(): bool
    {
        if (! $this->hasPlan()) {
            return false; // backward compat
        }

        return in_array($this->plan_status, ['expired', 'cancelled'])
            || $this->trialExpired();
    }

    // -------------------------------------------------------------------------
    // Feature & Limit Checks
    // -------------------------------------------------------------------------

    /**
     * Check if the tenant's plan includes a specific feature.
     * Backward compatible: tenants without a plan have all features.
     */
    public function hasFeature(string $feature): bool
    {
        $plan = $this->getEffectivePlan();

        if ($plan === null) {
            return true; // No plan system configured, allow everything
        }

        return $plan->hasFeature($feature);
    }

    /**
     * Check if the tenant can add more users based on plan limits.
     * Backward compatible: tenants without a plan have no limits.
     */
    public function canAddUsers(int $currentCount): bool
    {
        $plan = $this->getEffectivePlan();

        if ($plan === null) {
            return true;
        }

        return $plan->allowsUsers($currentCount + 1);
    }

    /**
     * Check if the tenant can add more warehouses based on plan limits.
     * Backward compatible: tenants without a plan have no limits.
     */
    public function canAddWarehouses(int $currentCount): bool
    {
        $plan = $this->getEffectivePlan();

        if ($plan === null) {
            return true;
        }

        return $plan->allowsWarehouses($currentCount + 1);
    }

    // -------------------------------------------------------------------------
    // Plan Management
    // -------------------------------------------------------------------------

    /**
     * Assign a plan to this tenant.
     */
    public function assignPlan(Plan $plan, bool $startTrial = false): self
    {
        $this->plan_id = $plan->id;
        $this->plan_changed_at = now();

        if ($startTrial && $plan->hasTrial()) {
            $this->plan_status = 'trialing';
            $this->trial_ends_at = now()->addDays($plan->trial_days);
        } else {
            $this->plan_status = 'active';
            $this->trial_ends_at = null;
        }

        $this->save();

        return $this;
    }

    /**
     * Upgrade or change the tenant's plan.
     */
    public function changePlan(Plan $plan): self
    {
        $this->plan_id = $plan->id;
        $this->plan_status = 'active';
        $this->trial_ends_at = null;
        $this->plan_changed_at = now();
        $this->save();

        return $this;
    }
}
