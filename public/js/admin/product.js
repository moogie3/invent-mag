document.addEventListener("DOMContentLoaded", function () {
    // Initialize modals (if present)
    initModals();

    // Initialize expiry checkbox toggle functionality
    initExpiryCheckbox();

    // Initialize flatpickr
    initFlatpickr();

    // Initialize product modal details + print
    initProductModal();
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
    const hasExpiryCheckbox = document.getElementById("has_expiry");
    const expiryDateContainer = document.getElementById(
        "expiry_date_container"
    );

    if (hasExpiryCheckbox && expiryDateContainer) {
        expiryDateContainer.style.display = hasExpiryCheckbox.checked
            ? "block"
            : "none";

        hasExpiryCheckbox.addEventListener("change", function () {
            expiryDateContainer.style.display = this.checked ? "block" : "none";

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
    if (typeof flatpickr !== "function") {
        console.error(
            "Flatpickr is not loaded. Please include the Flatpickr library."
        );
        return;
    }

    const expiryDateInput = document.querySelector("input[name='expiry_date']");

    if (expiryDateInput) {
        flatpickr(expiryDateInput, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d-m-Y",
            allowInput: true,
            defaultDate: expiryDateInput.value || null,
        });
    }
}

// NEW: Initializes the product modal loading + print button
function initProductModal() {
    const printButton = document.getElementById("productModalPrint");

    if (printButton) {
        printButton.addEventListener("click", handleProductModalPrint);
    }
}

// Function to load product details into modal
function loadProductDetails(id) {
    const viewProductModalContent = document.getElementById(
        "viewProductModalContent"
    );
    const productModalEdit = document.getElementById("productModalEdit");

    if (!viewProductModalContent || !productModalEdit) {
        console.error("Modal content or edit button not found!");
        return;
    }

    productModalEdit.href = `/admin/product/edit/${id}`;

    viewProductModalContent.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading product details...</p>
        </div>
    `;

    fetch(`/admin/product/modal-view/${id}`)
        .then((response) => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
        })
        .then((data) => {
            const template = document.getElementById(
                "productModalViewTemplate"
            ).innerHTML;
            viewProductModalContent.innerHTML = template;

            // Fill in product details
            setText("productName", data.name);
            setText("productCode", `Code: ${data.code}`);

            // Stock status
            const stockStatusElement = document.getElementById("stockStatus");
            const threshold = data.low_stock_threshold || 10;
            if (data.stock_quantity <= threshold) {
                setBadge(stockStatusElement, "Low Stock", "bg-danger-lt");
            } else {
                setBadge(stockStatusElement, "In Stock", "bg-success-lt");
            }

            // Image
            document.getElementById("productImage").src =
                data.image || "/images/default-product.png";

            // Basic info
            setText("productCategory", data.category?.name || "N/A");
            setText("productUnit", data.unit?.symbol || "N/A");
            setText("productQuantity", data.stock_quantity);

            // Threshold + note
            const thresholdElement =
                document.getElementById("productThreshold");
            const thresholdNoteElement = document.getElementById(
                "thresholdDefaultNote"
            );

            if (data.low_stock_threshold) {
                setText("productThreshold", data.low_stock_threshold);
                if (thresholdNoteElement)
                    thresholdNoteElement.style.display = "none";
            } else {
                setText("productThreshold", "10");
                if (thresholdNoteElement)
                    thresholdNoteElement.textContent = " (default)";
            }

            // Supplier & warehouse
            setText("productSupplier", data.supplier?.name || "N/A");
            setText("productWarehouse", data.warehouse?.name || "N/A");

            // Expiry date
            if (data.has_expiry && data.expiry_date) {
                const expiryDate = new Date(data.expiry_date);
                const formattedDate = expiryDate.toLocaleDateString("en-GB", {
                    day: "2-digit",
                    month: "long",
                    year: "numeric",
                });
                const badge = getExpiryBadge(data.expiry_date);
                document.getElementById("productExpiry").innerHTML =
                    formattedDate + badge;
            } else {
                setText("productExpiry", "N/A");
            }

            // Pricing
            setText("productPrice", data.formatted_price || data.price);
            setText(
                "productSellingPrice",
                data.formatted_selling_price || data.selling_price
            );

            // Margin
            const margin = (
                ((data.selling_price - data.price) / data.price) *
                100
            ).toFixed(2);
            setText("productMargin", margin + "%");

            // Description
            const descContainer = document.getElementById(
                "productDescriptionContainer"
            );
            const descElement = document.getElementById("productDescription");
            if (data.description) {
                setText("productDescription", data.description);
            } else if (descContainer) {
                descContainer.style.display = "none";
            }
        })
        .catch((error) => {
            viewProductModalContent.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="ti ti-alert-circle me-2"></i> Error loading product details: ${error.message}
                </div>
            `;
            console.error("Error loading product details:", error);
        });
}

function handleProductModalPrint() {
    const printContent = document.getElementById(
        "viewProductModalContent"
    ).innerHTML;
    const originalContent = document.body.innerHTML;

    document.body.innerHTML = `
        <div class="container print-container">
            <div class="card">
                <div class="card-body">
                    ${printContent}
                </div>
            </div>
        </div>
    `;

    window.print();
    document.body.innerHTML = originalContent;

    // Optional: reload to re-initialize everything
    setTimeout(() => window.location.reload(), 100);
}

// Helper functions
function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

function setBadge(el, text, badgeClass) {
    if (el) {
        el.className = `badge fs-6 ${badgeClass}`;
        el.textContent = text;
    }
}

function getExpiryBadge(expiryDateStr) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const expiryDate = new Date(expiryDateStr);
    expiryDate.setHours(0, 0, 0, 0);

    const diffTime = expiryDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return ' <span class="badge bg-danger">Expired</span>';
    } else if (diffDays <= 7) {
        return ` <span class="badge bg-warning">Expiring Soon - ${diffDays}d</span>`;
    }
    return "";
}

// Expose globally if needed
window.loadProductDetails = loadProductDetails;
