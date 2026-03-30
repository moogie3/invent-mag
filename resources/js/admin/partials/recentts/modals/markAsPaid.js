import { clearSelection } from '../utils/selection.js';
import { resetButton } from '../utils/resetButton.js';
import { updateBulkActions } from '../utils/dom.js';

let currentTransactionId = null;
let currentTransactionType = null;

export function showMarkAsPaidModal(id, type, invoice, customerSupplier, amount) {
    currentTransactionId = id;
    currentTransactionType = type;

    const modalInvoice = document.getElementById("modalInvoice");
    const modalCustomerSupplier = document.getElementById("modalCustomerSupplier");
    const modalAmount = document.getElementById("modalAmount");
    const modalType = document.getElementById("modalType");
    const modalEl = document.getElementById("markAsPaidModal");
    
    if (modalInvoice) modalInvoice.textContent = invoice;
    if (modalCustomerSupplier) modalCustomerSupplier.textContent = customerSupplier;
    if (modalAmount) modalAmount.textContent = amount;
    if (modalType) modalType.textContent = type === "sale" ? "Sales" : "Purchase";

    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

export function confirmMarkAsPaid() {
    if (!currentTransactionId || !currentTransactionType) return;

    const submitBtn = document.getElementById("confirmMarkPaidBtn");
    if (!submitBtn) return;
    const originalText = submitBtn.innerHTML;

    submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    submitBtn.disabled = true;

    fetch("/admin/reports/" + currentTransactionId + "/mark-paid", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? '',
        },
        body: JSON.stringify({
            type: currentTransactionType,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const modalEl = document.getElementById("markAsPaidModal");
                const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
                if (modal) {
                    modal.hide();
                    modal._element.addEventListener('hidden.bs.modal', function handler() {
                        modal._element.removeEventListener('hidden.bs.modal', handler);
                        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                    });
                }

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
                        statusBadge.classList.add('bg-success-lt');
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
            InventMagApp.showToast(
                "An error occurred while updating the transaction.",
                "error"
            );
        })
        .finally(() => {
            resetButton(submitBtn, originalText);
        });
}