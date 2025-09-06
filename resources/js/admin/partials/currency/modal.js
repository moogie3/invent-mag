import * as elements from './elements.js';
import { restoreOriginalValues } from './form.js';

export function setupModalEventListeners() {
    if (elements.showModalButton) {
        elements.showModalButton.addEventListener("click", function () {
            const confirmModal = new bootstrap.Modal(
                document.getElementById("confirmModal")
            );
            confirmModal.show();
        });
    }

    if (elements.cancelButton) {
        elements.cancelButton.addEventListener("click", function () {
            restoreOriginalValues();
        });
    }

    if (elements.modalElement) {
        elements.modalElement.addEventListener("hidden.bs.modal", function (event) {
            if (!event.target.classList.contains("confirmed")) {
                restoreOriginalValues();
            }
        });
    }
}
