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
        console.log("editCategoryModal element found."); // Debug
        editCategoryModal.addEventListener("show.bs.modal", function (event) {
            console.log("show.bs.modal event fired for editCategoryModal."); // Debug
            const button = event.relatedTarget;
            console.log("event.relatedTarget:", button); // Debug

            if (button) {
                const categoryId = button.getAttribute("data-id");
                const categoryName = button.getAttribute("data-name");
                const categoryDescription = button.getAttribute("data-description");

                console.log("categoryId:", categoryId); // Debug
                console.log("categoryName:", categoryName); // Debug
                console.log("categoryDescription:", categoryDescription); // Debug

                const categoryIdInput = document.getElementById("categoryId");
                const categoryNameEditInput = document.getElementById("categoryNameEdit");
                const categoryDescriptionEditInput = document.getElementById("categoryDescriptionEdit");

                console.log("categoryIdInput:", categoryIdInput); // Debug
                console.log("categoryNameEditInput:", categoryNameEditInput); // Debug
                console.log("categoryDescriptionEditInput:", categoryDescriptionEditInput); // Debug

                if (categoryIdInput) {
                    categoryIdInput.value = categoryId;
                    console.log("categoryIdInput.value after set:", categoryIdInput.value); // Debug
                }
                if (categoryNameEditInput) {
                    categoryNameEditInput.value = categoryName;
                    console.log("categoryNameEditInput.value after set:", categoryNameEditInput.value); // Debug
                }
                if (categoryDescriptionEditInput) {
                    categoryDescriptionEditInput.value = categoryDescription;
                    console.log("categoryDescriptionEditInput.value after set:", categoryDescriptionEditInput.value); // Debug
                }

                const routeBase = document.getElementById("updateRouteBase").value;
                document.getElementById("editCategoryForm").action = routeBase + "/" + categoryId;
            } else {
                console.warn("event.relatedTarget is null for editCategoryModal."); // Debug
            }
        });

        // Add submit listener for edit form
        const editCategoryForm = document.getElementById("editCategoryForm");
        if (editCategoryForm) {
            editCategoryForm.addEventListener("submit", (event) => handleFormSubmission(event, editCategoryModal));
        }
    } else {
        console.log("editCategoryModal element NOT found."); // Debug
    }

    // Add submit listener for create form
    const createCategoryForm = document.getElementById("createCategoryForm");
    if (createCategoryForm) {
        createCategoryForm.addEventListener("submit", (event) => handleFormSubmission(event, createCategoryModal, true));
    }

    // Toast notification functions (copied from user.js)
    });
