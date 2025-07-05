document.addEventListener("DOMContentLoaded", function () {
    initModals();
    initExpiryCheckbox();
    initFlatpickr();
    initProductModal();
    initBulkSelection();
    initializeSearch();
    initExpiryDateToggle();
    initializeEntriesSelector();
});

// Store selected checkbox states globally
let selectedProductIds = new Set();

// MODAL INITIALIZATION
function initModals() {
    const modals = [
        { btnId: "viewLowStock", modalId: "lowStockModal" },
        { btnId: "viewExpiringSoon", modalId: "expiringSoonModal" },
    ];

    modals.forEach(({ btnId, modalId }) => {
        const btn = document.getElementById(btnId);
        if (btn) {
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                modal.show();
            });
        }
    });
}

// EXPIRY CHECKBOX TOGGLE
function initExpiryCheckbox() {
    const checkbox = document.getElementById("has_expiry");
    const container = document.getElementById("expiry_date_container");

    if (checkbox && container) {
        container.style.display = checkbox.checked ? "block" : "none";

        checkbox.addEventListener("change", function () {
            container.style.display = this.checked ? "block" : "none";
            if (this.checked) {
                const dateInput = container.querySelector(
                    "input[name='expiry_date']"
                );
                dateInput?._flatpickr?.redraw();
            }
        });
    }
}

// FLATPICKR INITIALIZATION
function initFlatpickr() {
    if (typeof flatpickr !== "function") {
        console.error("Flatpickr is not loaded");
        return;
    }

    const expiryInput = document.querySelector("input[name='expiry_date']");
    if (expiryInput) {
        flatpickr(expiryInput, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d-m-Y",
            allowInput: true,
            defaultDate: expiryInput.value || null,
        });
    }
}

// PRODUCT MODAL
function initProductModal() {
    const printBtn = document.getElementById("productModalPrint");
    if (printBtn) printBtn.addEventListener("click", handleProductModalPrint);
}

// BULK SELECTION WITH PERSISTENT STATE
function initBulkSelection() {
    let attempts = 0;
    const maxAttempts = 5;

    const tryInit = () => {
        attempts++;
        const elements = {
            selectAll: document.getElementById("selectAll"),
            rowCheckboxes: document.querySelectorAll(".row-checkbox"),
            bulkBar: document.getElementById("bulkActionsBar"),
            selectedCount: document.getElementById("selectedCount"),
        };

        if (
            !elements.selectAll ||
            !elements.bulkBar ||
            elements.rowCheckboxes.length === 0
        ) {
            if (attempts < maxAttempts) {
                setTimeout(tryInit, 300);
                return;
            }
            console.warn("Bulk selection elements not found");
            return;
        }

        setupBulkSelectionListeners(elements);
        updateBulkUI(elements);
        // Restore checkbox states after initialization
        restoreCheckboxStates();
    };

    tryInit();
}

function setupBulkSelectionListeners({
    selectAll,
    rowCheckboxes,
    bulkBar,
    selectedCount,
}) {
    selectAll.addEventListener("change", (e) => {
        rowCheckboxes.forEach((cb) => {
            cb.checked = e.target.checked;
            if (e.target.checked) {
                selectedProductIds.add(cb.value);
            } else {
                selectedProductIds.delete(cb.value);
            }
        });
        updateBulkUI({ selectAll, rowCheckboxes, bulkBar, selectedCount });
    });

    rowCheckboxes.forEach((cb) => {
        cb.addEventListener("change", () => {
            if (cb.checked) {
                selectedProductIds.add(cb.value);
            } else {
                selectedProductIds.delete(cb.value);
            }
            updateSelectAllState(selectAll, rowCheckboxes);
            updateBulkActionsBar(rowCheckboxes, bulkBar, selectedCount);
        });
    });
}

function restoreCheckboxStates() {
    document.querySelectorAll(".row-checkbox").forEach((checkbox) => {
        if (selectedProductIds.has(checkbox.value)) {
            checkbox.checked = true;
        }
    });
}

function updateSelectAllState(selectAll, rowCheckboxes) {
    const checked = document.querySelectorAll(".row-checkbox:checked").length;
    const total = rowCheckboxes.length;

    if (checked === 0) {
        selectAll.indeterminate = false;
        selectAll.checked = false;
    } else if (checked === total) {
        selectAll.indeterminate = false;
        selectAll.checked = true;
    } else {
        selectAll.indeterminate = true;
        selectAll.checked = false;
    }
}

function updateBulkActionsBar(rowCheckboxes, bulkBar, selectedCount) {
    const checked = document.querySelectorAll(".row-checkbox:checked").length;
    bulkBar.style.display = checked > 0 ? "block" : "none";
    selectedCount.textContent = checked;
}

