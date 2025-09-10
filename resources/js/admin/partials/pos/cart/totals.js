import { getProducts } from './state.js';
import { formatCurrency } from '@/js/utils/currencyFormatter.js';

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
const taxRateElement = document.getElementById("taxRate");

export let subtotal = 0;
export let orderDiscount = 0;
export let taxAmount = 0;
export let grandTotal = 0;

export function calculateTotals() {
    const products = getProducts();
    subtotal = products.reduce((sum, product) => sum + product.total, 0);

    const discountValue = parseFloat(orderDiscountElement.value || 0);
    const discountType = discountTypeElement.value;
    const taxRate = parseFloat(taxRateElement.value || 0);

    if (discountType === "percent") {
        orderDiscount = (subtotal * discountValue) / 100;
    } else {
        orderDiscount = discountValue;
    }

    const taxableAmount = subtotal - orderDiscount;
    taxAmount = taxableAmount * (taxRate / 100);
    grandTotal = taxableAmount + taxAmount;

    subtotalElement.innerText = formatCurrency(subtotal);
    finalTotalElement.innerText = formatCurrency(grandTotal);
    cartCountElement.innerText = products.length;

    orderDiscountInput.value = orderDiscount;
    orderDiscountTypeInput.value = discountType;
    taxInput.value = taxAmount;
    grandTotalInput.value = grandTotal;

    processPaymentBtn.disabled = products.length === 0;
}
