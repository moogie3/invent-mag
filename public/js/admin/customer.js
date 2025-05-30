document.addEventListener("DOMContentLoaded", function () {
    const editCustomerModal = document.getElementById("editCustomerModal");

    editCustomerModal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;

        if (!button) return; // Prevent errors if button is null

        // Get supplier data from the button attributes
        const customerId = button.getAttribute("data-id") || "";
        const customerCode = button.getAttribute("data-code") || "";
        const customerName = button.getAttribute("data-name") || "";
        const customerAddress = button.getAttribute("data-address") || "";
        const customerPhone = button.getAttribute("data-phone_number") || "";
        const customerLocation = button.getAttribute("data-location") || "";
        const customerPayment = button.getAttribute("data-payment_terms") || "";

        // Populate the form fields inside the modal
        document.getElementById("customerId").value = customerId;
        document.getElementById("customerNameEdit").value = customerName;
        document.getElementById("customerAddressEdit").value = customerAddress;
        document.getElementById("customerPhoneEdit").value = customerPhone;
        document.getElementById("customerPaymentTermsEdit").value =
            customerPayment;

        // Set the form action dynamically
        const routeBase = document.getElementById("updateRouteBase").value;
        document.getElementById("editCustomerForm").action =
            routeBase + "/" + customerId;
    });
});
