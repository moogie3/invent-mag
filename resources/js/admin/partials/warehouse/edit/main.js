import { handleFormSubmission } from "../common/formSubmission.js";

export function initEditWarehouseModal() {
    const editWarehouseModal = document.getElementById("editWarehouseModal");

    if (!editWarehouseModal) return;
    if (editWarehouseModal.dataset.initialized === 'true') return;
    editWarehouseModal.dataset.initialized = 'true';

    editWarehouseModal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        const warehouseId = button.getAttribute("data-id");
        const warehouseName = button.getAttribute("data-name");
        const warehouseAddress = button.getAttribute("data-address");
        const warehouseDescription = button.getAttribute("data-description");
        const isMain = button.getAttribute("data-is-main");

        document.getElementById("warehouseId").value = warehouseId || '';
        document.getElementById("warehouseNameEdit").value = warehouseName || '';
        document.getElementById("warehouseAddressEdit").value = warehouseAddress || '';
        document.getElementById("warehouseDescriptionEdit").value = warehouseDescription || '';
        
        const isMainCheckbox = document.getElementById("isMainWarehouseEdit");
        if (isMainCheckbox) {
            isMainCheckbox.checked = isMain === '1' || isMain === 'true';
        }

        const updateUrl = button.getAttribute("data-update-url");
        const editWarehouseForm = document.getElementById("editWarehouseForm");
        if (editWarehouseForm && updateUrl) {
            editWarehouseForm.action = updateUrl;
        }
    });

    const editWarehouseForm = document.getElementById("editWarehouseForm");
    if (editWarehouseForm && !editWarehouseForm.dataset.formInitialized) {
        editWarehouseForm.dataset.formInitialized = 'true';
        editWarehouseForm.addEventListener("submit", (event) => {
            handleFormSubmission(event, editWarehouseModal);
        });
    }
}
