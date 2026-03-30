import { grandTotal } from '../cart/totals.js';
import { formatCurrency } from '@/js/utils/currencyFormatter.js';

export function calculateChange() {
    const amountReceived = document.getElementById("amountReceived");
    const changeAmount = document.getElementById("changeAmount");
    const paymentMethod = document.getElementById("paymentMethod");
    const completePaymentBtn = document.getElementById("completePaymentBtn");
    
    if (!amountReceived || !changeAmount || !paymentMethod || !completePaymentBtn) return;
    
    const received = parseFloat(amountReceived.value || 0);
    const change = received - grandTotal;

    changeAmount.textContent = formatCurrency(Math.max(0, change));

    completePaymentBtn.disabled =
        received < grandTotal && paymentMethod.value === "cash";
}

export function setExactAmount() {
    const amountReceived = document.getElementById("amountReceived");
    if (!amountReceived) return;
    
    amountReceived.value = grandTotal;
    calculateChange();
}

export function handlePaymentMethodChange() {
    const paymentMethod = document.getElementById("paymentMethod");
    const cashPaymentDiv = document.getElementById("cashPaymentDiv");
    const changeRow = document.getElementById("changeRow");
    const exactAmountBtn = document.getElementById("exactAmountBtn");
    const amountReceived = document.getElementById("amountReceived");
    const completePaymentBtn = document.getElementById("completePaymentBtn");
    
    if (!paymentMethod || !cashPaymentDiv || !changeRow || !exactAmountBtn || !amountReceived || !completePaymentBtn) return;
    
    const isCash = paymentMethod.value === "cash";

    cashPaymentDiv.style.display = isCash ? "block" : "none";
    changeRow.style.display = isCash ? "block" : "none";
    exactAmountBtn.style.display = isCash ? "inline-block" : "none";

    completePaymentBtn.disabled = isCash
        ? parseFloat(amountReceived.value || 0) < grandTotal
        : false;
}

export function initPaymentForm() {
    const paymentMethod = document.getElementById("paymentMethod");
    const amountReceived = document.getElementById("amountReceived");
    const exactAmountBtn = document.getElementById("exactAmountBtn");
    
    if (!paymentMethod || !amountReceived) return;
    
    if (exactAmountBtn) {
        exactAmountBtn.addEventListener("click", setExactAmount);
    }
    paymentMethod.addEventListener("change", handlePaymentMethodChange);
    amountReceived.addEventListener("input", calculateChange);
}
