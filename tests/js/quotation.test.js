import { describe, it, expect, beforeEach } from 'vitest';
import { recalcQuotationTotals, initQuotationForm } from '@/js/admin/quotation.js';

// Mirrors the DOM the quotation create/edit forms render: one row with the
// `.item-*` classes the script reads, the summary spans, and the discount,
// tax and pph selectors. The row starts with a stale readonly amount and
// the summary with zeros, exactly like a freshly loaded edit page.
function mountQuotationForm() {
    document.body.innerHTML = `
        <div id="currencySettings" data-currency-symbol="Rp " data-decimal-places="0"
            data-decimal-separator="." data-thousand-separator=","
            data-currency-code="IDR" data-position="prefix" style="display: none;"></div>
        <form id="quotationForm">
            <table>
                <tbody id="quotationItemsBody">
                    <tr class="product-row">
                        <td class="text-center">1</td>
                        <td>
                            <input type="hidden" name="items[0][product_id]" value="7">
                            <div class="strong">Widget</div>
                        </td>
                        <td><input type="number" name="items[0][quantity]" class="form-control text-end item-quantity" value="2"></td>
                        <td><input type="number" name="items[0][price]" class="form-control text-end item-price" value="1000"></td>
                        <td>
                            <div class="input-group">
                                <input type="number" name="items[0][discount]" class="form-control text-end item-discount" value="50">
                                <select name="items[0][discount_type]" class="form-select item-discount-type">
                                    <option value="percentage">%</option>
                                    <option value="fixed" selected>fixed</option>
                                </select>
                            </div>
                        </td>
                        <td><input type="text" class="form-control text-end item-subtotal" value="0" readonly></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-item-btn" title="Remove">
                                <i class="ti ti-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <input type="number" id="orderDiscount" value="100">
            <select id="orderDiscountType">
                <option value="percentage">%</option>
                <option value="fixed" selected>fixed</option>
            </select>
            <select id="taxSelector">
                <option value="" data-name="None">None (0%)</option>
                <option value="3" data-name="PPN" data-rate="11" data-type="exclusive" selected>PPN (11%)</option>
            </select>
            <select id="pphSelector">
                <option value="" selected>None</option>
            </select>
            <span id="discountLabel" data-base="Discount">Discount</span>
            <span id="taxLabel" data-base="Tax">Tax</span>
            <span id="displaySubtotal">0</span>
            <span id="displayDiscount">0</span>
            <span id="displayTax">0</span>
            <span id="displayPph">0</span>
            <strong id="displayTotal">0</strong>
        </form>`;

    return document.getElementById('quotationForm');
}

describe('resources/js/admin/quotation.js', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('prices a fixed line discount per unit and fills every summary figure', () => {
        const form = mountQuotationForm();

        recalcQuotationTotals(form);

        // (1000 - 50 per unit) * 2 = 1900, written INTO the readonly input
        // (textContent on an input changes nothing on screen).
        expect(form.querySelector('.item-subtotal').value).toBe('Rp 1,900');
        expect(document.getElementById('displaySubtotal').textContent).toBe('Rp 1,900');

        // Order discount 100 fixed, taxable 1800, PPN 11% = 198.
        expect(document.getElementById('displayDiscount').textContent).toBe('Rp 100');
        expect(document.getElementById('displayTax').textContent).toBe('Rp 198');
        expect(document.getElementById('displayPph').textContent).toBe('Rp 0');
        expect(document.getElementById('displayTotal').textContent).toBe('Rp 1,998');
    });

    it('recalculates on load and the remove button empties the table', () => {
        mountQuotationForm();

        initQuotationForm();

        // The edit page must show real figures before the user touches
        // anything: the summary is rendered with zeros by the blade.
        expect(document.getElementById('displaySubtotal').textContent).toBe('Rp 1,900');

        // Server-rendered rows bind their remove button through the form,
        // not on the row itself, so the button works on the edit page too.
        document.querySelector('.remove-item-btn').click();

        expect(document.querySelectorAll('.product-row')).toHaveLength(0);
        expect(document.getElementById('displaySubtotal').textContent).toBe('Rp 0');
        expect(document.getElementById('displayTotal').textContent).toBe('Rp 0');
    });
});
