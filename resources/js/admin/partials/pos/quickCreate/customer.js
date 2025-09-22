function setupQuickCreateCustomerButton() {
    const customerSelectContainer =
        document.getElementById("customer_id")?.parentElement;
    if (!customerSelectContainer) return;

    const addButton = document.createElement("button");
    addButton.type = "button";
    addButton.className = "btn btn-sm btn-primary ms-2";
    addButton.innerHTML = '<i class="ti ti-plus fs-3"></i>';
    addButton.title = "Create New Customer";
    addButton.setAttribute("data-bs-toggle", "modal");
    addButton.setAttribute("data-bs-target", "#quickCreateCustomerModal");

    const inputGroup = document.createElement("div");
    inputGroup.className = "d-flex align-items-center";

    const selectElement = customerSelectContainer.querySelector("select");
    if (!selectElement) return;

    selectElement.parentNode.removeChild(selectElement);

    inputGroup.appendChild(selectElement);
    inputGroup.appendChild(addButton);
    customerSelectContainer.appendChild(inputGroup);
}

function setupQuickCreateCustomerForm() {
    const customerForm = document.getElementById("quickCreateCustomerForm");
    if (!customerForm) return;

    customerForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        const url = form.getAttribute("action");
        if (!url) {
            console.error("Form action URL is missing");
            InventMagApp.showToast("Error", "Form configuration error", "error");
            return;
        }

        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;
        if (!csrfToken) {
            console.error("CSRF token not found");
            InventMagApp.showToast("Error", "Security token missing", "error");
            return;
        }

        fetch(url, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    const customerSelect =
                        document.getElementById("customer_id");
                    if (customerSelect) {
                        const newOption = new Option(
                            data.customer.name,
                            data.customer.id
                        );
                        newOption.setAttribute(
                            "data-payment-terms",
                            data.customer.payment_terms
                        );
                        customerSelect.add(newOption);
                        customerSelect.value = data.customer.id;
                    }

                    const modal = document.getElementById(
                        "quickCreateCustomerModal"
                    );
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    }

                    form.reset();

                    InventMagApp.showToast(
                        "Success",
                        "Customer created successfully",
                        "success"
                    );
                } else {
                    InventMagApp.showToast(
                        "Error",
                        data.message || "Failed to create customer",
                        "error"
                    );
                    console.error("Error response:", data);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                InventMagApp.showToast(
                    "Error",
                    "An error occurred while creating the customer",
                    "error"
                );
            });
    });
}

export function initQuickCreateCustomer() {
    setupQuickCreateCustomerButton();
    setupQuickCreateCustomerForm();
}