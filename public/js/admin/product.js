document.addEventListener("DOMContentLoaded", function () {
    // Initialize modals (if present)
    initModals();

    // Initialize expiry checkbox toggle functionality
    initExpiryCheckbox();

    // Initialize flatpickr
    initFlatpickr();

    // Initialize product modal details + print
    initProductModal();

    // Initialize bulk selection functionality
    initBulkSelection();
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

// NEW: Initialize bulk selection functionality
function initBulkSelection() {
    const maxAttempts = 5;
    let attempts = 0;

    const tryInit = () => {
        attempts++;

        const selectAllCheckbox = document.getElementById("selectAll");
        const rowCheckboxes = document.querySelectorAll(".row-checkbox");
        const bulkActionsBar = document.getElementById("bulkActionsBar");
        const selectedCount = document.getElementById("selectedCount");

        if (
            !selectAllCheckbox ||
            rowCheckboxes.length === 0 ||
            !bulkActionsBar ||
            !selectedCount
        ) {
            if (attempts < maxAttempts) {
                console.log(
                    `Bulk selection init attempt ${attempts}/${maxAttempts} - retrying...`
                );
                setTimeout(tryInit, 300);
                return;
            }

            console.warn(
                "Bulk selection elements not found after",
                maxAttempts,
                "attempts"
            );
            return;
        }

        setupBulkSelectionListeners(
            selectAllCheckbox,
            rowCheckboxes,
            bulkActionsBar,
            selectedCount
        );
        updateBulkUI(
            selectAllCheckbox,
            rowCheckboxes,
            bulkActionsBar,
            selectedCount
        );
        console.log("Bulk selection initialized successfully");
    };

    tryInit();
}

function setupBulkSelectionListeners(
    selectAllCheckbox,
    rowCheckboxes,
    bulkActionsBar,
    selectedCount
) {
    // Select all functionality
    selectAllCheckbox.addEventListener("change", (e) => {
        const isChecked = e.target.checked;
        rowCheckboxes.forEach((checkbox) => {
            checkbox.checked = isChecked;
        });
        updateBulkUI(
            selectAllCheckbox,
            rowCheckboxes,
            bulkActionsBar,
            selectedCount
        );
    });

    // Individual checkbox changes
    rowCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", () => {
            updateSelectAllState(selectAllCheckbox, rowCheckboxes);
            updateBulkActionsBar(rowCheckboxes, bulkActionsBar, selectedCount);
        });
    });
}

function updateSelectAllState(selectAllCheckbox, rowCheckboxes) {
    const totalCheckboxes = rowCheckboxes.length;
    const checkedCheckboxes = document.querySelectorAll(
        ".row-checkbox:checked"
    ).length;

    if (checkedCheckboxes === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (checkedCheckboxes === totalCheckboxes) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
        selectAllCheckbox.checked = false;
    }
}

function updateBulkActionsBar(rowCheckboxes, bulkActionsBar, selectedCount) {
    const checkedCount = document.querySelectorAll(
        ".row-checkbox:checked"
    ).length;

    if (checkedCount > 0) {
        bulkActionsBar.style.display = "block";
        selectedCount.textContent = checkedCount;
    } else {
        bulkActionsBar.style.display = "none";
    }
}

function updateBulkUI(
    selectAllCheckbox,
    rowCheckboxes,
    bulkActionsBar,
    selectedCount
) {
    updateSelectAllState(selectAllCheckbox, rowCheckboxes);
    updateBulkActionsBar(rowCheckboxes, bulkActionsBar, selectedCount);
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

// BULK ACTION FUNCTIONS
window.clearProductSelection = function () {
    const selectAllCheckbox = document.getElementById("selectAll");
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    const bulkActionsBar = document.getElementById("bulkActionsBar");
    const selectedCount = document.getElementById("selectedCount");

    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }

    rowCheckboxes.forEach((checkbox) => {
        checkbox.checked = false;
    });

    if (bulkActionsBar) {
        bulkActionsBar.style.display = "none";
    }
};

window.getSelectedProductIds = function () {
    return Array.from(document.querySelectorAll(".row-checkbox:checked")).map(
        (cb) => cb.value
    );
};

