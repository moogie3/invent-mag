/**
 * POS cart arithmetic for the mobile PWA.
 *
 * This is a line-for-line port of
 * resources/js/admin/partials/pos/cart/totals.js — the same formulas the
 * web terminal sends to PosService. The server recomputes everything from
 * discount_total / discount_total_type / tax_id / pph_tax_id, so drift here
 * would only show up as a mismatch between the number on screen and the
 * number on the receipt. Keep the two in step.
 *
 * @param {{
 *   subtotal: number,
 *   discountValue: number,
 *   discountType: 'fixed'|'percentage',
 *   taxRate: number,
 *   taxType: 'none'|'inclusive'|'exclusive',
 *   pphRate: number,
 * }} input
 * @returns {{orderDiscount: number, taxable: number, taxAmount: number,
 *            pphAmount: number, grandTotal: number}}
 */
export function computeTotals({
    subtotal = 0,
    discountValue = 0,
    discountType = 'fixed',
    taxRate = 0,
    taxType = 'none',
    pphRate = 0,
}) {
    const orderDiscount =
        discountType === 'percentage' ? (subtotal * discountValue) / 100 : discountValue;

    const taxable = subtotal - orderDiscount;

    let taxAmount;
    let grandTotal;
    if (taxType === 'inclusive') {
        // Price already contains the tax: show the slice, never add it back.
        taxAmount = taxable - taxable / (1 + taxRate / 100);
        grandTotal = taxable;
    } else {
        taxAmount = taxable * (taxRate / 100);
        grandTotal = taxable + taxAmount;
    }

    // PPh is withheld from the taxable amount, so it only ever reduces the total.
    const pphAmount = pphRate > 0 ? Math.round(taxable * (pphRate / 100) * 100) / 100 : 0;

    return {
        orderDiscount,
        taxable,
        taxAmount,
        pphAmount,
        grandTotal: Math.max(0, grandTotal - pphAmount),
    };
}
