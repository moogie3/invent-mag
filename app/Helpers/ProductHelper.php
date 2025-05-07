<?php

namespace App\Helpers;

use Carbon\Carbon;

class ProductHelper
{
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
}