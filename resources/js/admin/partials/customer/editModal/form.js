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
    document.getElementById("customerId").value = customerId;
    document.getElementById("customerNameEdit").value = customerName;
    document.getElementById("customerAddressEdit").value =
        customerAddress;
    document.getElementById("customerPhoneEdit").value = customerPhone;
    document.getElementById("customerPaymentTermsEdit").value =
        customerPayment;
    document.getElementById("customerEmailEdit").value = customerEmail;

    // Set form action
    const routeBase = document.getElementById("updateRouteBase").value;
    document.getElementById("editCustomerForm").action =
        routeBase + "/" + customerId;
}
