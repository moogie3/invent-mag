import { currencySettings } from './state.js';

export function formatCurrencyJs(amount) {
    let number = parseFloat(amount);
    if (isNaN(number)) {
        return amount;
    }

    return new Intl.NumberFormat(currencySettings.locale, {
        style: "currency",
        currency: currencySettings.currency_code,
        minimumFractionDigits: currencySettings.decimalPlaces,
        maximumFractionDigits: currencySettings.decimalPlaces,
    }).format(number);
}

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

export function showConfirmationModal(title, body) {
    return new Promise((resolve) => {
        const modal = new bootstrap.Modal(
            document.getElementById("confirmationModal")
        );
        document.getElementById("confirmationModalTitle").textContent =
            title;
        document.getElementById("confirmationModalBody").textContent = body;
        document.getElementById("confirmationModalConfirm").onclick =
            () => {
                modal.hide();
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
