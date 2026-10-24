<?php

namespace App\Helpers;

use App\Models\Product;
use Carbon\Carbon;

class ProductHelper
{
    /**
     * Category names (guide prefixes + the legacy TR alias) whose products are
     * semiconductors — the only ones an alldatasheet link makes sense for.
     */
    private const DATASHEET_CATEGORIES = ['IC', 'TRS', 'TR', 'MOS', 'IGBT', 'DIO'];

    /**
     * Official alldatasheet "link this part" URL (their documented no-API way
     * of pointing at a datasheet: /view.jsp?Searchword=PART).
     *
     * The part number comes from the product NAME — the SKU guide requires
     * names like "Transistor TIP122" / "IC TDA4510" — never from products.code,
     * which holds the SKU (TRS-0001) and would search to garbage.
     *
     * Matches token shapes like TIP122, TDA4510, TL071CP, IRF540N, 2N3055,
     * 1N4007, 2SC5200; returns null for non-semiconductor categories or names
     * without a recognizable part number.
     */
    public static function datasheetUrl(?string $name, ?string $categoryName): ?string
    {
        // Categories are normally prefix-named ("IC"); also accept the guide
        // label style ("Integrated Circuit (IC)") via its parenthetical prefix.
        $candidates = [strtoupper(trim((string) $categoryName))];
        if (preg_match_all('/\(([^)]*)\)/', (string) $categoryName, $m)) {
            foreach ($m[1] as $inner) {
                $candidates[] = strtoupper(trim($inner));
            }
        }

        if (! array_intersect($candidates, self::DATASHEET_CATEGORIES)) {
            return null;
        }

        if (! preg_match('/\b(?:[a-z]{1,5}\d{2,6}[a-z]{0,3}|\d[a-z]\d{2,6}[a-z]{0,3})\b/i', (string) $name, $m)) {
            return null;
        }

        return 'https://www.alldatasheet.com/view.jsp?Searchword='.rawurlencode($m[0]);
    }

    /**
     * Get appropriate class and text for expiry date badges
     *
     * @param  Carbon|string|null  $expiryDate
     * @return array [badgeClass, badgeText]
     */
    public static function getExpiryClassAndText($expiryDate)
    {
        if (! $expiryDate) {
            return [null, null]; // Handle null gracefully
        }

        $expiryDate = Carbon::parse($expiryDate)->startOfDay();
        $today = now()->startOfDay();
        $diffDays = (int) $today->diffInDays($expiryDate, false); // signed integer

        if ($expiryDate->isPast()) {
            return ['badge bg-red text-white', 'Expired'];
        } elseif ($diffDays <= 3 && $diffDays > 0) {
            return ['badge bg-orange text-white', 'Expiring Soon ('.$diffDays.'d)'];
        } elseif ($diffDays <= 7) {
            return ['badge bg-yellow text-white', 'Expiring Soon ('.$diffDays.'d)'];
        } elseif ($diffDays <= 30) {
            return ['badge bg-blue text-white', 'Expiring in '.$diffDays.'d'];
        }

        return [null, null]; // No badge if it's far away
    }

    /**
     * Get low stock badge and text
     */
    public static function getStockClassAndText(int $stockQty, ?int $threshold = null): array
    {
        // Use Product's default threshold if not provided
        $effectiveThreshold = $threshold ?? Product::DEFAULT_LOW_STOCK_THRESHOLD;

        if ($stockQty <= $effectiveThreshold) {
            return ['badge bg-red text-white', 'Low Stock'];
        }

        return ['badge bg-green text-white', 'In Stock'];
    }
}
