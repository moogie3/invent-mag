<?php

namespace App\Helpers;

class PurchaseReturnHelper
{
    /**
     * Get CSS class for status badge based on purchase return status
     * @param string $status - Current status of the purchase return
     * @return string CSS class for badge
     */
    public static function getStatusClass($status)
    {
        switch (strtolower($status)) {
            case 'completed':
                return 'bg-success-lt';
            case 'pending':
                return 'bg-warning-lt';
            case 'canceled':
                return 'bg-danger-lt';
            default:
                return 'bg-secondary-lt';
        }
    }

    /**
     * Get HTML for status text based on purchase return status
     * @param string $status - Current status of the purchase return
     * @return string HTML for status text
     */
    public static function getStatusText($status)
    {
        switch (strtolower($status)) {
            case 'completed':
                return '<span class="h4"><i class="ti ti-check me-1 fs-4"></i> ' . __('messages.pr_status_completed') . '</span>';
            case 'pending':
                return '<span class="h4"><i class="ti ti-clock me-1 fs-4"></i> ' . __('messages.pr_status_pending') . '</span>';
            case 'canceled':
                return '<span class="h4"><i class="ti ti-x me-1 fs-4"></i> ' . __('messages.pr_status_canceled') . '</span>';
            default:
                return '<span class="h4"><i class="ti ti-info-circle me-1 fs-4"></i> ' . ucfirst($status) . '</span>';
        }
    }
}
