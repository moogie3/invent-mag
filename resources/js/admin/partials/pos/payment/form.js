import { grandTotal } from '../cart/totals.js';
import { formatCurrency } from '@/js/utils/currencyFormatter.js';

const paymentMethod = document.getElementById("paymentMethod");
const amountReceived = document.getElementById("amountReceived");
const changeAmount = document.getElementById("changeAmount");
const cashPaymentDiv = document.getElementById("cashPaymentDiv");
const changeRow = document.getElementById("changeRow");
const exactAmountBtn = document.getElementById("exactAmountBtn");
const completePaymentBtn = document.getElementById("completePaymentBtn");

export function calculateChange() {
    const received = parseFloat(amountReceived.value || 0);
    const change = received - grandTotal;

    changeAmount.textContent = formatCurrency(Math.max(0, change));

    completePaymentBtn.disabled =
        received < grandTotal && paymentMethod.value === "cash";
}

export function setExactAmount() {
    amountReceived.value = grandTotal;
    calculateChange();
}

export function handlePaymentMethodChange() {
    const isCash = paymentMethod.value === "cash";

    cashPaymentDiv.style.display = isCash ? "block" : "none";
    changeRow.style.display = isCash ? "block" : "none";
    exactAmountBtn.style.display = isCash ? "inline-block" : "none";

    completePaymentBtn.disabled = isCash
        ? parseFloat(amountReceived.value || 0) < grandTotal
        : false;
}

export function initPaymentForm() {
    if (exactAmountBtn) {
        exactAmountBtn.addEventListener("click", setExactAmount);
    }
    paymentMethod.addEventListener("change", handlePaymentMethodChange);
    amountReceived.addEventListener("input", calculateChange);
}
