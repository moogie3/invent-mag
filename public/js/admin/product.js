document.addEventListener("DOMContentLoaded", function () {
    // Initialize modals
    initModals();

    // Initialize expiry checkbox toggle functionality
    initExpiryCheckbox();

    // Initialize flatpickr
    initFlatpickr();
});

function initModals() {
    // Low stock modal initialization
    const viewLowStockBtn = document.getElementById("viewLowStock");
    if (viewLowStockBtn) {
        const lowStockModalEl = document.getElementById("lowStockModal");
        const lowStockModal = new bootstrap.Modal(lowStockModalEl);

        viewLowStockBtn.addEventListener("click", function (e) {
            e.preventDefault();
            lowStockModal.show();
        });
    }

    // Expiring soon modal initialization
    const viewExpiringSoonBtn = document.getElementById("viewExpiringSoon");
    if (viewExpiringSoonBtn) {
        const expiringSoonModalEl =
            document.getElementById("expiringSoonModal");
        const expiringSoonModal = new bootstrap.Modal(expiringSoonModalEl);

        viewExpiringSoonBtn.addEventListener("click", function (e) {
            e.preventDefault();
            expiringSoonModal.show();
        });
    }
}

function initExpiryCheckbox() {
    // Handle multiple forms - both in edit and create modals
    const hasExpiryCheckboxes = document.querySelectorAll(
        "input[name='has_expiry']"
    );

    hasExpiryCheckboxes.forEach((checkbox) => {
        if (checkbox) {
            // Find the closest form to this checkbox
            const form = checkbox.closest("form");
            // Find the expiry date field in this specific form
            const expiryDateField = form.querySelector(".expiry-date-field");

            if (expiryDateField) {
                // Set initial state
                expiryDateField.style.display = checkbox.checked
                    ? "block"
                    : "none";

                // Add change event
                checkbox.addEventListener("change", function () {
                    expiryDateField.style.display = this.checked
                        ? "block"
                        : "none";

                    // If showing the field, ensure flatpickr is initialized properly
                    if (this.checked) {
                        const dateInput = expiryDateField.querySelector(
                            "input[name='expiry_date']"
                        );
                        if (dateInput && dateInput._flatpickr) {
                            dateInput._flatpickr.redraw();
                        }
                    }
                });
            }
        }
    });
}

function initFlatpickr() {
    // Check if flatpickr is available
    if (typeof flatpickr !== "function") {
        console.error(
            "Flatpickr is not loaded. Please include the Flatpickr library."
        );
        return;
    }

    // Get all expiry date inputs
    const expiryDateInputs = document.querySelectorAll(
        "input[name='expiry_date']"
    );

    expiryDateInputs.forEach((input) => {
        if (input) {
            // Initialize flatpickr on this input
            flatpickr(input, {
                dateFormat: "Y-m-d", // Database format
                altInput: true, // Use an alternate input for friendly display
                altFormat: "d-m-Y", // Display format (day-month-year)
                allowInput: true, // Allow manual input
                defaultDate: input.value || null, // Use existing value if present
            });
        }
    });
}
