import { getProducts } from './state.js';
import { formatCurrency } from '@/js/utils/currencyFormatter.js';

export let subtotal = 0;
export let orderDiscount = 0;
export let taxAmount = 0;
export let grandTotal = 0;

export function calculateTotals() {
    // Query DOM elements inside the function so they are found during test execution
    const subtotalElement = document.getElementById("subtotal");
    const finalTotalElement = document.getElementById("finalTotal");
    const orderDiscountInput = document.getElementById("orderDiscountInput");
    const orderDiscountTypeInput = document.getElementById(
        "orderDiscountTypeInput"
    );
    const taxInput = document.getElementById("taxInput");
    const cartCountElement = document.getElementById("cartCount");
    const grandTotalInput = document.getElementById("grandTotalInput");
    const processPaymentBtn = document.getElementById("processPaymentBtn");
    const orderDiscountElement = document.getElementById("orderDiscount");
    const discountTypeElement = document.getElementById("discountType");
    const taxSelector = document.getElementById("taxSelector");

    const products = getProducts();
    subtotal = products.reduce((sum, product) => sum + product.total, 0);

    const discountValue = parseFloat(orderDiscountElement?.value || 0);
    const discountType = discountTypeElement?.value ?? 'fixed';

    const selectedTaxOption = taxSelector?.options[taxSelector.selectedIndex];
    const taxRate = parseFloat(selectedTaxOption?.dataset.rate || 0);
    const taxType = selectedTaxOption?.dataset.type || 'none';

    if (discountType === "percentage") {
        orderDiscount = (subtotal * discountValue) / 100;
    } else {
        orderDiscount = discountValue;
    }

    const taxableAmount = subtotal - orderDiscount;
    if (taxType === "inclusive") {
        taxAmount = taxableAmount - taxableAmount / (1 + taxRate / 100);
        grandTotal = taxableAmount;
    } else {
        taxAmount = taxableAmount * (taxRate / 100);
        grandTotal = taxableAmount + taxAmount;
    }

    if (subtotalElement) subtotalElement.innerText = formatCurrency(subtotal);
    if (finalTotalElement) finalTotalElement.innerText = formatCurrency(grandTotal);
    if (cartCountElement) cartCountElement.innerText = products.length.toString();

    if (orderDiscountInput) orderDiscountInput.value = discountValue;  // raw input value (% or fixed), backend recalculates
    if (orderDiscountTypeInput) orderDiscountTypeInput.value = discountType;
    if (taxInput) taxInput.value = taxAmount;
    if (grandTotalInput) grandTotalInput.value = grandTotal;

    if (processPaymentBtn) processPaymentBtn.disabled = products.length === 0;
}
