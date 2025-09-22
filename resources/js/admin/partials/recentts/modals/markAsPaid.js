import { clearSelection } from '../utils/selection.js';
import { resetButton } from '../utils/resetButton.js';
import { updateBulkActions } from '../utils/dom.js';

let currentTransactionId = null;
let currentTransactionType = null;

export function showMarkAsPaidModal(id, type, invoice, customerSupplier, amount) {
    currentTransactionId = id;
    currentTransactionType = type;

    document.getElementById("modalInvoice").textContent = invoice;
    document.getElementById("modalCustomerSupplier").textContent =
        customerSupplier;
    document.getElementById("modalAmount").textContent = amount;
    document.getElementById("modalType").textContent =
        type === "sale" ? "Sales" : "Purchase";

    const modal = new bootstrap.Modal(
        document.getElementById("markAsPaidModal")
    );
    modal.show();
}

export function confirmMarkAsPaid() {
    if (!currentTransactionId || !currentTransactionType) return;

    const submitBtn = document.getElementById("confirmMarkPaidBtn");
    const originalText = submitBtn.innerHTML;

    submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    submitBtn.disabled = true;

    fetch("/admin/transactions/" + currentTransactionId + "/mark-paid", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            type: currentTransactionType,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(
                    document.getElementById("markAsPaidModal")
                );
                modal.hide();
                modal._element.addEventListener('hidden.bs.modal', function handler() {
                    modal._element.removeEventListener('hidden.bs.modal', handler);
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                });

                InventMagApp.showToast(
                    "Success",
                    "Transaction marked as paid successfully!",
                    "success"
                );

                const row = document.querySelector(`tr[data-id="${currentTransactionId}"]`);
                if (row) {
                    const statusBadge = row.querySelector('.badge');
                    if (statusBadge) {
                        statusBadge.textContent = 'Paid';
                        statusBadge.classList.remove('bg-warning', 'bg-danger', 'bg-info');
                        statusBadge.classList.add('bg-success');
                    }
                    const checkbox = row.querySelector('.row-checkbox');
                    if (checkbox) {
                        checkbox.checked = false;
                    }
                }
                updateBulkActions();
            } else {
                InventMagApp.showToast("Error: " + data.message, "error");
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            InventMagApp.showToast(
                "An error occurred while updating the transaction.",
                "error"
            );
        })
        .finally(() => {
            resetButton(submitBtn, originalText);
        });
}