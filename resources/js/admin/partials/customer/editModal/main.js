import { populateEditModalForm } from './form.js';
import { handleCustomerImage } from './image.js';

export function initEditCustomerModal() {
    const editCustomerModal = document.getElementById("editCustomerModal");

    if (editCustomerModal) {
        editCustomerModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            populateEditModalForm(button);
            handleCustomerImage(button);
        });
    }
}
