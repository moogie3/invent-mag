<?php
namespace App\Helpers;

use App\Models\Product;
use Carbon\Carbon;

class ProductHelper
{
    /**
     * Get appropriate class and text for expiry date badges
     *
     * @param \Carbon\Carbon|string|null $expiryDate
     * @return array [badgeClass, badgeText]
     */
    public static function getExpiryClassAndText($expiryDate)
    {
        if (!$expiryDate) {
            return [null, null]; // Handle null gracefully
        }

        $expiryDate = Carbon::parse($expiryDate)->startOfDay();
        $today = now()->startOfDay();
        $diffDays = $today->diffInDays($expiryDate, false); // signed integer

        if ($expiryDate->isPast()) {
            return ['badge bg-red-lt', 'Expired'];
        } elseif ($diffDays <= 3 && $diffDays > 0) {
            return ['badge bg-orange-lt', 'Expiring Soon (' . $diffDays . 'd)'];
        } elseif ($diffDays <= 7) {
            return ['badge bg-yellow-lt', 'Expiring Soon (' . $diffDays . 'd)'];
        } elseif ($diffDays <= 30) {
            return ['badge bg-blue-lt', 'Expiring in ' . $diffDays . 'd'];
        }

        return [null, null]; // No badge if it's far away
    }

    /**
     * Get low stock badge and text
     *
     * @param int $quantity
     * @param int|null $threshold
     * @return array [badgeClass, badgeText]
     */
    public static function getLowStockBadge($quantity, $threshold = null)
    {
        // Use provided threshold or fall back to default
        $effectiveThreshold = $threshold ?? Product::DEFAULT_LOW_STOCK_THRESHOLD;

        if ($quantity <= $effectiveThreshold) {
            return ['badge bg-red-lt', 'Low Stock'];
        }

        return [null, null];
    }

}
