import { describe, it, expect } from 'vitest';
import { computeTotals } from '@/js/mobile/totals.js';

/**
 * Parity pin: these figures are what SalesHelper (CalculatesInvoiceTotals)
 * produces for the same inputs — verified against the PHP helpers, not
 * copied from this file. If a formula here drifts, the mobile cart would
 * show one number and PosService would book another.
 */
describe('resources/js/mobile/totals.js', () => {
    it('leaves a plain cart untouched', () => {
        expect(
            computeTotals({ subtotal: 100000, discountValue: 0, discountType: 'fixed', taxRate: 0, taxType: 'none', pphRate: 0 }),
        ).toEqual({
            orderDiscount: 0,
            taxable: 100000,
            taxAmount: 0,
            pphAmount: 0,
            grandTotal: 100000,
        });
    });

    it('adds exclusive tax on top of the taxable amount', () => {
        const result = computeTotals({
            subtotal: 100000,
            taxRate: 11,
            taxType: 'exclusive',
        });

        expect(result.taxAmount).toBe(11000);
        expect(result.grandTotal).toBe(111000);
    });

    it('extracts inclusive tax instead of adding it back', () => {
        const result = computeTotals({
            subtotal: 111000,
            taxRate: 11,
            taxType: 'inclusive',
        });

        expect(result.taxAmount).toBeCloseTo(11000, 6);
        // Inclusive grand total IS the taxable amount — tax never re-added.
        expect(result.grandTotal).toBe(111000);
    });

    it('applies a fixed order discount before tax', () => {
        const result = computeTotals({
            subtotal: 100000,
            discountValue: 10000,
            discountType: 'fixed',
            taxRate: 11,
            taxType: 'exclusive',
        });

        expect(result.orderDiscount).toBe(10000);
        expect(result.taxable).toBe(90000);
        expect(result.taxAmount).toBe(9900);
        expect(result.grandTotal).toBe(99900);
    });

    it('converts a percentage discount against the subtotal', () => {
        const result = computeTotals({
            subtotal: 100000,
            discountValue: 10,
            discountType: 'percentage',
            taxRate: 0,
            taxType: 'none',
        });

        expect(result.orderDiscount).toBe(10000);
        expect(result.grandTotal).toBe(90000);
    });

    it('subtracts PPh from the taxable amount, rounded to two decimals', () => {
        const result = computeTotals({
            subtotal: 100000,
            discountValue: 10000,
            discountType: 'fixed',
            taxRate: 0,
            taxType: 'none',
            pphRate: 2,
        });

        expect(result.pphAmount).toBe(1800);
        expect(result.grandTotal).toBe(88200);
    });

    it('rounds PPh the way SalesHelper does', () => {
        // 33333.335 * 2% = 666.6667 → 666.67
        const result = computeTotals({ subtotal: 33333.335, pphRate: 2 });
        expect(result.pphAmount).toBe(666.67);
    });

    it('never returns a negative total after withholding', () => {
        const result = computeTotals({ subtotal: 100, pphRate: 200 });
        expect(result.grandTotal).toBe(0);
    });

    it('treats a missing discount as zero', () => {
        const result = computeTotals({ subtotal: 5000 });
        expect(result.orderDiscount).toBe(0);
        expect(result.taxAmount).toBe(0);
        expect(result.grandTotal).toBe(5000);
    });
});
