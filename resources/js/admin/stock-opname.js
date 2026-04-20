import * as Turbo from "@hotwired/turbo";

document.addEventListener("DOMContentLoaded", function initStockOpnameModals() {
    const cancelOpnameModal = document.getElementById("cancelOpnameModal");
    const completeOpnameModal = document.getElementById("completeOpnameModal");

    if (cancelOpnameModal && !cancelOpnameModal.dataset.initialized) {
        cancelOpnameModal.dataset.initialized = 'true';

        cancelOpnameModal.addEventListener("show.bs.modal", function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            const actionUrl = button.dataset.actionUrl;
            const cancelForm = document.getElementById("cancelOpnameForm");
            if (cancelForm && actionUrl) {
                cancelForm.action = actionUrl;
            }
        });
    }

    if (completeOpnameModal && !completeOpnameModal.dataset.initialized) {
        completeOpnameModal.dataset.initialized = 'true';

        completeOpnameModal.addEventListener("show.bs.modal", function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            const actionUrl = button.dataset.actionUrl;
            const completeForm = document.getElementById("completeOpnameForm");
            if (completeForm && actionUrl) {
                completeForm.action = actionUrl;
            }
        });
    }
});