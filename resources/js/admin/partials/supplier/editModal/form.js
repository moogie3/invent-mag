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

    const supplierIdEl = document.getElementById("supplierId");
    if (supplierIdEl) supplierIdEl.value = supplierId;
    const supplierCodeEl = document.getElementById("supplierCodeEdit");
    if (supplierCodeEl) supplierCodeEl.value = supplierCode;
    const supplierNameEl = document.getElementById("supplierNameEdit");
    if (supplierNameEl) supplierNameEl.value = supplierName;
    const supplierAddressEl = document.getElementById("supplierAddressEdit");
    if (supplierAddressEl) supplierAddressEl.value = supplierAddress;
    const supplierPhoneEl = document.getElementById("supplierPhoneEdit");
    if (supplierPhoneEl) supplierPhoneEl.value = supplierPhone;
    const supplierLocationEl = document.getElementById("supplierLocationEdit");
    if (supplierLocationEl) supplierLocationEl.value = supplierLocation;
    const supplierPaymentEl = document.getElementById("supplierPaymentTermsEdit");
    if (supplierPaymentEl) supplierPaymentEl.value = supplierPayment;
    const supplierEmailEl = document.getElementById("supplierEmailEdit");
    if (supplierEmailEl) supplierEmailEl.value = supplierEmail;

    const routeBase = document.getElementById("updateRouteBase")?.value;
    if (routeBase) {
        const editFormEl = document.getElementById("editSupplierForm");
        if (editFormEl) editFormEl.action = routeBase + "/" + supplierId;
    }
}
