<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BillingService
{
    /**
     * Get the next billing period dates (calendar-based: 1st of next month).
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
     * Get the current billing period dates.
     */
    public function getCurrentBillingPeriod(): array
    {
        $now = now();

        return [
            'start' => $now->startOfMonth()->toDateString(),
            'end' => $now->endOfMonth()->toDateString(),
        ];
    }

    /**
     * Get days remaining in the current billing period.
     */
    public function getDaysRemainingInPeriod(Tenant $tenant): int
    {
        $period = $tenant->getCurrentBillingPeriod();

        if ($period === null) {
            $period = $this->getCurrentBillingPeriod();
        }

        $end = Carbon::parse($period['end']);

        return max(0, (int) now()->diffInDays($end, false));
    }

    /**
     * Calculate the total days in the current billing period.
     */
    public function getTotalDaysInPeriod(Tenant $tenant): int
    {
        $period = $tenant->getCurrentBillingPeriod();

        if ($period === null) {
            $period = $this->getCurrentBillingPeriod();
        }

        $start = Carbon::parse($period['start']);
        $end = Carbon::parse($period['end']);

        return (int) $start->diffInDays($end) + 1;
    }

    /**
     * Calculate proration amount for upgrade (charge immediately).
     * Formula: (new_daily_price - current_daily_price) * days_remaining
     */
    public function calculateUpgradeProration(Plan $currentPlan, Plan $newPlan, int $daysRemaining): float
    {
        $currentDaily = $currentPlan->getDailyPrice();
        $newDaily = $newPlan->getDailyPrice();

        $diff = $newDaily - $currentDaily;

        return round(max(0, $diff * $daysRemaining), 2);
    }

    /**
     * Calculate credit for downgrade (stored for next bill).
     * Formula: (current_daily_price - new_daily_price) * days_remaining
     */
    public function calculateDowngradeCredit(Plan $currentPlan, Plan $newPlan, int $daysRemaining): float
    {
        $currentDaily = $currentPlan->getDailyPrice();
        $newDaily = $newPlan->getDailyPrice();

        $diff = $currentDaily - $newDaily;

        return round(max(0, $diff * $daysRemaining), 2);
    }

    /**
     * Determine if a plan change is an upgrade, downgrade, or same.
     */
    public function getPlanChangeType(Plan $currentPlan, Plan $newPlan): string
    {
        if ($newPlan->isUpgradeFrom($currentPlan)) {
            return 'upgrade';
        }

        if ($newPlan->isDowngradeFrom($currentPlan)) {
            return 'downgrade';
        }

        return 'same';
    }

    /**
     * Process a plan change (upgrade or downgrade).
     * Returns details about what action was taken.
     */
    public function processPlanChange(Tenant $tenant, Plan $newPlan): array
    {
        $currentPlan = $tenant->getCurrentPlan();
        $changeType = $this->getPlanChangeType($currentPlan, $newPlan);

        if ($changeType === 'same') {
            return [
                'type' => 'same',
                'action' => 'none',
                'message' => 'You are already on this plan.',
            ];
        }

        if ($changeType === 'upgrade') {
            $daysRemaining = $this->getDaysRemainingInPeriod($tenant);
            $prorationAmount = $this->calculateUpgradeProration($currentPlan, $newPlan, $daysRemaining);

            $tenant->plan_id = $newPlan->id;
            $tenant->plan_status = 'active';
            $tenant->plan_changed_at = now();
            
            if (!$tenant->billing_period_start) {
                $period = $this->getCurrentBillingPeriod();
                $tenant->billing_period_start = $period['start'];
                $tenant->billing_period_end = $period['end'];
            }
            
            $tenant->save();

            Log::info("Plan upgrade processed for tenant {$tenant->name}: {$currentPlan?->name} -> {$newPlan->name}. Proration: \${$prorationAmount}");

            return [
                'type' => 'upgrade',
                'action' => 'immediate',
                'new_plan' => $newPlan,
                'proration_amount' => $prorationAmount,
                'message' => "You have been upgraded to {$newPlan->name}. A prorated charge of \${$prorationAmount} has been applied.",
            ];
        }

        $nextPeriod = $this->getNextBillingPeriod();
        $daysRemaining = $this->getDaysRemainingInPeriod($tenant);
        $creditAmount = $this->calculateDowngradeCredit($currentPlan, $newPlan, $daysRemaining);

        $tenant->pending_plan_id = $newPlan->id;
        $tenant->pending_plan_effective_at = $nextPeriod['start'] . ' 00:00:00';
        $tenant->proration_credit = $creditAmount;
        
        if (!$tenant->billing_period_start) {
            $period = $this->getCurrentBillingPeriod();
            $tenant->billing_period_start = $period['start'];
            $tenant->billing_period_end = $period['end'];
        }
        
        $tenant->save();

        Log::info("Plan downgrade scheduled for tenant {$tenant->name}: {$currentPlan?->name} -> {$newPlan->name}. Effective: {$nextPeriod['start']}. Credit: \${$creditAmount}");

        $effectiveDate = Carbon::parse($nextPeriod['start'])->format('F d, Y');

        return [
            'type' => 'downgrade',
            'action' => 'scheduled',
            'new_plan' => $newPlan,
            'effective_date' => $effectiveDate,
            'credit_amount' => $creditAmount,
            'message' => "Your downgrade to {$newPlan->name} will take effect on {$effectiveDate}. You'll keep your current plan until then. A credit of \${$creditAmount} will be applied to your next bill.",
        ];
    }

    /**
     * Cancel a pending downgrade.
     */
    public function cancelScheduledDowngrade(Tenant $tenant): bool
    {
        if (!$tenant->hasScheduledDowngrade()) {
            return false;
        }

        $scheduledPlan = $tenant->getScheduledPlan();
        
        $tenant->cancelScheduledDowngrade();

        Log::info("Scheduled downgrade cancelled for tenant {$tenant->name}. Plan remains: {$scheduledPlan->name}");

        return true;
    }
}