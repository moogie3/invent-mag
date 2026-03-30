export function populateEditModalForm(button) {
    const customerId = button.getAttribute("data-id") || "";
    const customerName = button.getAttribute("data-name") || "";
    const customerAddress = button.getAttribute("data-address") || "";
    const customerPhone =
        button.getAttribute("data-phone_number") || "";
    const customerPayment =
        button.getAttribute("data-payment_terms") || "";
    const customerEmail = button.getAttribute("data-email") || "";

    // Populate form fields
    const customerIdEl = document.getElementById("customerId");
    if (customerIdEl) customerIdEl.value = customerId;
    const customerNameEl = document.getElementById("customerNameEdit");
    if (customerNameEl) customerNameEl.value = customerName;
    const customerAddressEl = document.getElementById("customerAddressEdit");
    if (customerAddressEl) customerAddressEl.value = customerAddress;
    const customerPhoneEl = document.getElementById("customerPhoneEdit");
    if (customerPhoneEl) customerPhoneEl.value = customerPhone;
    const customerPaymentEl = document.getElementById("customerPaymentTermsEdit");
    if (customerPaymentEl) customerPaymentEl.value = customerPayment;
    const customerEmailEl = document.getElementById("customerEmailEdit");
    if (customerEmailEl) customerEmailEl.value = customerEmail;

    // Set form action
    const routeBase = document.getElementById("updateRouteBase")?.value;
    if (routeBase) {
        const editFormEl = document.getElementById("editCustomerForm");
        if (editFormEl) editFormEl.action = routeBase + "/" + customerId;
    }
}
