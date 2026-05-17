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
            'pending_plan_effective_at' => 'datetime',
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
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

    /**
     * Get the pending plan (for scheduled downgrades).
     */
    public function pendingPlan()
    {
        return $this->belongsTo(Plan::class, 'pending_plan_id');
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

        return Plan::findBySlug('enterprise');
    }

    /**
     * Get the actual current plan (ignores scheduled downgrades).
     * Used for feature checks and billing.
     */
    public function getCurrentPlan(): ?Plan
    {
        return $this->getEffectivePlan();
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
     * Expressive alias for onTrial() used by UI logic.
     * Returns true only when the tenant is actively trialing
     * and trial_ends_at is still in the future.
     */
    public function isOnActiveTrial(): bool
    {
        return $this->onTrial();
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
     * Get the subscription expiry date.
     * Returns the appropriate expiry date based on subscription state:
     * - Active trial: trial_ends_at
     * - Expired trial: trial_ends_at (mirrors the actual end date)
     * - Paid with billing period: billing_period_end
     * - Paid without billing period: plan_changed_at + billing cycle days
     *
     * @return \Carbon\Carbon|null
     */
    public function getSubscriptionExpiryDate(): ?\Carbon\Carbon
    {
        if (! $this->hasPlan()) {
            return null;
        }

        // Priority 1: If on active trial, use trial end date
        if ($this->onTrial() && $this->trial_ends_at) {
            return $this->trial_ends_at->copy()->endOfDay();
        }

        // Priority 2: If trial has expired, return trial_ends_at as the actual expiry date.
        // Without this, the method falls through to plan_changed_at + 30 which gives a
        // misleading future date for tenants who never converted from trial to paid.
        if ($this->trialExpired() && $this->trial_ends_at) {
            return $this->trial_ends_at->copy()->endOfDay();
        }

        // Priority 3: If has billing period, use that
        if ($this->billing_period_start && $this->billing_period_end) {
            return \Carbon\Carbon::parse($this->billing_period_end)->endOfDay();
        }

        // Priority 4: Calculate from plan_changed_at + billing cycle
        $plan = $this->getEffectivePlan();
        if ($this->plan_changed_at && $plan) {
            $billingDays = $plan->getBillingPeriodDays() ?? 30;
            return $this->plan_changed_at->copy()->addDays($billingDays)->endOfDay();
        }

        return null;
    }

    /**
     * Get the number of days remaining in the subscription.
     * Returns 0 if expired, negative if unlimited/error.
     * Uses trialDaysRemaining() when on trial for consistency with trial badge.
     *
     * @return int
     */
    public function subscriptionDaysRemaining(): int
    {
        // When on active trial, use trialDaysRemaining() for consistency with trial badge
        if ($this->onTrial()) {
            return $this->trialDaysRemaining();
        }

        // Trial has expired — no remaining days. Prevents fallthrough to
        // getSubscriptionExpiryDate() Priority 4 (plan_changed_at + 30) which
        // would incorrectly return a future date for expired trial tenants.
        if ($this->trialExpired()) {
            return 0;
        }

        $expiryDate = $this->getSubscriptionExpiryDate();

        if (! $expiryDate) {
            return -1; // Unlimited
        }

        // Return 0 if expired, otherwise return days remaining
        $days = now()->diffInDays($expiryDate, false);
        return $days < 0 ? 0 : (int) $days;
    }

    /**
     * Check if the subscription is expiring soon (within specified days).
     *
     * @param int $days
     * @return bool
     */
    public function isSubscriptionExpiringSoon(int $days = 7): bool
    {
        $daysRemaining = $this->subscriptionDaysRemaining();

        // Negative means no expiry (unlimited) or error
        if ($daysRemaining < 0) {
            return false;
        }

        return $daysRemaining >= 0 && $daysRemaining <= $days;
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

    /**
     * Check if the tenant can add more products based on plan limits.
     * Backward compatible: tenants without a plan have no limits.
     */
    public function canAddProducts(int $currentCount): bool
    {
        $plan = $this->getEffectivePlan();

        if ($plan === null) {
            return true;
        }

        return $plan->allowsProducts($currentCount + 1);
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

    // -------------------------------------------------------------------------
    // Billing Period Management
    // -------------------------------------------------------------------------

    /**
     * Initialize billing period (calendar-based: 1st to last day of month).
     */
    public function initializeBillingPeriod(): self
    {
        $now = now();
        $this->billing_period_start = $now->startOfMonth()->toDateString();
        $this->billing_period_end = $now->endOfMonth()->toDateString();
        $this->save();

        return $this;
    }

    /**
     * Get the next billing period dates.
     */
    public function getNextBillingPeriod(): array
    {
        $now = now();
        $nextMonth = $now->copy()->addMonth()->startOfMonth();

        return [
            'start' => $nextMonth->toDateString(),
            'end' => $nextMonth->endOfMonth()->toDateString(),
        ];
    }

    /**
     * Get the current billing period info.
     */
    public function getCurrentBillingPeriod(): ?array
    {
        if (!$this->billing_period_start || !$this->billing_period_end) {
            return null;
        }

        $end = Carbon::parse($this->billing_period_end);
        $daysRemaining = max(0, now()->diffInDays($end, false));

        return [
            'start' => $this->billing_period_start,
            'end' => $this->billing_period_end,
            'days_remaining' => $daysRemaining,
        ];
    }

    /**
     * Check if there's a scheduled downgrade pending.
     */
    public function hasScheduledDowngrade(): bool
    {
        return $this->pending_plan_id !== null 
            && $this->pending_plan_effective_at !== null
            && $this->pending_plan_effective_at->isFuture();
    }

    /**
     * Get the scheduled plan (for downgrades).
     */
    public function getScheduledPlan(): ?Plan
    {
        if (!$this->hasScheduledDowngrade()) {
            return null;
        }

        return $this->pendingPlan;
    }

    /**
     * Get the effective plan (considering scheduled changes).
     * If there's a scheduled downgrade, show that plan.
     */
    public function getEffectivePlanForDisplay(): ?Plan
    {
        if ($this->hasScheduledDowngrade()) {
            return $this->pendingPlan;
        }

        return $this->getEffectivePlan();
    }

    /**
     * Schedule a downgrade to take effect at next billing period.
     */
    public function scheduleDowngrade(Plan $plan, string $effectiveAt): self
    {
        $this->pending_plan_id = $plan->id;
        $this->pending_plan_effective_at = $effectiveAt;
        $this->save();

        return $this;
    }

    /**
     * Cancel a scheduled downgrade.
     */
    public function cancelScheduledDowngrade(): self
    {
        $this->pending_plan_id = null;
        $this->pending_plan_effective_at = null;
        $this->proration_credit = null;
        $this->save();

        return $this;
    }

    /**
     * Apply a scheduled downgrade (called by cron job).
     */
    public function applyScheduledDowngrade(): self
    {
        if (!$this->hasScheduledDowngrade()) {
            return $this;
        }

        $oldPlan = $this->plan;
        $newPlan = $this->pendingPlan;

        $this->plan_id = $this->pending_plan_id;
        $this->plan_changed_at = now();
        
        $this->pending_plan_id = null;
        $this->pending_plan_effective_at = null;
        
        $this->save();

        \Illuminate\Support\Facades\Log::info("Scheduled downgrade applied for tenant {$this->name}: {$oldPlan?->name} -> {$newPlan->name}");

        return $this;
    }

    // -------------------------------------------------------------------------
    // Backup Configuration
    // -------------------------------------------------------------------------

    /**
     * Check if the tenant has a backup password set.
     */
    public function hasBackupPassword(): bool
    {
        return !empty($this->backup_password);
    }

    /**
     * Check if backup encryption is enabled for this tenant.
     */
    public function isBackupEncrypted(): bool
    {
        return $this->backup_encrypted && $this->hasBackupPassword();
    }
}