window.bulkDeleteProducts = function () {
    console.log("bulkDeleteProducts function called");

    const selected = getSelectedProductIds();
    console.log("Selected IDs:", selected);

    // Validate selection
    if (!selected || selected.length === 0) {
        showToast(
            "Warning",
            "Please select at least one product to delete.",
            "warning"
        );
        return;
    }

    // Update modal with selection count
    const bulkDeleteCount = document.getElementById("bulkDeleteCount");
    if (bulkDeleteCount) {
        bulkDeleteCount.textContent = selected.length;
    }

    // Show confirmation modal
    const bulkDeleteModal = new bootstrap.Modal(
        document.getElementById("bulkDeleteModal")
    );
    bulkDeleteModal.show();

    // Handle confirmation button
    const confirmBtn = document.getElementById("confirmBulkDeleteBtn");
    if (confirmBtn) {
        // Remove any existing event listeners by cloning the button
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener("click", function () {
            console.log("Confirm button clicked");
            performBulkDelete(selected, this, bulkDeleteModal);
        });
    }
};

window.bulkExportProducts = function () {
    const selected = getSelectedProductIds();

    if (selected.length === 0) {
        showToast(
            "Warning",
            "Please select at least one product to export.",
            "warning"
        );
        return;
    }

    const submitBtn = document.querySelector(
        '[onclick="bulkExportProducts()"]'
    );
    const originalText = submitBtn ? submitBtn.innerHTML : "";

    if (submitBtn) {
        submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
        submitBtn.disabled = true;
    }

    // Create form and submit for export
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/product/bulk-export";
    form.style.display = "none";

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const csrfInput = document.createElement("input");
        csrfInput.type = "hidden";
        csrfInput.name = "_token";
        csrfInput.value = csrfToken.getAttribute("content");
        form.appendChild(csrfInput);
    }

    // Add selected IDs
    selected.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "ids[]";
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();

    // Reset button after a delay
    setTimeout(() => {
        if (submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
        document.body.removeChild(form);
    }, 2000);
};

// Placeholder for bulk update stock function
window.bulkUpdateStock = function () {
    const selected = getSelectedProductIds();

    if (selected.length === 0) {
        showToast(
            "Warning",
            "Please select at least one product to update stock.",
            "warning"
        );
        return;
    }

    // Show the bulk update modal
    const bulkUpdateModal = new bootstrap.Modal(
        document.getElementById("bulkUpdateStockModal")
    );
    bulkUpdateModal.show();

    // Load selected products from the existing table
    loadBulkUpdateProductsFromTable(selected);
};

function performBulkDelete(selectedIds, confirmButton, modal) {
    console.log("performBulkDelete called with IDs:", selectedIds);

    if (!selectedIds || selectedIds.length === 0) return;

    // Show loading state
    const originalText = confirmButton.innerHTML;
    confirmButton.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Deleting...
    `;
    confirmButton.disabled = true;

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error("CSRF token not found");
        showToast(
            "Error",
            "Security token not found. Please refresh the page.",
            "error"
        );
        resetButton(confirmButton, originalText);
        return;
    }

    console.log("CSRF token found:", csrfToken.getAttribute("content"));

    // Make the API request
    fetch("/admin/product/bulk-delete", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken.getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify({
            ids: selectedIds,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Close modal
                modal.hide();

                // Show success message
                showToast(
                    "Success",
                    `${
                        data.deleted_count || selectedIds.length
                    } product(s) deleted successfully!`,
                    "success"
                );

                // Clear selection
                clearProductSelection();

                // Reload page after short delay
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast(
                    "Error",
                    data.message || "Failed to delete products.",
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            showToast(
                "Error",
                "An error occurred while deleting products.",
                "error"
            );
        })
        .finally(() => {
            // Reset button state
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
        });
}

function resetButton(button, originalText) {
    if (button) {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Toast notification function
function showToast(title, message, type = "info", duration = 4000) {
    // Create a toast container if it doesn't exist
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
        toastContainer = document.createElement("div");
        toastContainer.id = "toast-container";
        toastContainer.className =
            "toast-container position-fixed bottom-0 end-0 p-3";
        // Set higher z-index to appear above Bootstrap modals (which use 1055)
        toastContainer.style.zIndex = "1060";
        document.body.appendChild(toastContainer);

        // Add animation styles once
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
                /* Ensure toast container is above modal backdrop */
                #toast-container {
                    z-index: 1060 !important;
                }
                /* Individual toast z-index */
                .toast {
                    z-index: 1061 !important;
                }
            `;
            document.head.appendChild(style);
        }
    }

    // Create toast element
    const toast = document.createElement("div");
    toast.className =
        "toast toast-enter align-items-center text-white bg-" +
        getToastColor(type) +
        " border-0";
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");
    // Ensure individual toast has high z-index
    toast.style.zIndex = "1061";

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong>: ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    toastContainer.appendChild(toast);

    // Force reflow to ensure animation works
    void toast.offsetWidth;

    // Show with animation
    toast.classList.add("toast-show");

    // Initialize Bootstrap toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: duration,
    });
    bsToast.show();

    // Handle close button clicks
    const closeButton = toast.querySelector(".btn-close");
    closeButton.addEventListener("click", () => {
        hideToast(toast);
    });

    // Auto hide after duration
    const hideTimeout = setTimeout(() => {
        hideToast(toast);
    }, duration);

    // Store timeout on toast element for cleanup
    toast._hideTimeout = hideTimeout;
}

