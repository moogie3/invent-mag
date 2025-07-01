document.addEventListener("DOMContentLoaded", function () {
    const editCategoryModal = document.getElementById("editCategoryModal");
    const createCategoryModal = document.getElementById("createCategoryModal");

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
    if (editCategoryModal) {
        editCategoryModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            const categoryId = button.getAttribute("data-id");
            const categoryName = button.getAttribute("data-name");
            const categoryDescription = button.getAttribute("data-description");

            document.getElementById("categoryId").value = categoryId;
            document.getElementById("categoryNameEdit").value = categoryName;
            document.getElementById("categoryDescriptionEdit").value = categoryDescription;

            const routeBase = document.getElementById("updateRouteBase").value;
            document.getElementById("editCategoryForm").action = routeBase + "/" + categoryId;
        });

        // Add submit listener for edit form
        const editCategoryForm = document.getElementById("editCategoryForm");
        if (editCategoryForm) {
            editCategoryForm.addEventListener("submit", (event) => handleFormSubmission(event, editCategoryModal));
        }
    }

    // Add submit listener for create form
    const createCategoryForm = document.getElementById("createCategoryForm");
    if (createCategoryForm) {
        createCategoryForm.addEventListener("submit", (event) => handleFormSubmission(event, createCategoryModal, true));
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
