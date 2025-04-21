<?php

namespace App\Helpers;

class SalesHelper
{
    /**
     * Calculate item discount amount based on price, quantity, discount value and type
     * @param float $price - Unit price
     * @param int $quantity - Quantity
     * @param float $discount - Discount amount/percentage
     * @param string $discountType - 'fixed' or 'percentage'
     * @return float Total discount amount
     */
    public static function calculateItemDiscountAmount($price, $quantity, $discount, $discountType)
    {
        if ($discountType === 'percentage') {
            return ($price * $discount / 100) * $quantity;
        }
        return $discount * $quantity; // Fixed amount
    }

    /**
     * Calculate total price for a product after discount
     * @param float $price - Unit price
     * @param int $quantity - Quantity
     * @param float $discount - Discount amount/percentage
     * @param string $discountType - 'fixed' or 'percentage'
     * @return float Total price after discount
     */
    public static function calculateItemTotal($price, $quantity, $discount, $discountType)
    {
        $discountAmount = self::calculateItemDiscountAmount($price, $quantity, $discount, $discountType);
        return ($price * $quantity) - $discountAmount;
    }

    /**
     * Calculate order discount amount
     * @param float $subtotal - Subtotal amount
     * @param float $discount - Discount value
     * @param string $discountType - 'fixed' or 'percentage'
     * @return float Calculated order discount amount
     */
    public static function calculateOrderDiscount($subtotal, $discount, $discountType)
    {
        if ($discountType === 'percentage') {
            return $subtotal * ($discount / 100);
        }
        return $discount;
    }

    /**
     * Calculate tax amount
     * @param float $amount - Taxable amount
     * @param float $taxRate - Tax rate percentage
     * @return float Calculated tax amount
     */
    public static function calculateTaxAmount($amount, $taxRate)
    {
        return $amount * ($taxRate / 100);
    }

    /**
     * Calculate grand total including tax
     * @param float $subtotal - Subtotal after item discounts
     * @param float $orderDiscount - Order discount amount
     * @param float $taxRate - Tax rate percentage
     * @return float Grand total amount
     */
    public static function calculateGrandTotal($subtotal, $orderDiscount, $taxRate)
    {
        $taxableAmount = $subtotal - $orderDiscount;
        $taxAmount = self::calculateTaxAmount($taxableAmount, $taxRate);
        return $taxableAmount + $taxAmount;
    }
}
