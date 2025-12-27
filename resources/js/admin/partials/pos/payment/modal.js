import { getProducts } from '../cart/state.js';
import { subtotal, orderDiscount, taxAmount, grandTotal } from '../cart/totals.js';
import { formatCurrency } from '../../../../utils/currencyFormatter.js';

const paymentModal = new bootstrap.Modal(
    document.getElementById("paymentModal")
);
const modalProductList = document.getElementById("modalProductList");
const modalSubtotal = document.getElementById("modalSubtotal");
const modalDiscount = document.getElementById("modalDiscount");
const modalTax = document.getElementById("modalTax");
const modalGrandTotal = document.getElementById("modalGrandTotal");
const amountReceived = document.getElementById("amountReceived");
const changeAmount = document.getElementById("changeAmount");
const orderDiscountElement = document.getElementById("orderDiscount");
const discountTypeElement = document.getElementById("discountType");
const taxRateElement = document.getElementById("taxRate");

export function populatePaymentModal() {
    const products = getProducts();
    modalProductList.innerHTML = "";

    products.forEach((product) => {
        const row = document.createElement("tr");
        row.innerHTML = `
        <td>${product.name}</td>
        <td class="text-center">${product.quantity} ${product.unit}</td>
        <td class="text-end">${formatCurrency(product.price)}</td>
        <td class="text-end">${formatCurrency(product.total)}</td>
    `;
        modalProductList.appendChild(row);
    });

    const discountValue = parseFloat(orderDiscountElement.value || 0);
    const discountType = discountTypeElement.value;
    const taxRate = parseFloat(taxRateElement.value || 0);

    let discountText = "";
    if (discountType === "percent") {
        discountText = `(${discountValue}%)`;
    } else {
        discountText = "(Fixed)";
    }

    let taxText = `(${taxRate}%)`;

    modalSubtotal.textContent = formatCurrency(subtotal);
    modalDiscount.textContent = `${formatCurrency(
        orderDiscount
    )} ${discountText}`;
    modalTax.textContent = `${formatCurrency(taxAmount)} ${taxText}`;
    modalGrandTotal.textContent = formatCurrency(grandTotal);

    amountReceived.value = "";
    changeAmount.textContent = formatCurrency(0);

    paymentModal.show();
}
