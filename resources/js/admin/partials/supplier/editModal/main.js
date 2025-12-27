import { populateEditModalForm } from './form.js';
import { handleSupplierImage } from './image.js';

export function initEditSupplierModal() {
    const editSupplierModal = document.getElementById("editSupplierModal");

    if (editSupplierModal) {
        editSupplierModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;

            if (!button) return;

            populateEditModalForm(button);
            handleSupplierImage(button);
        });
    }
}
