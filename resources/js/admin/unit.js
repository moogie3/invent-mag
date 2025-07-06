document.addEventListener("DOMContentLoaded", function () {
    const editUnitModal = document.getElementById("editUnitModal");
    const createUnitModal = document.getElementById("createUnitModal");

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
                    bsModal._element.addEventListener('hidden.bs.modal', function handler() {
                        bsModal._element.removeEventListener('hidden.bs.modal', handler);
                        form.reset(); // Clear form fields
                        // Explicitly remove any remaining modal backdrops
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        backdrops.forEach(backdrop => backdrop.remove());
                        location.reload();
                    });
                    bsModal.hide();
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
    if (editUnitModal) {
        editUnitModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;

            const unitId = button.getAttribute("data-id");
            const unitSymbol = button.getAttribute("data-symbol");
            const unitName = button.getAttribute("data-name");

            document.getElementById("unitId").value = unitId;
            document.getElementById("unitSymbolEdit").value = unitSymbol;
            document.getElementById("unitNameEdit").value = unitName;

            const routeBase = document.getElementById("updateRouteBase").value;
            document.getElementById("editUnitForm").action = routeBase + "/" + unitId;
        });

        // Add submit listener for edit form
        const editUnitForm = document.getElementById("editUnitForm");
        if (editUnitForm) {
            editUnitForm.addEventListener("submit", (event) => handleFormSubmission(event, editUnitModal));
        }
    }

    // Add submit listener for create form
    const createUnitForm = document.getElementById("createUnitForm");
    if (createUnitForm) {
        createUnitForm.addEventListener("submit", (event) => handleFormSubmission(event, createUnitModal, true));
    }

    // Toast notification functions (copied from user.js)
        // Function to handle form submission via AJAX
});
