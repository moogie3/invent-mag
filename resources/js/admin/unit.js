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
        console.log("editUnitModal element found."); // Debug
        editUnitModal.addEventListener("show.bs.modal", function (event) {
            console.log("show.bs.modal event fired for editUnitModal."); // Debug
            const button = event.relatedTarget;
            console.log("event.relatedTarget:", button); // Debug

            if (button) {
                const unitId = button.getAttribute("data-id");
                const unitSymbol = button.getAttribute("data-symbol");
                const unitName = button.getAttribute("data-name");

                console.log("unitId:", unitId); // Debug
                console.log("unitSymbol:", unitSymbol); // Debug
                console.log("unitName:", unitName); // Debug

                const unitIdInput = document.getElementById("unitId");
                const unitSymbolEditInput = document.getElementById("unitSymbolEdit");
                const unitNameEditInput = document.getElementById("unitNameEdit");

                console.log("unitIdInput:", unitIdInput); // Debug
                console.log("unitSymbolEditInput:", unitSymbolEditInput); // Debug
                console.log("unitNameEditInput:", unitNameEditInput); // Debug

                if (unitIdInput) {
                    unitIdInput.value = unitId;
                    console.log("unitIdInput.value after set:", unitIdInput.value); // Debug
                }
                if (unitSymbolEditInput) {
                    unitSymbolEditInput.value = unitSymbol;
                    console.log("unitSymbolEditInput.value after set:", unitSymbolEditInput.value); // Debug
                }
                if (unitNameEditInput) {
                    unitNameEditInput.value = unitName;
                    console.log("unitNameEditInput.value after set:", unitNameEditInput.value); // Debug
                }

                const routeBase = document.getElementById("updateRouteBase").value;
                document.getElementById("editUnitForm").action = routeBase + "/" + unitId;
            } else {
                console.warn("event.relatedTarget is null for editUnitModal."); // Debug
            }
        });

        // Add submit listener for edit form
        const editUnitForm = document.getElementById("editUnitForm");
        if (editUnitForm) {
            editUnitForm.addEventListener("submit", (event) => handleFormSubmission(event, editUnitModal));
        }
    } else {
        console.log("editUnitModal element NOT found."); // Debug
    }

    // Add submit listener for create form
    const createUnitForm = document.getElementById("createUnitForm");
    if (createUnitForm) {
        createUnitForm.addEventListener("submit", (event) => handleFormSubmission(event, createUnitModal, true));
    }

    // Toast notification functions (copied from user.js)
        // Function to handle form submission via AJAX
});
