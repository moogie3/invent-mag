document.addEventListener("DOMContentLoaded", function () {
    const editCustomerModal = document.getElementById("editCustomerModal");
    const createCustomerModal = document.getElementById("createCustomerModal");

    // Function to handle form submission via AJAX
    function handleFormSubmission(event, modalElement, isCreate = false) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);
        const actionUrl = form.action;
        const method = form.method;

        fetch(actionUrl, {
            method: method === 'GET' ? 'GET' : 'POST', // Ensure POST for PUT/DELETE via _method
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message, 'success');
                const bsModal = bootstrap.Modal.getInstance(modalElement);
                if (bsModal) bsModal.hide();
                form.reset(); // Clear form fields

                // Optionally, update the table dynamically instead of reloading
                // For now, we'll reload to keep it simple, but this is where dynamic update would go
                location.reload();
            } else {
                showToast('Error', data.message || 'Operation failed.', 'error');
                console.error('Form submission error:', data.errors);
            }
        })
        .catch(error => {
            console.error('Error during fetch:', error);
            showToast('Error', 'An error occurred. Please check the console.', 'error');
        });
    }

    // Event listener for edit modal show
    if (editCustomerModal) {
        editCustomerModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;

            if (!button) return; // Prevent errors if button is null

            const customerId = button.getAttribute("data-id") || "";
            const customerCode = button.getAttribute("data-code") || "";
            const customerName = button.getAttribute("data-name") || "";
            const customerAddress = button.getAttribute("data-address") || "";
            const customerPhone = button.getAttribute("data-phone_number") || "";
            const customerLocation = button.getAttribute("data-location") || "";
            const customerPayment = button.getAttribute("data-payment_terms") || "";

            document.getElementById("customerId").value = customerId;
            document.getElementById("customerNameEdit").value = customerName;
            document.getElementById("customerAddressEdit").value = customerAddress;
            document.getElementById("customerPhoneEdit").value = customerPhone;
            document.getElementById("customerPaymentTermsEdit").value = customerPayment;

            const routeBase = document.getElementById("updateRouteBase").value;
            document.getElementById("editCustomerForm").action = routeBase + "/" + customerId;
        });

        // Add submit listener for edit form
        const editCustomerForm = document.getElementById("editCustomerForm");
        if (editCustomerForm) {
            editCustomerForm.addEventListener("submit", (event) => handleFormSubmission(event, editCustomerModal));
        }
    }

    // Add submit listener for create form
    const createCustomerForm = document.getElementById("createCustomerForm");
    if (createCustomerForm) {
        createCustomerForm.addEventListener("submit", (event) => handleFormSubmission(event, createCustomerModal, true));
    }

    // Toast notification functions (copied from user.js)
    function showToast(title, message, type = "info", duration = 4000) {
        let toastContainer = document.getElementById("toast-container");
        if (!toastContainer) {
            toastContainer = document.createElement("div");
            toastContainer.id = "toast-container";
            toastContainer.className =
                "toast-container position-fixed bottom-0 end-0 p-3";
            toastContainer.style.zIndex = "1050";
            document.body.appendChild(toastContainer);

            if (!document.getElementById("toast-styles")) {
                const style = document.createElement("style");
                style.id = "toast-styles";
                style.textContent = `
                        .toast-enter {
                            transform: translateX(100%);
                            opacity: 0;
                        }
                        .toast-show {
                            transform: translateX(0);
                            opacity: 1;
                            transition: transform 0.3s ease, opacity 0.3s ease;
                        }
                        .toast-exit {
                            transform: translateX(100%);
                            opacity: 0;
                            transition: transform 0.3s ease, opacity 0.3s ease;
                        }
                    `;
                document.head.appendChild(style);
            }
        }

        const toast = document.createElement("div");
        toast.className =
            "toast toast-enter align-items-center text-white bg-" +
            getToastColor(type) +
            " border-0";
        toast.setAttribute("role", "alert");
        toast.setAttribute("aria-live", "assertive");
        toast.setAttribute("aria-atomic", "true");

        toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong>: ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;

        toastContainer.appendChild(toast);

        void toast.offsetWidth;

        toast.classList.add("toast-show");

        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: duration,
        });
        bsToast.show();

        const closeButton = toast.querySelector(".btn-close");
        closeButton.addEventListener("click", () => {
            hideToast(toast);
        });

        const hideTimeout = setTimeout(() => {
            hideToast(toast);
        }, duration);

        toast._hideTimeout = hideTimeout;
    }

    function hideToast(toast) {
        if (toast._hideTimeout) {
            clearTimeout(toast._hideTimeout);
        }

        toast.classList.remove("toast-show");
        toast.classList.add("toast-exit");

        setTimeout(() => {
            toast.remove();
        }, 300);
    }

    function getToastColor(type) {
        switch (type) {
            case "success":
                return "success";
            case "error":
                return "danger";
            case "warning":
                return "warning";
            default:
                return "info";
        }
    }
});