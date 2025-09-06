import { handleFormSubmission } from '../common/formSubmission.js';

export function initCreateWarehouseForm() {
    const createWarehouseModal = document.getElementById("createWarehouseModal");
    const createWarehouseForm = document.getElementById("createWarehouseForm");
    if (createWarehouseForm) {
        createWarehouseForm.addEventListener("submit", (event) => handleFormSubmission(event, createWarehouseModal, true));
    }
}