// Helper function to hide toast with animation
function hideToast(toast) {
    // Clear any existing timeout
    if (toast._hideTimeout) {
        clearTimeout(toast._hideTimeout);
    }

    // Add exit animation
    toast.classList.remove("toast-show");
    toast.classList.add("toast-exit");

    // Remove after animation completes
    setTimeout(() => {
        toast.remove();
    }, 300);
}

// Helper function to get the appropriate Bootstrap color class
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

// Expose globally if needed
window.loadProductDetails = loadProductDetails;

window.bulkUpdateStock = function () {
    const selected = getSelectedProductIds();

    if (selected.length === 0) {
        showToast(
            "Warning",
            "Please select at least one product to update stock.",
            "warning"
        );
        return;
    }

    // Show the bulk update modal
    const bulkUpdateModal = new bootstrap.Modal(
        document.getElementById("bulkUpdateStockModal")
    );
    bulkUpdateModal.show();

    // Load selected products from the existing table instead of API call
    loadBulkUpdateProductsFromTable(selected);
};

function loadBulkUpdateProductsFromTable(productIds) {
    console.log("=== DEBUG: loadBulkUpdateProductsFromTable ===");
    console.log("Raw productIds:", productIds);

    const contentContainer = document.getElementById("bulkUpdateStockContent");
    const updateCountElement = document.getElementById("updateStockCount");

    if (!contentContainer || !updateCountElement) {
        console.error("Bulk update modal elements not found");
        return;
    }

    // Ensure productIds are valid integers
    const validProductIds = productIds
        .map((id) => {
            const parsed = parseInt(id);
            console.log(`Converting ID: "${id}" -> ${parsed}`);
            return parsed;
        })
        .filter((id) => {
            const isValid = !isNaN(id) && id > 0;
            if (!isValid) {
                console.warn(`Filtered out invalid ID: ${id}`);
            }
            return isValid;
        });

    console.log("Valid product IDs:", validProductIds);

    if (validProductIds.length === 0) {
        console.error("No valid product IDs found");
        contentContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="ti ti-alert-circle me-2"></i>
                No valid products selected. Please try again.
            </div>
        `;
        return;
    }

    // Update count
    updateCountElement.textContent = validProductIds.length;

    // Extract product data from existing table rows
    const products = [];
    validProductIds.forEach((productId) => {
        console.log(`Looking for row with data-id="${productId}"`);

        const tableRow = document.querySelector(`tr[data-id="${productId}"]`);
        console.log(`Row found for product ${productId}:`, !!tableRow);

        if (tableRow) {
            // Debug each selector
            const nameElement = tableRow.querySelector(".sort-name");
            const codeElement = tableRow.querySelector(".sort-code");
            const quantityElement = tableRow.querySelector(".sort-quantity");
            const imageElement = tableRow.querySelector(".sort-image img");

            // Extract text content more carefully
            const name = nameElement?.textContent?.trim() || "Unknown Product";
            const code = codeElement?.textContent?.trim() || "N/A";
            const quantityText = quantityElement?.textContent?.trim() || "0";

            // Parse stock quantity from text (might contain additional text like "Low Stock")
            const stockMatch = quantityText.match(/^\d+/);
            const stockQuantity = stockMatch ? parseInt(stockMatch[0]) : 0;

            console.log(`Product ${productId} extracted data:`, {
                name: name,
                code: code,
                quantityText: quantityText,
                stockQuantity: stockQuantity,
                imageSrc: imageElement?.src,
            });

            const product = {
                id: productId, // Keep as integer
                name: name,
                code: code,
                stock_quantity: stockQuantity,
                image_src: imageElement?.src || "/images/default-product.png",
            };

            console.log(`Product ${productId} final data:`, product);
            products.push(product);
        } else {
            console.error(`No table row found for product ID: ${productId}`);
            // Debug: show what rows actually exist
            const allRows = document.querySelectorAll("tr[data-id]");
            console.log(
                "All table rows with data-id:",
                Array.from(allRows).map((row) => ({
                    id: row.dataset.id,
                    element: row,
                }))
            );
        }
    });

    console.log("Final products array:", products);

    if (products.length === 0) {
        console.error("No products extracted from table");
        contentContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="ti ti-alert-circle me-2"></i>
                Could not find product data in the table. Please refresh the page and try again.
                <br><small>Debug: Looking for IDs: ${validProductIds.join(
                    ", "
                )}</small>
            </div>
        `;
        return;
    }

    // Render products using the extracted data
    renderBulkUpdateProducts(products);
    initializeBulkUpdateHandlers();
}

