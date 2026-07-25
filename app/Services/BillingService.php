<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\SubscriptionOrder;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BillingService
{
    /**
     * Get the next billing period dates.
     *
     * @param Plan|null $plan  If provided, uses the plan's billing cycle length.
     *                         Falls back to 30 days for backward compatibility.
     */
    public function getNextBillingPeriod(?Plan $plan = null): array
    {
        $days = $plan?->getBillingPeriodDays() ?? 30;
        $now = now();
        $nextStart = $now->copy()->addDays($days);

        return [
            'start' => $nextStart->toDateString(),
            'end'   => $nextStart->copy()->addDays($days)->toDateString(),
        ];
    }

    /**
     * Get the current billing period dates.
     *
     * @param Plan|null $plan  If provided, uses the plan's billing cycle length.
     *                         Falls back to 30 days for backward compatibility.
     */
    public function getCurrentBillingPeriod(?Plan $plan = null): array
    {
        $days = $plan?->getBillingPeriodDays() ?? 30;
        $now = now();

        return [
            'start' => $now->toDateString(),
            'end'   => $now->copy()->addDays($days)->toDateString(),
        ];
    }

    /**
     * Get days remaining in the current billing period.
     */
    public function getDaysRemainingInPeriod(Tenant $tenant): int
    {
        $period = $tenant->getCurrentBillingPeriod();

        if ($period === null) {
            $plan = $tenant->getCurrentPlan();
            $period = $this->getCurrentBillingPeriod($plan);
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
            $plan = $tenant->getCurrentPlan();
            $period = $this->getCurrentBillingPeriod($plan);
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
            $tenant->trial_ends_at = null;
            $tenant->plan_changed_at = now();

            $this->initializeBillingPeriodIfEmpty($tenant, $newPlan);

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

        $nextPeriod = $this->getNextBillingPeriod($newPlan);
        $daysRemaining = $this->getDaysRemainingInPeriod($tenant);
        $creditAmount = $this->calculateDowngradeCredit($currentPlan, $newPlan, $daysRemaining);

        $tenant->pending_plan_id = $newPlan->id;
        $tenant->pending_plan_effective_at = $nextPeriod['start'] . ' 00:00:00';
        $tenant->proration_credit = $creditAmount;

        $this->initializeBillingPeriodIfEmpty($tenant, $newPlan);

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

    // -------------------------------------------------------------------------
    // Payment Confirmation (Single source of truth)
    // -------------------------------------------------------------------------

    /**
     * Confirm a successful payment and update the tenant's plan accordingly.
     *
     * This is the ONLY method that should handle post-payment plan activation.
     * Used by PlanController, MidtransController webhook, and API PaymentController.
     *
     * @param SubscriptionOrder  $order       The paid order
     * @param array              $paymentInfo Raw payment info from Midtrans (optional)
     * @return array Operation result with keys: type, message, plan, and optional proration/effective_date
     */
    public function confirmPayment(SubscriptionOrder $order, array $paymentInfo = []): array
    {
        $tenant = $order->tenant;
        $newPlan = $order->plan;
        $currentPlan = $tenant->getCurrentPlan();

        if ($currentPlan === null) {
            $changeType = 'upgrade';
        } else {
            $changeType = $order->change_type ?? $this->getPlanChangeType($currentPlan, $newPlan);
        }

        $this->initializeBillingPeriodIfEmpty($tenant, $newPlan);

        match ($changeType) {
            'upgrade'  => $result = $this->confirmUpgrade($tenant, $order, $newPlan, $currentPlan),
            'same'     => $result = $this->confirmRenewal($tenant, $order, $newPlan),
            'downgrade' => $result = $this->confirmDowngrade($tenant, $order, $newPlan, $currentPlan),
            default    => $result = ['type' => 'unknown', 'message' => 'Unknown change type.'],
        };

        return $result;
    }

    /**
     * Initialize billing period on the tenant if not already set.
     * Uses the plan's billing cycle length (monthly=30, yearly=365).
     */
    private function initializeBillingPeriodIfEmpty(Tenant $tenant, Plan $plan): void
    {
        if (! $tenant->billing_period_start) {
            $period = $this->getCurrentBillingPeriod($plan);
            $tenant->billing_period_start = $period['start'];
            $tenant->billing_period_end = $period['end'];
        }
    }

    /**
     * Handle an upgrade payment — plan changes immediately.
     */
    private function confirmUpgrade(Tenant $tenant, SubscriptionOrder $order, Plan $newPlan, ?Plan $currentPlan): array
    {
        $daysRemaining = $this->getDaysRemainingInPeriod($tenant);
        $prorationAmount = $currentPlan
            ? $this->calculateUpgradeProration($currentPlan, $newPlan, $daysRemaining)
            : 0.0;

        $tenant->plan_id = $newPlan->id;
        $tenant->plan_status = 'active';
        $tenant->trial_ends_at = null;
        $tenant->plan_changed_at = now();
        $tenant->save();

        $tenant->refresh();

        Log::info("Payment confirmed for order {$order->order_number}. Upgrade: {$currentPlan?->name} -> {$newPlan->name}. Proration: \${$prorationAmount}");

        return [
            'type' => 'upgrade',
            'plan' => $newPlan->name,
            'proration_amount' => $prorationAmount,
            'message' => "Payment confirmed. Upgraded to {$newPlan->name}.",
        ];
    }

    /**
     * Handle a same-tier renewal — extend the billing period.
     */
    private function confirmRenewal(Tenant $tenant, SubscriptionOrder $order, Plan $newPlan): array
    {
        $tenant->plan_status = 'active';
        $tenant->trial_ends_at = null;
        $tenant->plan_changed_at = now();

        $billingDays = $newPlan->getBillingPeriodDays();

        if ($tenant->billing_period_end && Carbon::parse($tenant->billing_period_end)->isFuture()) {
            $tenant->billing_period_end = Carbon::parse($tenant->billing_period_end)->addDays($billingDays);
        } else {
            $period = $this->getCurrentBillingPeriod($newPlan);
            $tenant->billing_period_start = $period['start'];
            $tenant->billing_period_end = $period['end'];
        }

        $tenant->save();

        $tenant->refresh();

        Log::info("Payment confirmed for order {$order->order_number}. Renewal: {$newPlan->name}. Extended until: {$tenant->billing_period_end}");

        return [
            'type' => 'renewal',
            'plan' => $newPlan->name,
            'message' => "Payment confirmed. {$newPlan->name} renewed until {$tenant->billing_period_end}.",
        ];
    }

    /**
     * Handle a downgrade payment — schedule for next billing period.
     */
    private function confirmDowngrade(Tenant $tenant, SubscriptionOrder $order, Plan $newPlan, ?Plan $currentPlan): array
    {
        $nextPeriod = $this->getNextBillingPeriod($newPlan);
        $daysRemaining = $this->getDaysRemainingInPeriod($tenant);
        $creditAmount = $currentPlan
            ? $this->calculateDowngradeCredit($currentPlan, $newPlan, $daysRemaining)
            : 0.0;

        $tenant->pending_plan_id = $newPlan->id;
        $tenant->pending_plan_effective_at = $nextPeriod['start'] . ' 00:00:00';
        $tenant->proration_credit = $creditAmount;
        $tenant->trial_ends_at = null;
        $tenant->save();

        Log::info("Payment confirmed for order {$order->order_number}. Downgrade scheduled: {$currentPlan?->name} -> {$newPlan->name}. Effective: {$nextPeriod['start']}. Credit: \${$creditAmount}");

        return [
            'type' => 'downgrade',
            'plan' => $newPlan->name,
            'effective_date' => $nextPeriod['start'],
            'credit_amount' => $creditAmount,
            'message' => "Payment confirmed. Downgrade to {$newPlan->name} takes effect on {$nextPeriod['start']}.",
        ];
    }
}
