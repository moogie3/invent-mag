export function populateEditModalForm(button) {
    const supplierId = button.getAttribute("data-id") || "";
    const supplierCode = button.getAttribute("data-code") || "";
    const supplierName = button.getAttribute("data-name") || "";
    const supplierAddress = button.getAttribute("data-address") || "";
    const supplierPhone =
        button.getAttribute("data-phone_number") || "";
    const supplierLocation = button.getAttribute("data-location") || "";
    const supplierPayment =
        button.getAttribute("data-payment_terms") || "";
    const supplierEmail = button.getAttribute("data-email") || "";

    document.getElementById("supplierId").value = supplierId;
    if (document.getElementById("supplierCodeEdit")) {
        document.getElementById("supplierCodeEdit").value =
            supplierCode;
    }
    document.getElementById("supplierNameEdit").value = supplierName;
    document.getElementById("supplierAddressEdit").value =
        supplierAddress;
    document.getElementById("supplierPhoneEdit").value = supplierPhone;
    document.getElementById("supplierLocationEdit").value =
        supplierLocation;
    document.getElementById("supplierPaymentTermsEdit").value =
        supplierPayment;
    document.getElementById("supplierEmailEdit").value = supplierEmail;

    const routeBase = document.getElementById("updateRouteBase").value;
    document.getElementById("editSupplierForm").action =
        routeBase + "/" + supplierId;
}
