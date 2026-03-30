import { restoreOriginalValues } from './form.js';

export function setupModalEventListeners() {
    const showModalButton = document.getElementById("showModalButton");
    const cancelButton = document.querySelector("#confirmModal .btn-secondary");
    const modalElement = document.getElementById("confirmModal");
    
    if (showModalButton) {
        showModalButton.addEventListener("click", function () {
            const confirmModal = new bootstrap.Modal(
                document.getElementById("confirmModal")
            );
            confirmModal.show();
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener("click", function () {
            restoreOriginalValues();
        });
    }

    if (modalElement) {
        modalElement.addEventListener("hidden.bs.modal", function (event) {
            if (!event.target.classList.contains("confirmed")) {
                restoreOriginalValues();
            }
        });
    }
}
