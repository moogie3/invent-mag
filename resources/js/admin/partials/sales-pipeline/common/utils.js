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
        const modal = new bootstrap.Modal(
            document.getElementById("confirmationModal")
        );
        document.getElementById("deleteConfirmationForm").action = actionUrl;

        // We no longer set title and body dynamically, as they are handled by Blade
        // document.getElementById("confirmationModalTitle").textContent = title;
        // document.getElementById("confirmationModalBody").textContent = body;

        document.getElementById("confirmationModalConfirm").onclick =
            () => {
                // The form submission will handle the deletion and page refresh
                // We just need to resolve true if the user confirms
                resolve(true);
            };
        document.getElementById("confirmationModal").addEventListener(
            "hidden.bs.modal",
            () => {
                resolve(false);
            },
            { once: true }
        );
        modal.show();
    });
}
