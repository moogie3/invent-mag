import { formatCurrency } from '../../../../utils/currencyFormatter.js';

export { formatCurrency };

export function getStatusColor(status) {
    switch (status) {
        case "open":
            return "bg-primary";
        case "won":
            return "bg-success";
        case "lost":
            return "bg-danger";
        case "converted":
            return "bg-info";
        default:
            return "bg-secondary";
    }
}

export function showConfirmationModal(actionUrl) {
    return new Promise((resolve) => {
        const modalEl = document.getElementById("confirmationModal");
        if (!modalEl) {
            resolve(false);
            return;
        }
        
        const modal = new bootstrap.Modal(modalEl);
        const deleteForm = document.getElementById("deleteConfirmationForm");
        const confirmBtn = document.getElementById("confirmationModalConfirm");
        
        if (deleteForm) deleteForm.action = actionUrl;
        
        const cleanupBackdrop = () => {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        };

        if (confirmBtn) {
            confirmBtn.onclick = () => {
                cleanupBackdrop();
                resolve(true);
            };
        }
        
        modalEl.addEventListener("hidden.bs.modal", function handler() {
            modalEl.removeEventListener("hidden.bs.modal", handler);
            cleanupBackdrop();
            resolve(false);
        }, { once: true });
        
        modal.show();
    });
}
