document.addEventListener("DOMContentLoaded", function () {
    // Initialize modals (if present)
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
    // Grab the single checkbox + container by their IDs
    const hasExpiryCheckbox = document.getElementById("has_expiry");
    const expiryDateContainer = document.getElementById(
        "expiry_date_container"
    );

    if (hasExpiryCheckbox && expiryDateContainer) {
        // Set initial visibility on page load
        expiryDateContainer.style.display = hasExpiryCheckbox.checked
            ? "block"
            : "none";

        // Toggle visibility on checkbox change
        hasExpiryCheckbox.addEventListener("change", function () {
            expiryDateContainer.style.display = this.checked ? "block" : "none";

            // Redraw flatpickr if visible
            if (this.checked) {
                const dateInput = expiryDateContainer.querySelector(
                    "input[name='expiry_date']"
                );
                if (dateInput && dateInput._flatpickr) {
                    dateInput._flatpickr.redraw();
                }
            }
        });
    }
}

function initFlatpickr() {
    // Check if flatpickr is loaded
    if (typeof flatpickr !== "function") {
        console.error(
            "Flatpickr is not loaded. Please include the Flatpickr library."
        );
        return;
    }

    const expiryDateInput = document.querySelector("input[name='expiry_date']");

    if (expiryDateInput) {
        flatpickr(expiryDateInput, {
            dateFormat: "Y-m-d", // Database format
            altInput: true, // Friendly display format
            altFormat: "d-m-Y",
            allowInput: true,
            defaultDate: expiryDateInput.value || null,
        });
    }
}
