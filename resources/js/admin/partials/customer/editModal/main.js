import { populateEditModalForm } from './form.js';
import { handleCustomerImage } from './image.js';

export function initEditCustomerModal() {
    const editCustomerModal = document.getElementById("editCustomerModal");

    if (!editCustomerModal) return;
    if (editCustomerModal.dataset.initialized) return;
    editCustomerModal.dataset.initialized = 'true';

    editCustomerModal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        populateEditModalForm(button);
        handleCustomerImage(button);
    });
}
