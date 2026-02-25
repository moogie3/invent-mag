import { describe, it, expect, beforeEach, vi } from 'vitest';

// Mock the getProducts dependency
vi.mock('@/js/admin/partials/pos/cart/state.js', () => ({
    getProducts: vi.fn(),
}));

// We will NOT mock the currencyFormatter, but instead provide its dependency.
import { calculateTotals, subtotal, orderDiscount, taxAmount, grandTotal } from '@/js/admin/partials/pos/cart/totals.js';
import { getProducts } from '@/js/admin/partials/pos/cart/state.js';


describe('POS Cart Totals Calculation', () => {

    beforeEach(() => {
        // Reset mocks before each test
        vi.clearAllMocks();

        // Provide the global dependency that the real formatCurrency function needs
        window.currencySettings = {
            currency_symbol: '$',
            decimal_places: 2,
            decimal_separator: '.',
            thousand_separator: ',',
            position: 'prefix',
        };

        // Set up a mock DOM environment
        document.body.innerHTML = `
            <span id="subtotal"></span>
            <span id="finalTotal"></span>
            <input id="orderDiscountInput" />
            <input id="orderDiscountTypeInput" />
            <input id="taxInput" />
            <span id="cartCount"></span>
            <input id="grandTotalInput" />
            <button id="processPaymentBtn"></button>
            <input id="orderDiscount" value="0" />
            <input id="discountType" value="fixed" />
            <input id="taxRate" value="0" />
        `;
    });

    it('should calculate totals correctly with no items', () => {
        // Arrange
        getProducts.mockReturnValue([]);

        // Act
        calculateTotals();

        // Assert
        expect(subtotal).toBe(0);
        expect(orderDiscount).toBe(0);
        expect(taxAmount).toBe(0);
        expect(grandTotal).toBe(0);
        expect(document.getElementById('finalTotal').innerText).toBe('$ 0.00');
    });

    it('should calculate totals correctly for a single item with no discount or tax', () => {
        // Arrange
        getProducts.mockReturnValue([{ id: 1, name: 'Test Product', price: 100, quantity: 2, total: 200 }]);

        // Act
        calculateTotals();

        // Assert
        expect(subtotal).toBe(200);
        expect(orderDiscount).toBe(0);
        expect(taxAmount).toBe(0);
        expect(grandTotal).toBe(200);
        expect(document.getElementById('finalTotal').innerText).toBe('$ 200.00');
    });

    it('should calculate totals correctly with a fixed discount', () => {
        // Arrange
        getProducts.mockReturnValue([{ total: 150 }]);
        document.getElementById('orderDiscount').value = '10';
        document.getElementById('discountType').value = 'fixed';

        // Act
        calculateTotals();

        // Assert
        expect(subtotal).toBe(150);
        expect(orderDiscount).toBe(10);
        expect(grandTotal).toBe(140);
    });

    it('should calculate totals correctly with a percentage discount', () => {
        // Arrange
        getProducts.mockReturnValue([{ total: 200 }]);
        document.getElementById('orderDiscount').value = '10';
        document.getElementById('discountType').value = 'percent';

        // Act
        calculateTotals();

        // Assert
        expect(subtotal).toBe(200);
        expect(orderDiscount).toBe(20); // 10% of 200
        expect(grandTotal).toBe(180);
    });

    it('should calculate totals correctly with tax', () => {
        // Arrange
        getProducts.mockReturnValue([{ total: 100 }]);
        document.getElementById('taxRate').value = '10';

        // Act
        calculateTotals();

        // Assert
        expect(subtotal).toBe(100);
        expect(orderDiscount).toBe(0);
        expect(taxAmount).toBe(10); // 10% of 100
        expect(grandTotal).toBe(110);
    });

    it('should calculate totals correctly with both discount and tax', () => {
        // Arrange
        getProducts.mockReturnValue([{ total: 100 }]);
        document.getElementById('orderDiscount').value = '20';
        document.getElementById('discountType').value = 'fixed';
        document.getElementById('taxRate').value = '10';

        // Act
        calculateTotals();

        // Assert
        expect(subtotal).toBe(100);
        expect(orderDiscount).toBe(20);
        const taxableAmount = 100 - 20;
        expect(taxAmount).toBe(taxableAmount * 0.10); // 8
        expect(grandTotal).toBe(taxableAmount + (taxableAmount * 0.10)); // 88
    });

    it('should update the DOM elements correctly', () => {
        // Arrange
        getProducts.mockReturnValue([{ total: 100 }]);
        document.getElementById('orderDiscount').value = '10';
        document.getElementById('discountType').value = 'fixed';
        document.getElementById('taxRate').value = '10';

        // Act
        calculateTotals();

        // Assert
        const taxableAmount = 100 - 10;
        const finalGrandTotal = taxableAmount + (taxableAmount * 0.10);

        expect(document.getElementById('subtotal').innerText).toBe('$ 100.00');
        expect(document.getElementById('finalTotal').innerText).toBe(`$ ${finalGrandTotal.toFixed(2)}`);
        expect(document.getElementById('cartCount').innerText).toBe('1');
        expect(document.getElementById('orderDiscountInput').value).toBe('10');
        expect(document.getElementById('taxInput').value).toBe((taxableAmount * 0.10).toString());
        expect(document.getElementById('grandTotalInput').value).toBe(finalGrandTotal.toString());
    });
});