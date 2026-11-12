<?php

namespace App\Helpers\Concerns;

/**
 * The arithmetic every invoice shares: discount, VAT and PPh. One copy for
 * both SalesHelper and PurchaseHelper, because these formulas used to live
 * twice and had already drifted apart - whoever changes the inclusive-VAT
 * rounding here changes it for sales and purchases at once.
 */
trait CalculatesInvoiceTotals
{
    public static function calculateTotal($price, $quantity, $discount, $discountType)
    {
        $discountPerUnit = static::calculateDiscountPerUnit($price, $discount, $discountType);

        return ($price - $discountPerUnit) * $quantity;
    }

    public static function calculateDiscountPerUnit($price, $discount, $discountType)
    {
        return $discountType === 'percentage' ? ($price * $discount) / 100 : $discount;
    }

    public static function calculateDiscount($subtotal, $discountValue, $discountType)
    {
        return $discountType === 'percentage' ? ($subtotal * $discountValue) / 100 : $discountValue;
    }

    public static function calculateTaxAmount($amount, $taxRate, $taxType = 'exclusive')
    {
        if ($taxType === 'none' || $taxRate <= 0 || $amount <= 0) {
            return 0.0;
        }

        if ($taxType === 'inclusive') {
            return round($amount - ($amount / (1 + $taxRate / 100)), 2);
        }

        return round($amount * ($taxRate / 100), 2);
    }

    /**
     * PPh is withheld from the taxable amount, never added to it, so it only
     * ever reduces the final total.
     */
    public static function calculatePphAmount($taxableAmount, $pphRate = 0)
    {
        if ($pphRate <= 0 || $taxableAmount <= 0) {
            return 0.0;
        }

        return round($taxableAmount * ($pphRate / 100), 2);
    }

    /**
     * The shared tail of both invoice summaries: order discount, tax, PPh,
     * final total. Each helper keeps its own item loop (they price different
     * things) and hands the accumulated figures here so the tax half cannot
     * drift between the two.
     *
     * @return array<string, mixed>
     */
    protected static function finalizeSummary(
        $subtotal,
        $itemCount,
        $totalProductDiscount,
        $discountTotal,
        $discountTotalType,
        $taxRate,
        $taxType,
        $pphRate
    ): array {
        $orderDiscount = static::calculateDiscount($subtotal, $discountTotal, $discountTotalType);
        $taxableAmount = $subtotal - $orderDiscount;
        $taxAmount = static::calculateTaxAmount($taxableAmount, $taxRate, $taxType);

        if ($taxType === 'inclusive') {
            $finalTotal = $taxableAmount;
        } else {
            $finalTotal = $taxableAmount + $taxAmount;
        }

        $pphAmount = static::calculatePphAmount($taxableAmount, $pphRate);
        $finalTotal = max(0, $finalTotal - $pphAmount);

        return [
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
            'totalProductDiscount' => $totalProductDiscount,
            'orderDiscount' => $orderDiscount,
            'taxAmount' => $taxAmount,
            'pphAmount' => $pphAmount,
            'finalTotal' => $finalTotal,
        ];
    }
}