function updateBulkUI(elements) {
    updateSelectAllState(elements.selectAll, elements.rowCheckboxes);
    updateBulkActionsBar(
        elements.rowCheckboxes,
        elements.bulkBar,
        elements.selectedCount
    );
}

// PRODUCT DETAILS LOADING
function loadProductDetails(id) {
    const content = document.getElementById("viewProductModalContent");
    const editBtn = document.getElementById("productModalEdit");

    if (!content || !editBtn) {
        console.error("Modal elements not found");
        return;
    }

    editBtn.href = `/admin/product/edit/${id}`;
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-3 text-muted">Loading...</p>
        </div>
    `;

    fetch(`/admin/product/modal-view/${id}`)
        .then((response) => {
            if (!response.ok) throw new Error("Network error");
            return response.json();
        })
        .then((data) => renderProductDetails(data))
        .catch((error) => {
            content.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
}

function renderProductDetails(data) {
    const content = document.getElementById("viewProductModalContent");
    const template = document.getElementById(
        "productModalViewTemplate"
    ).innerHTML;
    content.innerHTML = template;

    // Fill basic info
    setText("productName", data.name);
    setText("productCode", `Code: ${data.code}`);
    setText("productCategory", data.category?.name || "N/A");
    setText("productUnit", data.unit?.symbol || "N/A");
    setText("productQuantity", data.stock_quantity);
    setText("productSupplier", data.supplier?.name || "N/A");
    setText("productWarehouse", data.warehouse?.name || "N/A");

    // Stock status
    const threshold = data.low_stock_threshold || 10;
    const stockElement = document.getElementById("stockStatus");
    const isLowStock = data.stock_quantity <= threshold;
    setBadge(
        stockElement,
        isLowStock ? "Low Stock" : "In Stock",
        isLowStock ? "bg-danger-lt" : "bg-success-lt"
    );

    // Image
    document.getElementById("productImage").src =
        data.image || "/images/default-product.png";

    // Threshold
    const thresholdElement = document.getElementById("productThreshold");
    const thresholdNote = document.getElementById("thresholdDefaultNote");
    setText("productThreshold", data.low_stock_threshold || "10");
    if (thresholdNote) {
        thresholdNote.style.display = data.low_stock_threshold
            ? "none"
            : "inline";
    }

    // Expiry date
    if (data.has_expiry && data.expiry_date) {
        const date = new Date(data.expiry_date).toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "long",
            year: "numeric",
        });
        document.getElementById("productExpiry").innerHTML =
            date + getExpiryBadge(data.expiry_date);
    } else {
        setText("productExpiry", "N/A");
    }

    // Pricing
    setText("productPrice", data.formatted_price || data.price);
    setText(
        "productSellingPrice",
        data.formatted_selling_price || data.selling_price
    );

    const margin = (
        ((data.selling_price - data.price) / data.price) *
        100
    ).toFixed(2);
    setText("productMargin", margin + "%");

    // Description
    const descContainer = document.getElementById(
        "productDescriptionContainer"
    );
    if (data.description) {
        setText("productDescription", data.description);
    } else if (descContainer) {
        descContainer.style.display = "none";
    }
}

// PRINT FUNCTIONALITY
function handleProductModalPrint() {
    const content = document.getElementById(
        "viewProductModalContent"
    ).innerHTML;
    const original = document.body.innerHTML;

    document.body.innerHTML = `
        <div class="container print-container">
            <div class="card"><div class="card-body">${content}</div></div>
        </div>
    `;

    window.print();
    document.body.innerHTML = original;
    setTimeout(() => window.location.reload(), 100);
}

// BULK ACTIONS
window.clearProductSelection = function () {
    const selectAll = document.getElementById("selectAll");
    const checkboxes = document.querySelectorAll(".row-checkbox");
    const bulkBar = document.getElementById("bulkActionsBar");

    selectedProductIds.clear(); // Clear the global state

    if (selectAll) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }
    checkboxes.forEach((cb) => (cb.checked = false));
    if (bulkBar) bulkBar.style.display = "none";
};

window.getSelectedProductIds = function () {
    return Array.from(selectedProductIds);
};

window.bulkDeleteProducts = function () {
    const selected = getSelectedProductIds();
    if (!selected.length) {
        showToast("Warning", "Please select products to delete.", "warning");
        return;
    }

    document.getElementById("bulkDeleteCount").textContent = selected.length;
    const modal = new bootstrap.Modal(
        document.getElementById("bulkDeleteModal")
    );
    modal.show();

    const confirmBtn = document.getElementById("confirmBulkDeleteBtn");
    const newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener("click", () =>
        performBulkDelete(selected, newBtn, modal)
    );
};

window.bulkExportProducts = function () {
    const selected = getSelectedProductIds();
    if (!selected.length) {
        showToast("Warning", "Please select products to export.", "warning");
        return;
    }

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/product/bulk-export";
    form.style.display = "none";

    // CSRF token
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    // Selected IDs
    selected.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "ids[]";
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
};

window.bulkUpdateStock = function () {
    const selected = getSelectedProductIds();
    if (!selected.length) {
        showToast("Warning", "Please select products to update.", "warning");
        return;
    }

    const modal = new bootstrap.Modal(
        document.getElementById("bulkUpdateStockModal")
    );
    modal.show();
    loadBulkUpdateProductsFromTable(selected);
};

// BULK DELETE
function performBulkDelete(ids, button, modal) {
    const original = button.innerHTML;
    button.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
    button.disabled = true;

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf) {
        showToast("Error", "Security token not found.", "error");
        resetButton(button, original);
        return;
    }

    fetch("/admin/product/bulk-delete", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrf.getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify({ ids }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                modal.hide();
                // Listen for the 'hidden.bs.modal' event to ensure the modal is fully closed
                modal._element.addEventListener('hidden.bs.modal', function handler() {
                    modal._element.removeEventListener('hidden.bs.modal', handler); // Remove the listener
                    showToast(
                        "Success",
                        `${data.deleted_count || ids.length} products deleted successfully!`,
                        "success"
                    );
                    // Explicitly remove any remaining modal backdrops
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                });
                clearProductSelection();
                // Remove deleted rows from the table
                ids.forEach(id => {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) {
                        row.remove();
                    }
                });
                // setTimeout(() => location.reload(), 1500);
            } else {
                showToast("Error", data.message || "Delete failed.", "error");
            }
        })
        .catch((error) => {
            console.error("Delete error:", error);
            showToast(
                "Error",
                "An error occurred while deleting products.",
                "error"
            );
        })
        .finally(() => {
            resetButton(button, original);
        });
}

// BULK STOCK UPDATE
function loadBulkUpdateProductsFromTable(ids) {
    const content = document.getElementById("bulkUpdateStockContent");
    const countElement = document.getElementById("updateStockCount");

    const validIds = ids
        .map((id) => parseInt(id))
        .filter((id) => !isNaN(id) && id > 0);

    if (!validIds.length) {
        content.innerHTML =
            '<div class="alert alert-danger">No valid products selected.</div>';
        return;
    }

    countElement.textContent = validIds.length;

    // Use stored product data instead of DOM extraction
    const products = validIds
        .map((id) => {
            const idStr = id.toString();

            // First try to get from stored original data
            if (originalProductData.has(idStr)) {
                return originalProductData.get(idStr);
            }

            // Fallback to DOM extraction if not in stored data
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                return extractProductDataFromRow(row);
            }

            return null;
        })
        .filter(Boolean);

    if (!products.length) {
        content.innerHTML =
            '<div class="alert alert-danger">Could not find product data for selected items.</div>';
        return;
    }

    renderBulkUpdateProducts(products);
    initializeBulkUpdateHandlers();
}

function renderBulkUpdateProducts(products) {
    const content = document.getElementById("bulkUpdateStockContent");
    const template = document.getElementById("stockUpdateRowTemplate");

    if (!template) return;

    content.innerHTML = "";

    products.forEach((product) => {
        const row = template.cloneNode(true);
        row.style.display = "block";
        row.dataset.productId = product.id.toString();
        row.classList.add("stock-update-row");

        // Fill product data
        const elements = {
            img: row.querySelector(".product-image"),
            name: row.querySelector(".product-name"),
            code: row.querySelector(".product-code"),
            currentStock: row.querySelector(".current-stock"),
            newStockInput: row.querySelector(".new-stock-input"),
        };

        if (elements.img) {
            elements.img.src = product.image;
            elements.img.onerror = () =>
                (elements.img.src = "/images/default-product.png");
        }
        if (elements.name) elements.name.textContent = product.name;
        if (elements.code) elements.code.textContent = `Code: ${product.code}`;
        if (elements.currentStock)
            elements.currentStock.textContent = product.stock_quantity;
        if (elements.newStockInput) {
            elements.newStockInput.value = product.stock_quantity;
            elements.newStockInput.dataset.originalStock =
                product.stock_quantity.toString();
        }

        content.appendChild(row);
    });

    initializeStockRowHandlers();
}

function initializeStockRowHandlers() {
    document.querySelectorAll(".stock-update-row").forEach((row) => {
        const decrease = row.querySelector(".decrease-btn");
        const increase = row.querySelector(".increase-btn");
        const input = row.querySelector(".new-stock-input");

        if (decrease) {
            decrease.addEventListener("click", () => {
                const current = parseInt(input.value) || 0;
                if (current > 0) {
                    input.value = current - 1;
                    updateStockChangeDisplay(row);
                }
            });
        }

        if (increase) {
            increase.addEventListener("click", () => {
                const current = parseInt(input.value) || 0;
                input.value = current + 1;
                updateStockChangeDisplay(row);
            });
        }

        if (input) {
            input.addEventListener("input", () =>
                updateStockChangeDisplay(row)
            );
        }
    });
}

function updateStockChangeDisplay(row) {
    const input = row.querySelector(".new-stock-input");
    const badge = row.querySelector(".stock-change-badge");
    if (!input || !badge) return;

    const original = parseInt(input.dataset.originalStock) || 0;
    const current = parseInt(input.value) || 0;
    const change = current - original;

    if (change === 0) {
        badge.textContent = "No change";
        badge.className = "badge stock-change-badge bg-secondary-lt";
    } else if (change > 0) {
        badge.textContent = `+${change}`;
        badge.className = "badge stock-change-badge bg-success-lt";
    } else {
        badge.textContent = change.toString();
        badge.className = "badge stock-change-badge bg-danger-lt";
    }
}

function initializeBulkUpdateHandlers() {
    const confirmBtn = document.getElementById("confirmBulkUpdateBtn");
    if (confirmBtn) {
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
        newBtn.addEventListener("click", () => {
            handleBulkStockUpdate();
        });
    }
}

function handleBulkStockUpdate() {
    const rows = document.querySelectorAll(".stock-update-row");
    const updates = [];

    rows.forEach((row) => {
        const id = parseInt(row.dataset.productId);
        const input = row.querySelector(".new-stock-input");
        if (!input || isNaN(id)) return;

        const newStock = parseInt(input.value);
        const originalStock = parseInt(input.dataset.originalStock) || 0;

        if (!isNaN(newStock) && newStock >= 0) {
            updates.push({
                id,
                stock_quantity: newStock,
                original_stock: originalStock,
            });
        }
    });

    if (!updates.length) {
        console.log("No updates found, showing warning toast.");
        showToast("Error", "No valid updates found.", "error");
        return;
    }

    const hasChanges = updates.some(
        (u) => u.original_stock !== u.stock_quantity
    );
    if (!hasChanges) {
        console.log("No changes detected, showing info toast.");
        showToast("Info", "No changes detected.", "info");
        return;
    }

    const confirmBtn = document.getElementById("confirmBulkUpdateBtn");
    const original = confirmBtn.innerHTML;
    confirmBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    confirmBtn.disabled = true;

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf) {
        console.error("CSRF token not found.");
        showToast("Error", "Security token not found.", "error");
        resetButton(confirmBtn, original);
        return;
    }

    console.log("Sending bulk stock update request...");
    fetch("/admin/product/bulk-update-stock", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrf.getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify({ updates }),
    })
        .then((response) => {
            console.log("Received response from server.", response);
            if (!response.ok) {
                console.error("Server response not OK.", response.status, response.statusText);
                // Attempt to read response body for more details on error
                return response.json().then(errorData => {
                    console.error("Error response data:", errorData);
                    throw new Error(errorData.message || `Server error: ${response.status} ${response.statusText}`);
                }).catch(() => {
                    throw new Error(`Server error: ${response.status} ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then((data) => {
            console.log("Processing server data:", data);
            if (data.success) {
                console.log("Update successful, hiding modal and showing success toast.");
                // Introduce a slight delay before hiding the modal
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(
                        document.getElementById("bulkUpdateStockModal")
                    );
                    if (modal) {
                        modal.hide();
                        // Listen for the 'hidden.bs.modal' event to ensure the modal is fully closed
                        modal._element.addEventListener('hidden.bs.modal', function handler() {
                            modal._element.removeEventListener('hidden.bs.modal', handler); // Remove the listener
                            // Explicitly remove any remaining modal backdrops
                            const backdrops = document.querySelectorAll('.modal-backdrop');
                            backdrops.forEach(backdrop => backdrop.remove());
                        });
                    }
                    showToast(
                        "Success",
                        `Stock updated successfully for ${
                            data.updated_count || updates.length
                        } products!`,
                        "success"
                    );
                }, 300); // 300ms delay
                // Update the stock quantity in the table dynamically
                updates.forEach(updatedProduct => {
                    const row = document.querySelector(`tr[data-id="${updatedProduct.id}"]`);
                    if (row) {
                        const quantityElement = row.querySelector('.sort-quantity');
                        if (quantityElement) {
                            quantityElement.textContent = updatedProduct.stock_quantity;
                            // Also update the badge if it exists
                            const badge = quantityElement.querySelector('.badge');
                            if (badge) {
                                const threshold = updatedProduct.low_stock_threshold || 10; // Assuming threshold is returned or default
                                if (updatedProduct.stock_quantity <= threshold) {
                                    badge.className = 'badge bg-danger-lt';
                                    badge.textContent = 'Low Stock';
                                } else {
                                    badge.remove(); // Remove badge if no longer low stock
                                }
                            }
                        }
                    }
                });
                clearProductSelection();
                // setTimeout(() => location.reload(), 1500);
            } else {
                console.log("Update failed according to server data, showing error toast.");
                showToast("Error", data.message || "Update failed.", "error");
            }
        })
        .catch((error) => {
            console.error("Fetch or processing error:", error);
            showToast(
                "Error",
                `An error occurred while updating stock: ${error.message}`,
                "error"
            );
        })
        .finally(() => {
            console.log("Bulk stock update process finished.");
            resetButton(confirmBtn, original);
        });
}

