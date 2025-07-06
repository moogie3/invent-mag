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
                if (bsModal) {
                    bsModal.hide();
                    // Listen for the 'hidden.bs.modal' event to ensure the modal is fully closed
                    bsModal._element.addEventListener('hidden.bs.modal', function handler() {
                        bsModal._element.removeEventListener('hidden.bs.modal', handler); // Remove the listener
                        form.reset(); // Clear form fields
                        // Explicitly remove any remaining modal backdrops
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        backdrops.forEach(backdrop => backdrop.remove());
                        location.reload();
                    });
                } else {
                    form.reset();
                    location.reload();
                }
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
    });
