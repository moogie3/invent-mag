<?php

namespace App\Helpers;

/**
 * Proportional tax reversal for sales and purchase returns.
 *
 * Single source of truth for the arithmetic: the return journals and the
 * return screens both call these methods, so a figure shown to the user can
 * never drift from the figure booked to the ledger. Change the formula here
 * and both move together.
 *
 * Deliberately pure (no queries): where the original amounts come from
 * differs by document type. A sale's PPN/PPh are read from its denormalized
 * snapshot; a purchase's are read from what its original journal actually
 * posted. That scan lives in the service that owns the journal.
 *
 * The base is always NET of tax (a sale's revenue credit, a purchase's
 * inventory debit), never the gross document total, because the reversal
 * must tie back to the original invoice.
 */
class ReturnTaxHelper
{
    /**
     * Net-base ratio: the return's total over the original booked base.
     * 0 when either side is missing, which zeroes every derived amount and
     * makes the caller post nothing. Capped at 1.0: a return can never
     * cover more than the invoice it reverses, and a ratio above 1 would
     * book a larger reversal than the original ever booked (PPN, PPh, COGS
     * and the AP/AR legs all scale by this number).
     */
    public static function ratio(float $returnTotal, float $originalBase): float
    {
        if ($returnTotal <= 0 || $originalBase <= 0) {
            return 0;
        }

        return min(1.0, $returnTotal / $originalBase);
    }

    /**
     * What a sales return reverses, from the sale's snapshot.
     *
     * @return array{ratio: float, ppn: float, pph: float}
     */
    public static function salesReversal(
        float $returnTotal,
        float $originalRevenueBase,
        float $snapshotPpnAmount,
        bool $isPpn,
        float $snapshotPphAmount,
        bool $isPph,
    ): array {
        $ratio = self::ratio($returnTotal, $originalRevenueBase);

        return [
            'ratio' => $ratio,
            'ppn' => ($isPpn && $snapshotPpnAmount > 0) ? round($snapshotPpnAmount * $ratio, 2) : 0.0,
            'pph' => ($isPph && $snapshotPphAmount > 0) ? round($snapshotPphAmount * $ratio, 2) : 0.0,
        ];
    }

    /**
     * What a purchase return reverses, from the amounts its original
     * journal posted (not the purchase row's snapshot: legacy and fallback
     * postings may differ, and the ledger is what gets reversed).
     *
     * @return array{ratio: float, ppn: float, pph: float}
     */
    public static function purchaseReversal(
        float $returnTotal,
        float $originalPurchaseBase,
        float $originalPostedPpn,
        float $originalPostedPph,
    ): array {
        $ratio = self::ratio($returnTotal, $originalPurchaseBase);

        return [
            'ratio' => $ratio,
            'ppn' => $originalPostedPpn > 0 ? round($originalPostedPpn * $ratio, 2) : 0.0,
            'pph' => $originalPostedPph > 0 ? round($originalPostedPph * $ratio, 2) : 0.0,
        ];
    }
}
