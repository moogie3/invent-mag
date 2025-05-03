<?php

namespace App\Helpers;

class SalesHelper
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
        $discountPerUnit = self::calculateDiscountPerUnit($price, $discount, $discountType);
        return ($price - $discountPerUnit) * $quantity;
    }

    /**
     * Calculate discount per unit based on price and discount
     * @param float $price - Unit price
     * @param float $discount - Discount amount/percentage
     * @param string $discountType - 'fixed' or 'percentage'
     * @return float Discount amount per unit
     */
    public static function calculateDiscountPerUnit($price, $discount, $discountType)
    {
        return $discountType === 'percentage' ? ($price * $discount) / 100 : $discount;
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
        return $discountType === 'percentage' ? ($subtotal * $discountValue) / 100 : $discountValue;
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
     * Calculate invoice summary figures
     * @param array $items - Collection of sales items
     * @param float $discountTotal - Order discount value
     * @param string $discountTotalType - Order discount type
     * @param float $taxRate - Tax rate percentage
     * @return array Summary figures (subtotal, itemCount, totalProductDiscount, orderDiscount, taxAmount, finalTotal)
     */
    public static function calculateInvoiceSummary($items, $discountTotal, $discountTotalType, $taxRate = 0)
    {
        $subtotal = 0;
        $totalProductDiscount = 0;
        $itemCount = count($items);

        foreach ($items as $item) {
            // Important: Use customer_price field instead of price for sales items
            $price = $item->customer_price ?? $item->price; // Fallback to price if customer_price is not available

            $finalAmount = self::calculateTotal(
                $price,
                $item->quantity,
                $item->discount,
                $item->discount_type
            );

            $discountPerUnit = self::calculateDiscountPerUnit(
                $price,
                $item->discount,
                $item->discount_type
            );

            $subtotal += $finalAmount;
            $totalProductDiscount += $discountPerUnit * $item->quantity;
        }

        $orderDiscount = self::calculateDiscount($subtotal, $discountTotal, $discountTotalType);
        $taxableAmount = $subtotal - $orderDiscount;
        $taxAmount = self::calculateTaxAmount($taxableAmount, $taxRate);
        $finalTotal = $taxableAmount + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
            'totalProductDiscount' => $totalProductDiscount,
            'orderDiscount' => $orderDiscount,
            'taxAmount' => $taxAmount,
            'finalTotal' => $finalTotal,
        ];
    }


    /**
     * Get CSS class for status badge based on status and due date
     * @param string $status - Current status
     * @param \DateTime $dueDate - Due date
     * @return string CSS class for badge
     */
    public static function getStatusClass($status, $dueDate)
    {
        $dueDate = \Carbon\Carbon::parse($dueDate);

        if ($status === 'Paid') {
            return 'badge bg-green-lt';
        } elseif (now()->isAfter($dueDate)) {
            return 'badge bg-red-lt';
        } elseif (now()->diffInDays($dueDate) <= 3) {
            return 'badge bg-orange-lt';
        } elseif (now()->diffInDays($dueDate) <= 7) {
            return 'badge bg-yellow-lt';
        }
        return 'badge bg-blue-lt';
    }

    /**
     * Get HTML for status text based on status and due date
     * @param string $status - Current status
     * @param \DateTime $dueDate - Due date
     * @param \DateTime|null $paymentDate - Payment date if paid
     * @return string HTML for status text
     */
    public static function getStatusText($status, $dueDate)
    {
        $dueDate = \Carbon\Carbon::parse($dueDate);
        $today = now();
        $diffDays = (int) $today->diffInDays($dueDate, false); // Cast to integer to remove decimals

        if ($status === 'Paid') {
            return '<span class="h4"><i class="ti ti-check me-1 fs-4"></i> Paid</span>';
        } elseif ($diffDays == 0) {
            return '<span class="h4"><i class="ti ti-alert-triangle me-1 fs-4"></i> Due Today</span>';
        } elseif ($diffDays > 0 && $diffDays <= 3) {
            return '<span class="h4"><i class="ti ti-calendar-event me-1 fs-4"></i> Due in ' . $diffDays . ' Days</span>';
        } elseif ($diffDays > 3 && $diffDays <= 7) {
            return '<span class="h4"><i class="ti ti-calendar me-1 fs-4"></i> Due in 1 Week</span>';
        } elseif ($diffDays < 0) {
            return '<span class="h4"><i class="ti ti-alert-circle me-1 fs-4"></i> Overdue</span>';
        } else {
            return '<span class="h4"><i class="ti ti-clock me-1 fs-4"></i> Pending</span>';
        }
    }
}
