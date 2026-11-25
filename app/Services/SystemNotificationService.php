<?php

namespace App\Services;

use App\Models\POItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            ->concat($this->getAccountingSetupNotifications())
            ->concat($this->getWelcomeNotifications())
            ->concat($this->getProductExpiryNotifications())
            ->concat($this->getBackupStatusNotifications())
            ->filter(fn ($item) => ! in_array($item['id'], $dismissed))
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
    // Accounting Setup Incomplete Notification
    // Shows when any of the 6 required accounting settings are missing/null
    // -------------------------------------------------------------------------

    protected function getAccountingSetupNotifications(): Collection
    {
        $user = Auth::user();

        if ($user === null) {
            return collect();
        }

        // Read accounting settings from the tenant (not the user)
        $tenant = $this->getCurrentTenant();
        $settings = $tenant !== null ? ($tenant->accounting_settings ?? []) : [];

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
    // Product Expiry Notification
    // Shows when items with expiry_date are within 30 days
    // -------------------------------------------------------------------------

    protected function getProductExpiryNotifications(): Collection
    {
        // Count items expiring within 30 days with remaining stock
        $thirtyDaysFromNow = now()->addDays(30);
        $sevenDaysFromNow = now()->addDays(7);

        $critical = POItem::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', $sevenDaysFromNow)
            ->where('remaining_quantity', '>', 0)
            ->count();

        $total = POItem::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', $thirtyDaysFromNow)
            ->where('remaining_quantity', '>', 0)
            ->count();

        if ($total === 0) {
            return collect();
        }

        $urgency = $critical > 0 ? 'high' : 'medium';
        $color = $critical > 0 ? 'danger' : 'warning';

        return collect([
            [
                'id' => 'system::product_expiry',
                'title' => __('plan.notif_product_expiry_title'),
                'description' => $critical > 0
                    ? __('plan.notif_product_expiry_critical_desc', ['critical' => $critical, 'total' => $total])
                    : __('plan.notif_product_expiry_desc', ['count' => $total]),
                'icon' => 'ti ti-calendar-exclamation',
                'color' => $color,
                'urgency' => $urgency,
                'category' => 'inventory',
                'action_label' => __('plan.notif_view_products'),
                'action_route' => route('admin.product'),
                'type' => 'system',
                'show_notification' => true,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Backup Status Notification
    // Warns when no backup has been taken in the last 7 days
    // OR confirms if a recent backup was taken (within 24 hours)
    // -------------------------------------------------------------------------

    protected function getBackupStatusNotifications(): Collection
    {
        try {
            $tenant = $this->getCurrentTenant();
            if ($tenant === null) {
                return collect();
            }

            $domainPrefix = $tenant->domain.'-';
            $allFiles = Storage::disk('local')->files('backups');

            // Filter to this tenant's backup files
            $tenantFiles = collect($allFiles)->filter(function ($file) use ($domainPrefix) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'zip'
                    && str_starts_with(basename($file), $domainPrefix);
            });

            if ($tenantFiles->isEmpty()) {
                // No backups at all — suggest creating one
                return collect([
                    [
                        'id' => 'system::no_backup',
                        'title' => __('plan.notif_no_backup_title'),
                        'description' => __('plan.notif_no_backup_desc'),
                        'icon' => 'ti ti-device-floppy',
                        'color' => 'warning',
                        'urgency' => 'medium',
                        'category' => 'backup',
                        'action_label' => __('plan.notif_go_to_backup'),
                        'action_route' => route('admin.setting.backup.index'),
                        'type' => 'system',
                        'show_notification' => true,
                    ],
                ]);
            }

            // Find the most recent backup
            $latestTimestamp = $tenantFiles->map(function ($file) {
                return Storage::disk('local')->lastModified($file);
            })->max();

            $latestDate = Carbon::createFromTimestamp($latestTimestamp);
            $hoursSince = now()->diffInHours($latestDate, false);
            $daysSince = now()->diffInDays($latestDate, false);

            // Recent backup (within 24 hours) — success confirmation
            if ($hoursSince >= 0 && $hoursSince <= 24) {
                return collect([
                    [
                        'id' => 'system::backup_recent',
                        'title' => __('plan.notif_backup_recent_title'),
                        'description' => __('plan.notif_backup_recent_desc', [
                            'time' => $latestDate->diffForHumans(),
                        ]),
                        'icon' => 'ti ti-circle-check',
                        'color' => 'success',
                        'urgency' => 'low',
                        'category' => 'backup',
                        'action_label' => __('plan.notif_go_to_backup'),
                        'action_route' => route('admin.setting.backup.index'),
                        'type' => 'system',
                        'show_notification' => true,
                    ],
                ]);
            }

            // Backup is older than 7 days — warn user
            if ($daysSince > 7) {
                return collect([
                    [
                        'id' => 'system::backup_overdue',
                        'title' => __('plan.notif_backup_overdue_title'),
                        'description' => __('plan.notif_backup_overdue_desc', [
                            'days' => (int) $daysSince,
                        ]),
                        'icon' => 'ti ti-device-floppy',
                        'color' => 'warning',
                        'urgency' => $daysSince > 30 ? 'high' : 'medium',
                        'category' => 'backup',
                        'action_label' => __('plan.notif_go_to_backup'),
                        'action_route' => route('admin.setting.backup.index'),
                        'type' => 'system',
                        'show_notification' => true,
                    ],
                ]);
            }

            // Backup is between 1–7 days old — no notification needed
            return collect();

        } catch (\Exception $e) {
            return collect();
        }
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
