import { handleFormSubmission } from '../common/formSubmission.js';

export function initEditWarehouseModal() {
    const editWarehouseModal = document.getElementById("editWarehouseModal");

    if (editWarehouseModal) {
        editWarehouseModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;

            const warehouseId = button.getAttribute("data-id");
            const warehouseName = button.getAttribute("data-name");
            const warehouseAddress = button.getAttribute("data-address");
            const warehouseDescription = button.getAttribute("data-description");

            document.getElementById("warehouseId").value = warehouseId;
            document.getElementById("warehouseNameEdit").value = warehouseName;
            document.getElementById("warehouseAddressEdit").value = warehouseAddress;
            document.getElementById("warehouseDescriptionEdit").value = warehouseDescription;

            document.getElementById("editWarehouseForm").action = "/admin/warehouse/update/" + warehouseId;
        });

        const editWarehouseForm = document.getElementById("editWarehouseForm");
        if (editWarehouseForm) {
            editWarehouseForm.addEventListener("submit", (event) => handleFormSubmission(event, editWarehouseModal));
        }
    }
}
