document.addEventListener("DOMContentLoaded", function () {
    const editWarehouseModal = document.getElementById("editWarehouseModal");

    editWarehouseModal.addEventListener("show.bs.modal", function (event) {
        // Get the button that triggered the modal
        const button = event.relatedTarget;

        // Get warehouse data from the button attributes
        const warehouseId = button.getAttribute("data-id");
        const warehouseName = button.getAttribute("data-name");
        const warehouseAddress = button.getAttribute("data-address");
        const warehouseDescription = button.getAttribute("data-description");

        // Populate the form fields inside the modal
        document.getElementById("warehouseId").value = warehouseId;
        document.getElementById("warehouseNameEdit").value = warehouseName;
        document.getElementById("warehouseAddressEdit").value =
            warehouseAddress;
        document.getElementById("warehouseDescriptionEdit").value =
            warehouseDescription;

        // Set the form action dynamically
        document.getElementById("editWarehouseForm").action =
            "{{ route('admin.warehouse.update', '') }}/" + warehouseId;
    });
});
