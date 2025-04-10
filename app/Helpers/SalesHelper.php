<?php

namespace App\Helpers;

class SalesHelper
{
    public static function calculateTotals($items, $taxRate)
    {
        $totalDiscount = collect($items)->sum(function ($item) {
            return isset($item['discount_type']) && $item['discount_type'] === 'percentage'
                ? $item['price'] * $item['quantity'] * ($item['discount'] / 100)
                : $item['discount'];
        });

        $totalBeforeDiscount = $items->sum(fn($item) => $item->price * $item->quantity);
        $subTotal = $totalBeforeDiscount - $totalDiscount;
        $taxAmount = isset($taxRate) ? $subTotal * ($taxRate / 100) : 0;
        $grandTotal = $subTotal + $taxAmount;

        return [
            'totalDiscount' => $totalDiscount,
            'subTotal' => $subTotal,
            'taxAmount' => $taxAmount,
            'grandTotal' => $grandTotal,
        ];
    }
}
