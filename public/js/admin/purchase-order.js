document.addEventListener("DOMContentLoaded", function () {
    // Initialize modals for low stock and expiring soon
    initModals();

    // Initialize expiry checkbox toggle functionality
    initExpiryCheckbox();

    // Initialize flatpickr for date fields
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
    // Toggle visibility of expiry date field based on checkbox
    const hasExpiryCheckboxes = document.querySelectorAll(
        "input[name='has_expiry']"
    );

    hasExpiryCheckboxes.forEach((checkbox) => {
        if (checkbox) {
            const form = checkbox.closest("form");
            const expiryDateField = form.querySelector(".expiry-date-field");

            checkbox.addEventListener("change", function () {
                expiryDateField.style.display = this.checked ? "block" : "none";
            });
        }
    });
}

function initFlatpickr() {
    // Get all expiry date fields (both in edit and create forms)
    const expiryDateFields = document.querySelectorAll(
        "input[name='expiry_date']"
    );

    // Initialize flatpickr for each expiry date field
    expiryDateFields.forEach((field) => {
        if (field) {
            flatpickr(field, {
                dateFormat: "Y-m-d", // Database format
                altInput: true,
                altFormat: "d-m-Y", // Fancy alternate format
                allowInput: true, // Allow typing manually
            });
        }
    });
}