// Improved function to ensure original stock is properly set
function renderBulkUpdateProducts(products) {
    const contentContainer = document.getElementById("bulkUpdateStockContent");
    const template = document.getElementById("stockUpdateRowTemplate");

    console.log("=== DEBUG: renderBulkUpdateProducts ===");
    console.log("Products to render:", products);
    console.log("Template found:", !!template);
    console.log("Container found:", !!contentContainer);

    if (!template) {
        console.error("Stock update row template not found");
        return;
    }

    contentContainer.innerHTML = "";

    products.forEach((product, index) => {
        console.log(`Rendering product ${index}:`, product);

        const row = template.cloneNode(true);
        row.style.display = "block";
        row.id = `stock-row-${product.id}`; // Give unique ID

        // IMPORTANT: Ensure product ID is properly set as string
        row.dataset.productId = product.id.toString();

        // Add the stock-update-row class if it's not already there
        if (!row.classList.contains("stock-update-row")) {
            row.classList.add("stock-update-row");
        }

        // Fill product data
        const img = row.querySelector(".product-image");
        const name = row.querySelector(".product-name");
        const code = row.querySelector(".product-code");
        const currentStock = row.querySelector(".current-stock");
        const newStockInput = row.querySelector(".new-stock-input");

        console.log(`Product ${product.id} elements found:`, {
            img: !!img,
            name: !!name,
            code: !!code,
            currentStock: !!currentStock,
            newStockInput: !!newStockInput,
        });

        // Use the same image source as in the table
        if (img) {
            img.src = product.image_src;
            img.alt = product.name || "Product Image";

            // Simple error handling - just use default if image fails
            img.onerror = function () {
                if (this.src !== "/images/default-product.png") {
                    this.src = "/images/default-product.png";
                }
            };
        }

        // Fill other product data with validation
        if (name) name.textContent = product.name || "Unknown Product";
        if (code) code.textContent = `Code: ${product.code || "N/A"}`;
        if (currentStock)
            currentStock.textContent = product.stock_quantity || 0;

        if (newStockInput) {
            const stockQty = parseInt(product.stock_quantity) || 0;
            newStockInput.value = stockQty;
            // IMPORTANT: Ensure original stock is properly set as string
            newStockInput.dataset.originalStock = stockQty.toString();

            console.log(`Set data for product ${product.id}:`, {
                value: newStockInput.value,
                originalStock: newStockInput.dataset.originalStock,
                productId: row.dataset.productId,
            });
        }

        contentContainer.appendChild(row);
    });

    console.log(
        "Finished rendering. Total rows in container:",
        contentContainer.querySelectorAll(".stock-update-row").length
    );

    // Verify all rows have proper data attributes
    const renderedRows = contentContainer.querySelectorAll(".stock-update-row");
    renderedRows.forEach((row, index) => {
        console.log(`Rendered row ${index} verification:`, {
            productId: row.dataset.productId,
            stockInput: row.querySelector(".new-stock-input"),
            stockValue: row.querySelector(".new-stock-input")?.value,
            originalStock:
                row.querySelector(".new-stock-input")?.dataset.originalStock,
        });
    });

    // Initialize row-specific handlers after rendering
    initializeStockRowHandlers();
}

