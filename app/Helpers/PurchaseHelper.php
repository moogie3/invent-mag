<?php

namespace App\Helpers;

class PurchaseHelper
{
    public static function calculateTotal($price, $quantity, $discount, $discountType)
    {
        $discountPerUnit = self::calculateDiscountPerUnit($price, $discount, $discountType);
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

    public static function calculateInvoiceSummary($items, $discountTotal, $discountTotalType, $taxRate = 0, $taxType = 'exclusive')
    {
        $subtotal = 0;
        $totalProductDiscount = 0;
        $itemCount = count($items);

        foreach ($items as $item) {
            $price = is_object($item) ? $item->price : $item['price'];
            $quantity = is_object($item) ? $item->quantity : $item['quantity'];
            $discount = is_object($item) ? $item->discount : $item['discount'];
            $discount_type = is_object($item) ? $item->discount_type : $item['discount_type'];

            $finalAmount = self::calculateTotal($price, $quantity, $discount, $discount_type);
            $discountPerUnit = self::calculateDiscountPerUnit($price, $discount, $discount_type);
            $subtotal += $finalAmount;
            $totalProductDiscount += $discountPerUnit * $quantity;
        }

        $orderDiscount = self::calculateDiscount($subtotal, $discountTotal, $discountTotalType);
        $taxableAmount = $subtotal - $orderDiscount;
        $taxAmount = self::calculateTaxAmount($taxableAmount, $taxRate, $taxType);

        if ($taxType === 'inclusive') {
            $finalTotal = $taxableAmount;
        } else {
            $finalTotal = $taxableAmount + $taxAmount;
        }

        return [
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
            'totalProductDiscount' => $totalProductDiscount,
            'orderDiscount' => $orderDiscount,
            'taxAmount' => $taxAmount,
            'finalTotal' => $finalTotal,
        ];
    }

    public static function calculateInvoiceSummaryWithReturns($items, $discountTotal, $discountTotalType, $purchaseReturns = null, $taxRate = 0, $taxType = 'exclusive')
    {
        $subtotal = 0;
        $totalProductDiscount = 0;
        $itemCount = 0;
        $totalReturnedQty = 0;

        foreach ($items as $item) {
            $price = is_object($item) ? $item->price : $item['price'];
            $originalQty = (int) (is_object($item) ? $item->quantity : $item['quantity']);
            $discount = is_object($item) ? $item->discount : $item['discount'];
            $discount_type = is_object($item) ? $item->discount_type : $item['discount_type'];

            $returnedQty = 0;
            if ($purchaseReturns && $purchaseReturns->isNotEmpty()) {
                $productId = is_object($item) ? $item->product_id : $item['product_id'];
                $returnedQty = $purchaseReturns
                    ->where('status', 'Completed')
                    ->flatMap(fn($pr) => $pr->items ?? collect())
                    ->where('product_id', $productId)
                    ->sum('quantity');
            }

            $adjustedQty = max(0, $originalQty - $returnedQty);
            $totalReturnedQty += $returnedQty;

            if ($adjustedQty > 0) {
                $itemCount++;
                $finalAmount = self::calculateTotal($price, $adjustedQty, $discount, $discount_type);
                $discountPerUnit = self::calculateDiscountPerUnit($price, $discount, $discount_type);
                $subtotal += $finalAmount;
                $totalProductDiscount += $discountPerUnit * $adjustedQty;
            }
        }

        $orderDiscount = self::calculateDiscount($subtotal, $discountTotal, $discountTotalType);
        $taxableAmount = $subtotal - $orderDiscount;
        $taxAmount = self::calculateTaxAmount($taxableAmount, $taxRate, $taxType);

        if ($taxType === 'inclusive') {
            $finalTotal = $taxableAmount;
        } else {
            $finalTotal = $taxableAmount + $taxAmount;
        }

        return [
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
            'totalProductDiscount' => $totalProductDiscount,
            'orderDiscount' => $orderDiscount,
            'taxAmount' => $taxAmount,
            'finalTotal' => $finalTotal,
            'totalReturnedQty' => $totalReturnedQty,
        ];
    }

    public static function getStatusClass($status, $dueDate)
    {
        $dueDate = \Carbon\Carbon::parse($dueDate);

        if ($status === 'Paid') {
            return 'badge bg-green-lt';
        } elseif ($status === 'Returned') {
            return 'badge bg-purple-lt';
        } elseif ($status === 'Partial') {
            return 'badge bg-cyan-lt';
        } elseif (now()->isAfter($dueDate)) {
            return 'badge bg-red-lt';
        } elseif (now()->diffInDays($dueDate) <= 3) {
            return 'badge bg-orange-lt';
        } elseif (now()->diffInDays($dueDate) <= 7) {
            return 'badge bg-yellow-lt';
        }
        return 'badge bg-blue-lt';
    }

    public static function getStatusText($status, $dueDate)
    {
        $dueDate = \Carbon\Carbon::parse($dueDate);
        $today = now();
        $diffDays = (int) $today->diffInDays($dueDate, false);

        if ($status === 'Paid') {
            return '<span class="h4"><i class="ti ti-check me-1 fs-4"></i> ' . __('messages.purchase_status_paid') . '</span>';
        } elseif ($status === 'Returned') {
            return '<span class="h4"><i class="ti ti-package-import me-1 fs-4"></i> ' . __('messages.purchase_status_returned') . '</span>';
        } elseif ($status === 'Partial') {
            return '<span class="h4"><i class="ti ti-git-branch me-1 fs-4"></i> ' . __('messages.purchase_status_partial') . '</span>';
        } elseif ($diffDays == 0) {
            return '<span class="h4"><i class="ti ti-alert-triangle me-1 fs-4"></i> ' . __('messages.purchase_status_due_today') . '</span>';
        } elseif ($diffDays > 0 && $diffDays <= 3) {
            return '<span class="h4"><i class="ti ti-calendar-event me-1 fs-4"></i> ' . __('messages.purchase_status_due_in_days', ['days' => $diffDays]) . '</span>';
        } elseif ($diffDays > 3 && $diffDays <= 7) {
            return '<span class="h4"><i class="ti ti-calendar me-1 fs-4"></i> ' . __('messages.purchase_status_due_in_1_week') . '</span>';
        } elseif ($diffDays < 0) {
            return '<span class="h4"><i class="ti ti-alert-circle me-1 fs-4"></i> ' . __('messages.purchase_status_overdue') . '</span>';
        } else {
            return '<span class="h4"><i class="ti ti-clock me-1 fs-4"></i> ' . __('messages.purchase_status_pending') . '</span>';
        }
    }
}