// FIXED SEARCH FUNCTIONALITY - NO AUTO REFRESH
let searchTimeout;
let currentRequest = null;
let isSearchActive = false;
let originalTableContent = null;
let originalProductData = new Map();

if (typeof selectedProductIds === "undefined") {
    window.selectedProductIds = new Set();
}

function initializeSearch() {
    const searchInput = document.getElementById("searchInput");
    if (!searchInput) return;

    // Store original table content immediately
    storeOriginalTable();

    searchInput.addEventListener("input", function () {
        clearTimeout(searchTimeout);
        if (currentRequest) {
            currentRequest.abort();
            currentRequest = null;
        }

        const query = this.value.trim();

        // Clear search timeout
        searchTimeout = setTimeout(() => {
            if (query.length === 0) {
                // Restore original table when search is cleared
                if (isSearchActive) {
                    restoreOriginalTable();
                }
                isSearchActive = false;
            } else {
                performSearch(query);
                isSearchActive = true;
            }
        }, 500);
    });
}

function storeOriginalTable() {
    if (!originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            originalTableContent = tableBody.innerHTML;

            // Extract and store product data from existing rows
            const rows = tableBody.querySelectorAll("tr[data-id]");
            rows.forEach((row) => {
                const productId = row.dataset.id;
                const productData = extractProductDataFromRow(row);
                if (productData) {
                    originalProductData.set(productId, productData);
                }
            });
        }
    }
}

