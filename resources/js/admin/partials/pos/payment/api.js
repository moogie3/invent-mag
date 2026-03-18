import { grandTotal } from "../cart/totals.js";
import { clearCart } from "../cart/actions.js";

let _paymentModal = null;

function getPaymentModal() {
    if (!_paymentModal) {
        const el = document.getElementById("paymentModal");
        _paymentModal = el ? new bootstrap.Modal(el, {}) : null;
    }
    return _paymentModal;
}

function completePayment() {
    const completePaymentBtn = document.getElementById("completePaymentBtn");
    const invoiceForm = document.getElementById("invoiceForm");
    const paymentMethod = document.getElementById("paymentMethod");
    const amountReceived = document.getElementById("amountReceived");

    if (!completePaymentBtn || !invoiceForm || !paymentMethod) return;

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
            parseFloat(amountReceived.value || 0) - grandTotal,
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
            Accept: "application/json",
        },
    })
        .then((response) => {
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            } else {
                return response.text().then((text) => {
                    throw new Error(
                        `Expected JSON, but received ${contentType || "unknown"}: ${text}`,
                    );
                });
            }
        })
        .then((data) => {
            if (data.success) {
                InventMagApp.showToast("Success", data.message, "success");
                const modal = getPaymentModal();
                if (modal) modal.hide();
                clearCart();
                // Use Turbo.visit instead of window.location.href
                // so Turbo lifecycle events fire correctly (loader, etc.)
                Turbo.visit(`/admin/pos/receipt/${data.sale_id}`, {
                    action: "replace",
                });
            } else {
                InventMagApp.showToast(
                    "Error",
                    data.message || "Failed to process payment.",
                    "error",
                );
                console.error("Payment error:", data.errors);
            }
        })
        .catch((error) => {
            console.error("Error processing payment:", error);
            InventMagApp.showToast(
                "Error",
                "An error occurred while processing payment. Please check the console for details.",
                "error",
            );
        })
        .finally(() => {
            completePaymentBtn.disabled = false;
        });
}

export function initPaymentApi() {
    const completePaymentBtn = document.getElementById("completePaymentBtn");
    if (completePaymentBtn) {
        completePaymentBtn.addEventListener("click", completePayment);
    }
}
