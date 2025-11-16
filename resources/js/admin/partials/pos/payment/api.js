import { grandTotal } from '../cart/totals.js';
import { clearCart } from '../cart/actions.js';

const completePaymentBtn = document.getElementById("completePaymentBtn");
const invoiceForm = document.getElementById("invoiceForm");
const paymentMethod = document.getElementById("paymentMethod");
const amountReceived = document.getElementById("amountReceived");
const paymentModal = new bootstrap.Modal(
    document.getElementById("paymentModal")
);

function completePayment() {
    completePaymentBtn.disabled = true;

    const paymentMethodMap = {
        cash: "Cash",
        card: "Card",
        transfer: "Transfer",
        ewallet: "eWallet",
    };

    const paymentInfoInput = document.createElement("input");
    paymentInfoInput.type = "hidden";
    paymentInfoInput.name = "payment_method";
    paymentInfoInput.value =
        paymentMethodMap[paymentMethod.value] || paymentMethod.value;
    invoiceForm.appendChild(paymentInfoInput);

    const taxRateInput = document.getElementById("taxRateInput");
    const taxRateValue = document.getElementById("taxRate").value;
    taxRateInput.value = taxRateValue;

    if (paymentMethod.value === "cash") {
        const amountReceivedInput = document.createElement("input");
        amountReceivedInput.type = "hidden";
        amountReceivedInput.name = "amount_received";
        amountReceivedInput.value = parseFloat(amountReceived.value || 0);
        invoiceForm.appendChild(amountReceivedInput);

        const changeInput = document.createElement("input");
        changeInput.type = "hidden";
        changeInput.name = "change_amount";
        changeInput.value = Math.max(
            0,
            parseFloat(amountReceived.value || 0) - grandTotal
        );
        invoiceForm.appendChild(changeInput);
    } else {
        const amountReceivedInput = document.createElement("input");
        amountReceivedInput.type = "hidden";
        amountReceivedInput.name = "amount_received";
        amountReceivedInput.value = grandTotal;
        invoiceForm.appendChild(amountReceivedInput);
    }

    localStorage.removeItem("cachedProducts");

    fetch(invoiceForm.action, {
        method: invoiceForm.method,
        body: new FormData(invoiceForm),
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                InventMagApp.showToast("Success", data.message, "success");
                paymentModal.hide();
                clearCart();
                window.location.href = `/admin/pos/receipt/${data.sale_id}`;
            } else {
                InventMagApp.showToast(
                    "Error",
                    data.message || "Failed to process payment.",
                    "error"
                );
                // // console.error("Payment error:", data.errors);
            }
        })
        .catch((error) => {
            // // console.error("Error processing payment:", error);
            InventMagApp.showToast(
                "Error",
                "An error occurred while processing payment. Please check the console for details.",
                "error"
            );
        })
        .finally(() => {
            completePaymentBtn.disabled = false;
        });
}

export function initPaymentApi() {
    completePaymentBtn.addEventListener("click", completePayment);
}