function extractProductDataFromRow(row) {
    try {
        const img = row.querySelector(".sort-image img");
        const nameElement = row.querySelector(".sort-name");
        const codeElement = row.querySelector(".sort-code");
        const quantityElement = row.querySelector(".sort-quantity");
        const categoryElement = row.querySelector(".sort-category");
        const unitElement = row.querySelector(".sort-unit");
        const priceElement = row.querySelector(".sort-price");
        const sellingPriceElement = row.querySelector(".sort-sellingprice");
        const supplierElement = row.querySelector(".sort-supplier");
        const expiryElement = row.querySelector(".sort-expiry");

        if (!nameElement) return null;

        // Extract stock quantity (remove any badges)
        const quantityText = quantityElement?.textContent?.trim() || "0";
        const stockMatch = quantityText.match(/^\d+/);
        const stock = stockMatch ? parseInt(stockMatch[0]) : 0;

        return {
            id: parseInt(row.dataset.id),
            name: nameElement.textContent.trim(),
            code: codeElement?.textContent?.trim() || "N/A",
            stock_quantity: stock,
            category: { name: categoryElement?.textContent?.trim() || "N/A" },
            unit: { symbol: unitElement?.textContent?.trim() || "N/A" },
            price: extractPriceFromText(priceElement?.textContent || "0"),
            selling_price: extractPriceFromText(
                sellingPriceElement?.textContent || "0"
            ),
            supplier: { name: supplierElement?.textContent?.trim() || "N/A" },
            expiry_date: extractExpiryFromText(
                expiryElement?.textContent || ""
            ),
            has_expiry: expiryElement?.textContent?.trim() !== "N/A",
            image: img?.src || "/images/default-product.png",
        };
    } catch (error) {
        console.error("Error extracting product data:", error);
        return null;
    }
}

