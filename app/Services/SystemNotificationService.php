<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SystemNotificationService
{
    /**
     * Get all system notifications for the current tenant/user.
     * Returns a collection of notification arrays with consistent structure.
     */
    public function getSystemNotifications(): Collection
    {
        $user = Auth::user();
        $dismissed = $user ? ($user->system_settings['dismissed_notifications'] ?? []) : [];

        return collect()
            ->concat($this->getTrialStatusNotifications())
            ->concat($this->getPlanExpiryNotifications())
            ->concat($this->getUsageLimitNotifications())
            ->concat($this->getAccountingSetupNotifications())
            ->concat($this->getWelcomeNotifications())
            ->filter(fn($item) => !in_array($item['id'], $dismissed))
            ->values();
    }

    /**
     * Get the count of active system notifications.
     */
    public function getSystemNotificationCount(): int
    {
        return $this->getSystemNotifications()->count();
    }

    // -------------------------------------------------------------------------
    // Trial Status Notification
    // Shows when tenant is on trial — "Your trial ends in X days"
    // Shows when trial has expired — "Your trial has expired"
    // -------------------------------------------------------------------------

    protected function getTrialStatusNotifications(): Collection
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant === null || !$tenant->hasPlan()) {
            return collect();
        }

        $notifications = collect();

        // Active trial — show days remaining
        if ($tenant->onTrial()) {
            $daysRemaining = $tenant->trialDaysRemaining();
            $urgency = $daysRemaining <= 3 ? 'critical' : ($daysRemaining <= 7 ? 'high' : 'medium');

            $notifications->push([
                'id' => 'system::trial_status',
                'title' => __('plan.notif_trial_ending_title'),
                'description' => __('plan.notif_trial_ending_desc', ['days' => $daysRemaining]),
                'icon' => 'ti ti-clock-hour-4',
                'color' => $daysRemaining <= 7 ? 'warning' : 'info',
                'urgency' => $urgency,
                'category' => 'trial',
                'action_label' => __('plan.upgrade_now'),
                'action_route' => route('admin.setting.plan.upgrade'),
                'type' => 'system',
                'show_notification' => true,
            ]);
        }

        // Trial expired
        if ($tenant->trialExpired()) {
            $notifications->push([
                'id' => 'system::trial_expired',
                'title' => __('plan.notif_trial_expired_title'),
                'description' => __('plan.notif_trial_expired_desc'),
                'icon' => 'ti ti-alert-triangle',
                'color' => 'danger',
                'urgency' => 'critical',
                'category' => 'trial',
                'action_label' => __('plan.upgrade_now'),
                'action_route' => route('admin.setting.plan.upgrade'),
                'type' => 'system',
                'show_notification' => true,
            ]);
        }

        return $notifications;
    }

    // -------------------------------------------------------------------------
    // Plan Expiry Notification
    // Shows 2 weeks before plan_expires_at — "Your plan is expiring soon"
    // -------------------------------------------------------------------------

    protected function getPlanExpiryNotifications(): Collection
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant === null || !$tenant->hasPlan()) {
            return collect();
        }

        // Only show if plan_expires_at is set and within 14 days
        if ($tenant->plan_expires_at === null) {
            return collect();
        }

        // Skip if on trial (trial notifications handle that)
        if ($tenant->onTrial()) {
            return collect();
        }

        $daysUntilExpiry = (int) max(0, now()->diffInDays($tenant->plan_expires_at, false));

        // Only show if within 14 days of expiry
        if ($daysUntilExpiry > 14) {
            return collect();
        }

        $plan = $tenant->getEffectivePlan();
        $planName = $plan ? $plan->name : 'current';

        if ($daysUntilExpiry <= 0) {
            // Plan has expired
            return collect([
                [
                    'id' => 'system::plan_expired',
                    'title' => __('plan.notif_plan_expired_title'),
                    'description' => __('plan.notif_plan_expired_desc', ['plan' => $planName]),
                    'icon' => 'ti ti-alert-circle',
                    'color' => 'danger',
                    'urgency' => 'critical',
                    'category' => 'plan_expiry',
                    'action_label' => __('plan.upgrade_now'),
                    'action_route' => route('admin.setting.plan.upgrade'),
                    'type' => 'system',
                    'show_notification' => true,
                ],
            ]);
        }

        // Plan expiring soon (within 14 days)
        $urgency = $daysUntilExpiry <= 3 ? 'critical' : ($daysUntilExpiry <= 7 ? 'high' : 'medium');

        return collect([
            [
                'id' => 'system::plan_expiring',
                'title' => __('plan.notif_plan_expiring_title'),
                'description' => __('plan.notif_plan_expiring_desc', ['days' => $daysUntilExpiry, 'plan' => $planName]),
                'icon' => 'ti ti-calendar-exclamation',
                'color' => $daysUntilExpiry <= 7 ? 'warning' : 'info',
                'urgency' => $urgency,
                'category' => 'plan_expiry',
                'action_label' => __('plan.upgrade_now'),
                'action_route' => route('admin.setting.plan.upgrade'),
                'type' => 'system',
                'show_notification' => true,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Usage Limit Notifications
    // Only shows when the tenant has reached the limit (not before)
    // -------------------------------------------------------------------------

    protected function getUsageLimitNotifications(): Collection
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant === null || !$tenant->hasPlan()) {
            return collect();
        }

        $plan = $tenant->getEffectivePlan();

        if ($plan === null) {
            return collect();
        }

        $notifications = collect();

        // User limit reached
        if ($plan->max_users > 0) {
            $currentUsers = User::count();
            if ($currentUsers >= $plan->max_users) {
                $notifications->push([
                    'id' => 'system::user_limit_reached',
                    'title' => __('plan.notif_user_limit_title'),
                    'description' => __('plan.notif_user_limit_desc', [
                        'current' => $currentUsers,
                        'limit' => $plan->max_users,
                    ]),
                    'icon' => 'ti ti-users',
                    'color' => 'warning',
                    'urgency' => 'high',
                    'category' => 'usage_limit',
                    'action_label' => __('plan.upgrade_plan'),
                    'action_route' => route('admin.setting.plan.upgrade'),
                    'type' => 'system',
                    'show_notification' => true,
                ]);
            }
        }

        // Warehouse limit reached
        if ($plan->max_warehouses > 0) {
            $currentWarehouses = Warehouse::count();
            if ($currentWarehouses >= $plan->max_warehouses) {
                $notifications->push([
                    'id' => 'system::warehouse_limit_reached',
                    'title' => __('plan.notif_warehouse_limit_title'),
                    'description' => __('plan.notif_warehouse_limit_desc', [
                        'current' => $currentWarehouses,
                        'limit' => $plan->max_warehouses,
                    ]),
                    'icon' => 'ti ti-building-warehouse',
                    'color' => 'warning',
                    'urgency' => 'high',
                    'category' => 'usage_limit',
                    'action_label' => __('plan.upgrade_plan'),
                    'action_route' => route('admin.setting.plan.upgrade'),
                    'type' => 'system',
                    'show_notification' => true,
                ]);
            }
        }

        return $notifications;
    }

    // -------------------------------------------------------------------------
    // Accounting Setup Incomplete Notification
    // Shows when any of the 6 required accounting settings are missing/null
    // -------------------------------------------------------------------------

    protected function getAccountingSetupNotifications(): Collection
    {
        $user = Auth::user();

        if ($user === null) {
            return collect();
        }

        // Check if this tenant has the accounting feature
        $tenant = $this->getCurrentTenant();
        if ($tenant !== null && !$tenant->hasFeature('accounting')) {
            return collect();
        }

        $settings = $user->accounting_settings ?? [];

        $requiredKeys = [
            'cash_account_id',
            'accounts_payable_account_id',
            'inventory_account_id',
            'sales_revenue_account_id',
            'accounts_receivable_account_id',
            'cost_of_goods_sold_account_id',
        ];

        $missingCount = 0;
        foreach ($requiredKeys as $key) {
            if (empty($settings[$key])) {
                $missingCount++;
            }
        }

        if ($missingCount === 0) {
            return collect();
        }

        return collect([
            [
                'id' => 'system::accounting_setup',
                'title' => __('plan.notif_accounting_setup_title'),
                'description' => __('plan.notif_accounting_setup_desc', ['count' => $missingCount]),
                'icon' => 'ti ti-settings-cog',
                'color' => 'info',
                'urgency' => 'low',
                'category' => 'setup',
                'action_label' => __('plan.notif_configure'),
                'action_route' => route('admin.setting.accounting'),
                'type' => 'system',
                'show_notification' => true,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // New User Welcome / Profile Incomplete Notification
    // Shows when shopname, address, or avatar are not set
    // -------------------------------------------------------------------------

    protected function getWelcomeNotifications(): Collection
    {
        $user = Auth::user();

        if ($user === null) {
            return collect();
        }

        $incompleteFields = [];

        if (empty($user->shopname)) {
            $incompleteFields[] = __('plan.notif_field_shopname');
        }
        if (empty($user->address)) {
            $incompleteFields[] = __('plan.notif_field_address');
        }
        if (empty($user->getRawOriginal('avatar'))) {
            $incompleteFields[] = __('plan.notif_field_avatar');
        }

        if (empty($incompleteFields)) {
            return collect();
        }

        return collect([
            [
                'id' => 'system::profile_incomplete',
                'title' => __('plan.notif_welcome_title'),
                'description' => __('plan.notif_welcome_desc', ['fields' => implode(', ', $incompleteFields)]),
                'icon' => 'ti ti-user-edit',
                'color' => 'info',
                'urgency' => 'low',
                'category' => 'setup',
                'action_label' => __('plan.notif_complete_profile'),
                'action_route' => route('admin.setting.profile.edit'),
                'type' => 'system',
                'show_notification' => true,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function getCurrentTenant(): ?Tenant
    {
        try {
            $tenant = app('currentTenant');
            return $tenant instanceof Tenant ? $tenant : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