// Initialize handlers for individual stock update rows
function initializeStockRowHandlers() {
    const rows = document.querySelectorAll(".stock-update-row");

    rows.forEach((row) => {
        const decreaseBtn = row.querySelector(".decrease-btn");
        const increaseBtn = row.querySelector(".increase-btn");
        const stockInput = row.querySelector(".new-stock-input");

        if (decreaseBtn) {
            decreaseBtn.addEventListener("click", () => {
                const currentValue = parseInt(stockInput.value) || 0;
                if (currentValue > 0) {
                    stockInput.value = currentValue - 1;
                    updateStockChangeDisplay(row);
                }
            });
        }

        if (increaseBtn) {
            increaseBtn.addEventListener("click", () => {
                const currentValue = parseInt(stockInput.value) || 0;
                stockInput.value = currentValue + 1;
                updateStockChangeDisplay(row);
            });
        }

        if (stockInput) {
            stockInput.addEventListener("input", () => {
                updateStockChangeDisplay(row);
            });
        }
    });
}

// Enhanced function to ensure stock change detection works properly
function updateStockChangeDisplay(row) {
    const stockInput = row.querySelector(".new-stock-input");
    const changeBadge = row.querySelector(".stock-change-badge");

    if (!stockInput || !changeBadge) return;

    const originalStock = parseInt(stockInput.dataset.originalStock) || 0;
    const newStock = parseInt(stockInput.value) || 0;
    const change = newStock - originalStock;

    console.log(
        `Stock change for row: Original=${originalStock}, New=${newStock}, Change=${change}`
    ); // Debug log

    if (change === 0) {
        changeBadge.textContent = "No change";
        changeBadge.className = "badge stock-change-badge bg-secondary-lt";
    } else if (change > 0) {
        changeBadge.textContent = `+${change}`;
        changeBadge.className = "badge stock-change-badge bg-success-lt";
    } else {
        changeBadge.textContent = change.toString();
        changeBadge.className = "badge stock-change-badge bg-danger-lt";
    }
}

// Initialize bulk update modal handlers
function initializeBulkUpdateHandlers() {
    const confirmBtn = document.getElementById("confirmBulkUpdateBtn");

    if (confirmBtn) {
        // Remove existing listeners by cloning
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener("click", handleBulkStockUpdate);
    }
}