// Helper function to extract price from formatted text
function extractPriceFromText(priceText) {
    if (!priceText || priceText === "N/A") return 0;
    // Remove currency symbols and formatting, extract numbers
    const matches = priceText.match(/[\d,]+/g);
    if (matches) {
        return parseInt(matches.join("").replace(/,/g, "")) || 0;
    }
    return 0;
}

// Helper function to extract expiry date
function extractExpiryFromText(expiryText) {
    if (!expiryText || expiryText.trim() === "N/A") return null;
    // Try to extract date from text (assuming format like "21/06/2025")
    const dateMatch = expiryText.match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
    if (dateMatch) {
        // Convert to YYYY-MM-DD format
        return `${dateMatch[3]}-${dateMatch[2].padStart(
            2,
            "0"
        )}-${dateMatch[1].padStart(2, "0")}`;
    }
    return null;
}

function restoreOriginalTable() {
    if (originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            tableBody.innerHTML = originalTableContent;
            // Reinitialize bulk selection and restore states
            setTimeout(() => {
                initBulkSelection();
                restoreCheckboxStates();
                updateBulkActionsBarVisibility();
            }, 100);
        }
    }
}

function restoreCheckboxStates() {
    document.querySelectorAll(".row-checkbox").forEach((checkbox) => {
        if (selectedProductIds.has(checkbox.value)) {
            checkbox.checked = true;
        }
    });

    // Update select all checkbox
    updateSelectAllCheckbox();
}

function updateSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById("selectAll");
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    const checkedCount = document.querySelectorAll(
        ".row-checkbox:checked"
    ).length;

    if (selectAllCheckbox) {
        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === rowCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }
}

function updateBulkActionsBarVisibility() {
    const bulkActionsBar = document.getElementById("bulkActionsBar");
    const selectedCount = document.getElementById("selectedCount");

    if (bulkActionsBar && selectedCount) {
        const count = selectedProductIds.size;
        selectedCount.textContent = count;

        if (count > 0) {
            bulkActionsBar.style.display = "block";
        } else {
            bulkActionsBar.style.display = "none";
        }
    }
}

function performSearch(query) {
    // Store original table on first search
    storeOriginalTable();

    const tableBody = document.querySelector("table tbody");

    if (!query) {
        restoreOriginalTable();
        return;
    }

    tableBody.innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-3 text-muted">Searching...</p>
        </td></tr>
    `;

    const controller = new AbortController();
    currentRequest = controller;

    fetch(`/admin/product/search?q=${encodeURIComponent(query)}`, {
        signal: controller.signal,
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            currentRequest = null;
            if (data.success) {
                renderSearchResults(data.products);
            } else {
                showNoResults(data.message);
            }
        })
        .catch((error) => {
            currentRequest = null;
            if (error.name !== "AbortError") {
                showSearchError(error.message);
            }
        });
}

function renderSearchResults(products) {
    const tableBody = document.querySelector("table tbody");
    if (!products.length) {
        showNoResults();
        return;
    }

    // Store search results in originalProductData for bulk operations
    products.forEach((product) => {
        originalProductData.set(product.id.toString(), product);
    });

    const formatCurrency = (amount) => {
        if (!amount) return "N/A";
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
        }).format(amount);
    };

    const html = products
        .map((product, index) => {
            const isLowStock =
                product.stock_quantity <= (product.low_stock_threshold || 10);
            const isSelected = selectedProductIds.has(product.id.toString());

            return `
            <tr data-id="${product.id}">
                <td><input type="checkbox" class="form-check-input row-checkbox" value="${
                    product.id
                }" ${isSelected ? "checked" : ""}></td>
                <td class="sort-no">${index + 1}</td>
                <td class="sort-image" style="width:120px">
                    <img src="${product.image || "/images/default-product.png"}"
                         width="80px" height="80px" alt="${product.name}"
                         onerror="this.src='/images/default-product.png'">
                </td>
                <td class="sort-code no-print">${product.code || "N/A"}</td>
                <td class="sort-name">${product.name}</td>
                <td class="sort-quantity no-print text-center">
                    ${product.stock_quantity}
                    ${
                        isLowStock
                            ? '<span class="badge bg-red-lt">Low Stock</span>'
                            : ""
                    }
                </td>
                <td class="sort-category no-print">${
                    product.category?.name || "N/A"
                }</td>
                <td class="sort-unit">${product.unit?.symbol || "N/A"}</td>
                <td class="sort-price text-center">${formatCurrency(
                    product.price
                )}</td>
                <td class="sort-sellingprice text-center">${formatCurrency(
                    product.selling_price
                )}</td>
                <td class="sort-supplier text-center">${
                    product.supplier?.name || "N/A"
                }</td>
                <td class="sort-expiry text-center">
                    ${
                        product.has_expiry && product.expiry_date
                            ? new Date(product.expiry_date).toLocaleDateString(
                                  "id-ID"
                              )
                            : '<span class="text-muted">N/A</span>'
                    }
                </td>
                <td class="no-print" style="text-align:center">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu">
                            <a href="javascript:void(0)" onclick="loadProductDetails('${
                                product.id
                            }')"
                               data-bs-toggle="modal" data-bs-target="#viewProductModal" class="dropdown-item">
                                <i class="ti ti-zoom-scan me-2"></i> View
                            </a>
                            <a href="/admin/product/edit/${
                                product.id
                            }" class="dropdown-item">
                                <i class="ti ti-edit me-2"></i> Edit
                            </a>
                            <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal"
                                    data-bs-target="#deleteModal" onclick="setDeleteFormAction('/admin/product/destroy/${
                                        product.id
                                    }')">
                                <i class="ti ti-trash me-2"></i> Delete
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
        `;
        })
        .join("");

    tableBody.innerHTML = html;

    // Reinitialize bulk selection with preserved states
    setTimeout(() => {
        initBulkSelection();
        updateSelectAllCheckbox();
        updateBulkActionsBarVisibility();
    }, 100);
}

function showNoResults(message = "No products found matching your search.") {
    document.querySelector("table tbody").innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <i class="ti ti-search-off fs-1 text-muted"></i>
            <p class="mt-3 text-muted">${message}</p>
        </td></tr>
    `;

    // Hide bulk actions bar when no results
    const bulkActionsBar = document.getElementById("bulkActionsBar");
    if (bulkActionsBar && selectedProductIds.size === 0) {
        bulkActionsBar.style.display = "none";
    }
}

