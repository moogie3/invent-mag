document.addEventListener("DOMContentLoaded", function () {
    const editSupplierModal = document.getElementById("editSupplierModal");

    editSupplierModal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;

        if (!button) return; // Prevent errors if button is null

        // Get supplier data from the button attributes
        const supplierId = button.getAttribute("data-id") || "";
        const supplierCode = button.getAttribute("data-code") || "";
        const supplierName = button.getAttribute("data-name") || "";
        const supplierAddress = button.getAttribute("data-address") || "";
        const supplierPhone = button.getAttribute("data-phone_number") || "";
        const supplierLocation = button.getAttribute("data-location") || "";
        const supplierPayment = button.getAttribute("data-payment_terms") || "";

        // Populate the form fields inside the modal
        document.getElementById("supplierId").value = supplierId;
        if (document.getElementById("supplierCodeEdit")) {
            document.getElementById("supplierCodeEdit").value = supplierCode;
        }
        document.getElementById("supplierNameEdit").value = supplierName;
        document.getElementById("supplierAddressEdit").value = supplierAddress;
        document.getElementById("supplierPhoneEdit").value = supplierPhone;
        document.getElementById("supplierLocationEdit").value =
            supplierLocation;
        document.getElementById("supplierPaymentTermsEdit").value =
            supplierPayment;

        // Set the form action dynamically
        document.getElementById("editSupplierForm").action =
            "{{ route('admin.supplier.update', '') }}/" + supplierId;
    });
});
