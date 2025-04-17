<?php

namespace App\Helpers;

class PurchaseHelper
{
    /**
     * Calculate total price for a product
     * @param float $price - Unit price
     * @param int $quantity - Quantity
     * @param float $discount - Discount amount/percentage
     * @param string $discountType - 'fixed' or 'percentage'
     * @return float Total price after discount
     */
    public static function calculateTotal($price, $quantity, $discount, $discountType)
    {
        $discountPerUnit = $discountType === 'percentage' ? ($price * $discount / 100) : $discount;
        return ($price - $discountPerUnit) * $quantity;
    }

    /**
     * Calculate discount amount based on value and type
     * @param float $subtotal - Subtotal amount
     * @param float $discountValue - Discount value
     * @param string $discountType - 'fixed' or 'percentage'
     * @return float Calculated discount amount
     */
    public static function calculateDiscount($subtotal, $discountValue, $discountType)
    {
        return $discountType === 'percentage' ? ($subtotal * $discountValue / 100) : $discountValue;
    }
}