function showSearchError(errorMessage = "Search error occurred.") {
    document.querySelector("table tbody").innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <i class="ti ti-alert-circle fs-1 text-danger"></i>
            <p class="mt-3 text-danger">${errorMessage}</p>
            <button class="btn btn-outline-primary mt-2" onclick="window.location.reload()">
                <i class="ti ti-refresh me-2"></i> Refresh
            </button>
        </td></tr>
    `;
}

// Enhanced bulk selection initialization
function initBulkSelection() {
    const selectAllCheckbox = document.getElementById("selectAll");
    const bulkActionsBar = document.getElementById("bulkActionsBar");

    if (!selectAllCheckbox) return;

    // Remove existing event listeners to avoid duplicates
    const newSelectAll = selectAllCheckbox.cloneNode(true);
    selectAllCheckbox.parentNode.replaceChild(newSelectAll, selectAllCheckbox);

    // Add select all functionality
    newSelectAll.addEventListener("change", handleSelectAllChange);

    // Add event listeners to row checkboxes using event delegation
    document.addEventListener("change", function (e) {
        if (e.target.classList.contains("row-checkbox")) {
            handleRowCheckboxChange(e.target);
        }
    });

    // Restore checkbox states
    restoreCheckboxStates();

    // Initialize bulk actions bar state
    updateBulkActionsBarVisibility();
}

function handleSelectAllChange(e) {
    const isChecked = e.target.checked;
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");

    rowCheckboxes.forEach((checkbox) => {
        checkbox.checked = isChecked;
        if (isChecked) {
            selectedProductIds.add(checkbox.value);
        } else {
            selectedProductIds.delete(checkbox.value);
        }
    });

    updateBulkActionsBarVisibility();
}

function handleRowCheckboxChange(checkbox) {
    if (checkbox.checked) {
        selectedProductIds.add(checkbox.value);
    } else {
        selectedProductIds.delete(checkbox.value);
    }

    updateSelectAllCheckbox();
    updateBulkActionsBarVisibility();
}

// Clear selection function
window.clearProductSelection = function () {
    selectedProductIds.clear();
    document.querySelectorAll(".row-checkbox").forEach((checkbox) => {
        checkbox.checked = false;
    });
    updateSelectAllCheckbox();
    updateBulkActionsBarVisibility();
};

window.setBulkAction = function (action, text) {
    const element = document.getElementById("bulkActionText");
    if (element) {
        element.textContent = text;
        element.dataset.action = action;
    }
};

window.applyBulkStockAction = function () {
    const actionElement = document.getElementById("bulkActionText");
    const valueInput = document.getElementById("bulkStockValue");
    const action = actionElement?.dataset.action || "add";
    const value = parseInt(valueInput?.value) || 0;

    if (!value) {
        showToast("Warning", "Please enter a valid value.", "warning");
        return;
    }

    document.querySelectorAll(".stock-update-row").forEach((row) => {
        const input = row.querySelector(".new-stock-input");
        if (!input) return;

        const current = parseInt(input.value) || 0;
        let newValue;

        switch (action) {
            case "add":
                newValue = current + value;
                break;
            case "subtract":
                newValue = Math.max(0, current - value);
                break;
            case "set":
                newValue = value;
                break;
            default:
                newValue = current;
        }

        input.value = newValue;
        if (typeof updateStockChangeDisplay === "function") {
            updateStockChangeDisplay(row);
        }
    });

    if (valueInput) valueInput.value = "";
    showToast("Success", "Bulk action applied to all products.", "success");
};

// UTILITY FUNCTIONS
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
    const diffDays = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return ' <span class="badge bg-danger-lt">Expired</span>';
    if (diffDays <= 7)
        return ` <span class="badge bg-warning">Expiring Soon - ${diffDays}d</span>`;
    return "";
}

function resetButton(button, originalText) {
    if (button) {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}



// DELETE MODAL FUNCTIONALITY
window.setDeleteFormAction = function (action) {
    const deleteForm = document.getElementById("deleteForm");
    if (deleteForm) {
        deleteForm.action = action;
    }
};

// PRODUCT STATS UPDATE
function updateProductStats(stats) {
    if (stats.total_products !== undefined) {
        const totalElement = document.getElementById("totalProducts");
        if (totalElement) totalElement.textContent = stats.total_products;
    }

    if (stats.low_stock_count !== undefined) {
        const lowStockElement = document.getElementById("lowStockCount");
        if (lowStockElement)
            lowStockElement.textContent = stats.low_stock_count;
    }

    if (stats.expiring_soon_count !== undefined) {
        const expiringSoonElement =
            document.getElementById("expiringSoonCount");
        if (expiringSoonElement)
            expiringSoonElement.textContent = stats.expiring_soon_count;
    }
}

// ENTRIES PER PAGE FUNCTIONALITY
function initializeEntriesSelector() {
    const entriesSelect = document.getElementById("entriesSelect");
    if (entriesSelect) {
        entriesSelect.addEventListener("change", function () {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set("entries", this.value);
            currentUrl.searchParams.delete("page"); // Reset to first page
            window.location.href = currentUrl.toString();
        });
    }
}

// EXPIRY DATE TOGGLE FUNCTIONALITY
function initExpiryDateToggle() {
    const expiryCheckbox = document.getElementById("has_expiry");
    const expiryDateField = document.querySelector(".expiry-date-field");
    const expiryDateInput = document.getElementById("expiry_date");

    if (expiryCheckbox && expiryDateField) {
        // Set initial state
        expiryDateField.style.display = expiryCheckbox.checked
            ? "block"
            : "none";

        // Add event listener for checkbox change
        expiryCheckbox.addEventListener("change", function () {
            if (this.checked) {
                expiryDateField.style.display = "block";
                // Focus on the date input when enabled
                if (expiryDateInput) {
                    setTimeout(() => expiryDateInput.focus(), 100);
                }
            } else {
                expiryDateField.style.display = "none";
                // Clear the date input when disabled
                if (expiryDateInput) {
                    expiryDateInput.value = "";
                }
            }
        });
    }
}

// EXPORT FUNCTIONALITY
window.exportProducts = function (format = "csv") {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = `/admin/product/export/${format}`;
    form.style.display = "none";

    // Add CSRF token
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    // Add current search query if exists
    const searchInput = document.getElementById("searchInput");
    if (searchInput && searchInput.value.trim()) {
        const searchQuery = document.createElement("input");
        searchQuery.type = "hidden";
        searchQuery.name = "search";
        searchQuery.value = searchInput.value.trim();
        form.appendChild(searchQuery);
    }

    document.body.appendChild(form);
    form.submit();

    setTimeout(() => {
        if (document.body.contains(form)) {
            document.body.removeChild(form);
        }
    }, 2000);
};

// KEYBOARD SHORTCUTS
document.addEventListener("keydown", function (e) {
    // Ctrl/Cmd + K for search focus
    if ((e.ctrlKey || e.metaKey) && e.key === "k") {
        e.preventDefault();
        const searchInput = document.getElementById("searchInput");
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }

    // Escape to clear search
    if (e.key === "Escape") {
        const searchInput = document.getElementById("searchInput");
        if (searchInput && document.activeElement === searchInput) {
            searchInput.value = "";
            const event = new Event("input", { bubbles: true });
            searchInput.dispatchEvent(event);
            searchInput.blur();
        }
    }
});

// BACKUP INITIALIZATION ON WINDOW LOAD
window.addEventListener("load", function () {
    console.log("Window loaded, ensuring all functions are initialized");

    // Double-check search initialization
    const searchInput = document.getElementById("searchInput");
    if (searchInput && !searchInput.hasAttribute("data-search-initialized")) {
        initializeSearch();
        searchInput.setAttribute("data-search-initialized", "true");
    }

    // Ensure bulk selection is working
    const selectAllCheckbox = document.getElementById("selectAll");
    if (
        selectAllCheckbox &&
        !selectAllCheckbox.hasAttribute("data-bulk-initialized")
    ) {
        initBulkSelection();
        selectAllCheckbox.setAttribute("data-bulk-initialized", "true");
    }

    // Initialize selectedProductIds if not already defined
    if (typeof selectedProductIds === "undefined") {
        window.selectedProductIds = new Set();
    }
});
