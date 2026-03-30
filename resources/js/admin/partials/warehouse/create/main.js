import { handleFormSubmission } from '../common/formSubmission.js';

export function initCreateWarehouseForm() {
    const createWarehouseForm = document.getElementById("createWarehouseForm");
    
    if (!createWarehouseForm) return;
    if (createWarehouseForm.dataset.formInitialized) return;
    createWarehouseForm.dataset.formInitialized = 'true';
    
    createWarehouseForm.addEventListener("submit", (event) => {
        handleFormSubmission(event, document.getElementById("createWarehouseModal"), true);
    });
}