// Handle bulk stock update confirmation - FIXED VERSION
function handleBulkStockUpdate() {
    const confirmBtn = document.getElementById("confirmBulkUpdateBtn");
    const rows = document.querySelectorAll(".stock-update-row");

    console.log("=== DEBUG: handleBulkStockUpdate ===");
    console.log("Found rows:", rows.length);

    // Collect update data with better validation
    const updates = [];

    rows.forEach((row, index) => {
        console.log(`Processing row ${index}:`, row);

        // Get product ID from data attribute
        const productIdStr = row.dataset.productId;
        const productId = parseInt(productIdStr);

        // Get stock input element
        const stockInput = row.querySelector(".new-stock-input");

        console.log(`Row ${index} - Product ID String:`, productIdStr);
        console.log(`Row ${index} - Product ID Parsed:`, productId);
        console.log(`Row ${index} - Stock Input:`, stockInput);
        console.log(`Row ${index} - Stock Input Value:`, stockInput?.value);

        // Enhanced validation
        if (!stockInput) {
            console.error(`Row ${index}: Stock input not found`);
            return;
        }

        if (!productIdStr || productIdStr.trim() === "") {
            console.error(`Row ${index}: Product ID is empty or missing`);
            return;
        }

        if (isNaN(productId) || productId <= 0) {
            console.error(
                `Row ${index}: Invalid product ID - ${productIdStr} -> ${productId}`
            );
            return;
        }

        // Validate stock input value
        const stockValueStr = stockInput.value;
        const newStock = parseInt(stockValueStr);

        if (stockValueStr === "" || isNaN(newStock) || newStock < 0) {
            console.error(
                `Row ${index}: Invalid stock value - ${stockValueStr} -> ${newStock}`
            );
            return;
        }

        // Get original stock with better validation
        const originalStockStr = stockInput.dataset.originalStock;
        const originalStock = parseInt(originalStockStr) || 0;

        console.log(
            `Product ${productId}: Original=${originalStock}, New=${newStock}`
        );

        // Create update object with all required fields
        const updateData = {
            id: productId,
            stock_quantity: newStock,
            original_stock: originalStock,
        };

        // Validate the update object before adding
        if (
            updateData.id &&
            typeof updateData.stock_quantity === "number" &&
            updateData.stock_quantity >= 0 &&
            typeof updateData.original_stock === "number" &&
            updateData.original_stock >= 0
        ) {
            updates.push(updateData);
            console.log(
                `Added valid update for product ${productId}:`,
                updateData
            );
        } else {
            console.error(
                `Invalid update data for product ${productId}:`,
                updateData
            );
        }
    });

    console.log("All updates to send:", updates);
    console.log("Updates count:", updates.length);

    // Validate we have updates
    if (updates.length === 0) {
        console.error("No valid updates found - debugging info:");
        console.log(
            "Rows found:",
            document.querySelectorAll(".stock-update-row")
        );
        console.log(
            "Stock inputs found:",
            document.querySelectorAll(".new-stock-input")
        );

        // Debug each row individually
        rows.forEach((row, index) => {
            console.log(`Row ${index} debug:`, {
                element: row,
                productId: row.dataset.productId,
                stockInput: row.querySelector(".new-stock-input"),
                stockValue: row.querySelector(".new-stock-input")?.value,
                originalStock:
                    row.querySelector(".new-stock-input")?.dataset
                        .originalStock,
            });
        });

        showToast(
            "Error",
            "No valid products found to update. Please check the data and try again.",
            "error"
        );
        return;
    }

    // Check if there are any actual changes
    const hasChanges = updates.some((update) => {
        const originalStock = parseInt(update.original_stock) || 0;
        const newStock = parseInt(update.stock_quantity) || 0;
        return originalStock !== newStock;
    });

    console.log("Has changes:", hasChanges);

    if (!hasChanges) {
        showToast(
            "Info",
            "No changes detected. Please adjust the stock quantities before updating.",
            "info"
        );
        return;
    }

    // Show loading state
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"></span>
        Updating Stock...
    `;
    confirmBtn.disabled = true;

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        showToast(
            "Error",
            "Security token not found. Please refresh the page.",
            "error"
        );
        resetButton(confirmBtn, originalText);
        return;
    }

    // Prepare request payload
    const requestPayload = {
        updates: updates,
    };

    console.log(
        "Final request payload:",
        JSON.stringify(requestPayload, null, 2)
    );

    // Submit updates
    fetch("/admin/product/bulk-update-stock", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken.getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify(requestPayload),
    })
        .then(async (response) => {
            const responseText = await response.text();
            console.log("Raw server response:", responseText);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error("Failed to parse response as JSON:", e);
                throw new Error("Invalid server response format");
            }

            console.log("Parsed server response:", data);

            if (!response.ok) {
                // Log validation errors if they exist
                if (data.errors) {
                    console.error("Validation errors:", data.errors);
                }
                throw new Error(
                    data.message || `HTTP error! status: ${response.status}`
                );
            }

            return data;
        })
        .then((data) => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(
                    document.getElementById("bulkUpdateStockModal")
                );
                if (modal) modal.hide();

                // Show success message
                showToast(
                    "Success",
                    `Stock updated for ${
                        data.updated_count || updates.length
                    } product(s)!`,
                    "success"
                );

                // Clear selection and reload
                clearProductSelection();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast(
                    "Error",
                    data.message || "Failed to update stock quantities.",
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            showToast(
                "Error",
                error.message ||
                    "An error occurred while updating stock quantities.",
                "error"
            );
        })
        .finally(() => {
            // Reset button
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        });
}

// Bulk action functions for the modal
window.setBulkAction = function (action, text) {
    document.getElementById("bulkActionText").textContent = text;
    document.getElementById("bulkActionText").dataset.action = action;
};

window.applyBulkStockAction = function () {
    const actionElement = document.getElementById("bulkActionText");
    const valueInput = document.getElementById("bulkStockValue");

    const action = actionElement.dataset.action || "add";
    const value = parseInt(valueInput.value) || 0;

    if (value === 0) {
        showToast("Warning", "Please enter a valid value.", "warning");
        return;
    }

    const rows = document.querySelectorAll(".stock-update-row");

    rows.forEach((row) => {
        const stockInput = row.querySelector(".new-stock-input");
        if (!stockInput) return;

        const currentValue = parseInt(stockInput.value) || 0;
        let newValue;

        switch (action) {
            case "add":
                newValue = currentValue + value;
                break;
            case "subtract":
                newValue = Math.max(0, currentValue - value);
                break;
            case "set":
                newValue = value;
                break;
            default:
                newValue = currentValue;
        }

        stockInput.value = newValue;
        updateStockChangeDisplay(row);
    });

    // Clear the input
    valueInput.value = "";

    showToast("Success", `Bulk action applied to all products.`, "success");
};
