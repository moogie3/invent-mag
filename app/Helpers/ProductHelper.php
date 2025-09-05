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
        $diffDays = (int) $today->diffInDays($expiryDate, false); // signed integer

        if ($expiryDate->isPast()) {
            return ['badge bg-red text-white', 'Expired'];
        } elseif ($diffDays <= 3 && $diffDays > 0) {
            return ['badge bg-orange text-white', 'Expiring Soon (' . $diffDays . 'd)'];
        } elseif ($diffDays <= 7) {
            return ['badge bg-yellow text-white', 'Expiring Soon (' . $diffDays . 'd)'];
        } elseif ($diffDays <= 30) {
            return ['badge bg-blue text-white', 'Expiring in ' . $diffDays . 'd'];
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
    public static function getStockClassAndText(Product $product)
    {
        $threshold = $product->low_stock_threshold ?? 10;
        $stockQty = $product->stock_quantity;

        if ($stockQty <= $threshold) {
            return ['badge bg-red text-white', 'Low Stock'];
        }

        return ['badge bg-green text-white', 'In Stock'];
    }
}