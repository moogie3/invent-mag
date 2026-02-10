import { getSelectedProductIds, clearProductSelection } from "./selection.js";
import { resetButton, getStockClassAndText } from "../utils/ui.js";
import { fetchProductMetrics } from "../stats.js";
import { originalProductData } from "../search/state.js";
import { extractProductDataFromRow } from "../utils/helpers.js";

export function bulkUpdateStock() {
    const selected = getSelectedProductIds();
    if (!selected.length) {
        InventMagApp.showToast("Warning", "Please select products to update.", "warning");
        return;
    }

    const modalElement = document.getElementById("bulkUpdateStockModal");

    if (modalElement) {
        if (
            typeof bootstrap !== "undefined" &&
            typeof bootstrap.Modal !== "undefined"
        ) {
            // Pre-select warehouse from global filter
            const globalWarehouseSelect = document.querySelector('select[name="warehouse_id"]');
            const globalWarehouseId = globalWarehouseSelect ? globalWarehouseSelect.value : '';
            const bulkUpdateWarehouseSelect = document.getElementById('bulkUpdateWarehouse');
            
            if (bulkUpdateWarehouseSelect) {
                if (globalWarehouseId) {
                    bulkUpdateWarehouseSelect.value = globalWarehouseId;
                    bulkUpdateWarehouseSelect.disabled = true;
                } else {
                    bulkUpdateWarehouseSelect.disabled = false;
                    if (bulkUpdateWarehouseSelect.options.length > 0 && !bulkUpdateWarehouseSelect.value) {
                        bulkUpdateWarehouseSelect.selectedIndex = 0;
                    }
                }
            }

            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            loadBulkUpdateProductsFromTable(selected);
            initializeBulkUpdateHandlers();

            // Initial refresh of stock values for the selected warehouse
            if (bulkUpdateWarehouseSelect) {
                // Determine which warehouse ID to use (global filter or current selection)
                const targetWarehouseId = globalWarehouseId || bulkUpdateWarehouseSelect.value;
                if (targetWarehouseId) {
                    // Short delay to ensure template rows are rendered and then refresh
                    setTimeout(() => {
                        refreshBulkUpdateStockForWarehouse(targetWarehouseId);
                    }, 150);
                }
            }
        } else {
            InventMagApp.showToast(
                "Error",
                "Bootstrap Modal functionality not available.",
                "error"
            );
        }
    } else {
        InventMagApp.showToast("Error", "Bulk update modal not found.", "error");
    }
}

function loadBulkUpdateProductsFromTable(selectedIds) {
    const container = document.getElementById("bulkUpdateStockContent");
    const template = document.getElementById("stockUpdateRowTemplate");
    const countElement = document.getElementById("updateStockCount");

    if (!container || !template) return;

    container.innerHTML = "";
    countElement.textContent = selectedIds.length;

    selectedIds.forEach((id) => {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (!row) return;

        const data = extractProductDataFromRow(row);
        const clone = template.cloneNode(true);
        clone.style.display = "block";
        clone.id = "";
        clone.dataset.productId = id;

        clone.querySelector(".product-name").textContent = data.name;
        clone.querySelector(".product-code").textContent = data.code;
        clone.querySelector(".current-stock").textContent = data.stock_quantity;
        
        const img = clone.querySelector(".product-image");
        const iconPlaceholder = clone.querySelector(".product-icon-placeholder");
        
        if (data.is_placeholder) {
            if (iconPlaceholder) iconPlaceholder.classList.remove("d-none");
            if (img) img.classList.add("d-none");
        } else {
            if (img) {
                img.src = data.image;
                img.classList.remove("d-none");
                img.onerror = () => {
                    img.classList.add("d-none");
                    if (iconPlaceholder) iconPlaceholder.classList.remove("d-none");
                };
            }
            if (iconPlaceholder) iconPlaceholder.classList.add("d-none");
        }

        const input = clone.querySelector(".new-stock-input");
        input.value = data.stock_quantity;
        input.dataset.originalStock = data.stock_quantity;

        // Add event listeners for this row
        const decreaseBtn = clone.querySelector(".decrease-btn");
        const increaseBtn = clone.querySelector(".increase-btn");

        decreaseBtn.addEventListener("click", () => {
            input.value = Math.max(0, parseInt(input.value) - 1);
            updateStockChangeDisplay(clone);
        });

        increaseBtn.addEventListener("click", () => {
            input.value = parseInt(input.value) + 1;
            updateStockChangeDisplay(clone);
        });

        input.addEventListener("input", () => {
            if (parseInt(input.value) < 0) input.value = 0;
            updateStockChangeDisplay(clone);
        });

        container.appendChild(clone);
    });
}

function updateStockChangeDisplay(row) {
    const input = row.querySelector(".new-stock-input");
    const badge = row.querySelector(".stock-change-badge");
    if (!input || !badge) return;

    const current = parseInt(input.dataset.originalStock) || 0;
    const next = parseInt(input.value) || 0;
    const diff = next - current;

    if (diff === 0) {
        badge.textContent = "No change";
        badge.className = "badge stock-change-badge bg-secondary-lt";
    } else if (diff > 0) {
        badge.textContent = `+${diff}`;
        badge.className = "badge stock-change-badge bg-success-lt";
    } else {
        badge.textContent = diff;
        badge.className = "badge stock-change-badge bg-danger-lt";
    }
}

function refreshBulkUpdateStockForWarehouse(warehouseId) {
    const rows = document.querySelectorAll("#bulkUpdateStockContent .stock-update-row");
    rows.forEach(row => {
        const productId = row.dataset.productId;
        const currentStockDisplay = row.querySelector(".current-stock");
        const newStockInput = row.querySelector(".new-stock-input");
        const changeBadge = row.querySelector(".stock-change-badge");
        
        if (!currentStockDisplay || !newStockInput) return;

        fetch(`/admin/product/modal-view/${productId}`)
            .then(response => response.json())
            .then(product => {
                let quantity = 0;
                if (warehouseId) {
                    const warehouse = product.warehouses.find(w => w.id == warehouseId);
                    quantity = warehouse ? (warehouse.pivot ? warehouse.pivot.quantity : 0) : 0;
                } else {
                    quantity = product.total_stock || product.stock_quantity || 0;
                }
                currentStockDisplay.textContent = quantity;
                newStockInput.dataset.originalStock = quantity.toString();
                // Reset input to current stock to reflect the warehouse context
                newStockInput.value = quantity;
                if (changeBadge) {
                    changeBadge.textContent = "No change";
                    changeBadge.className = "badge stock-change-badge bg-secondary-lt";
                }
            })
            .catch(() => {
                currentStockDisplay.textContent = "0";
                newStockInput.dataset.originalStock = "0";
                newStockInput.value = 0;
                if (changeBadge) {
                    changeBadge.textContent = "No change";
                    changeBadge.className = "badge stock-change-badge bg-secondary-lt";
                }
            });
    });
}

function initializeBulkUpdateHandlers() {
    const confirmBtn = document.getElementById("confirmBulkUpdateBtn");
    if (confirmBtn) {
        // Use a more robust way to handle the click and avoid multiple bindings
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
        newBtn.addEventListener("click", () => {
            handleBulkStockUpdate();
        });
    }

    const bulkUpdateWarehouseSelect = document.getElementById('bulkUpdateWarehouse');
    if (bulkUpdateWarehouseSelect) {
        // Use onchange to ensure only one listener is active
        bulkUpdateWarehouseSelect.onchange = (e) => {
            refreshBulkUpdateStockForWarehouse(e.target.value);
        };
    }

    // Default currentBulkAction
    window.currentBulkAction = 'add';

    // Expose bulk action helper functions to window
    window.setBulkAction = function(action, text) {
        const btn = document.getElementById('bulkActionText');
        if (btn) btn.textContent = text;
        window.currentBulkAction = action;
    };

    window.applyBulkStockAction = function() {
        const bulkStockValueInput = document.getElementById('bulkStockValue');
        const val = parseInt(bulkStockValueInput ? bulkStockValueInput.value : 0);
        
        if (isNaN(val)) {
            InventMagApp.showToast("Warning", "Please enter a valid value.", "warning");
            return;
        }

        const action = window.currentBulkAction || 'add';
        const rows = document.querySelectorAll("#bulkUpdateStockContent .stock-update-row");
        
        rows.forEach(row => {
            const input = row.querySelector(".new-stock-input");
            const original = parseInt(input.dataset.originalStock) || 0;
            
            if (action === 'add') {
                input.value = original + val;
            } else if (action === 'subtract') {
                input.value = Math.max(0, original - val);
            } else if (action === 'set') {
                input.value = val;
            }
            updateStockChangeDisplay(row);
        });
    };
}

function handleBulkStockUpdate() {
    const rows = document.querySelectorAll("#bulkUpdateStockContent .stock-update-row");
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
        InventMagApp.showToast("Error", "No valid updates found.", "error");
        return;
    }

    const hasChanges = updates.some(
        (u) => u.original_stock !== u.stock_quantity
    );
    if (!hasChanges) {
        InventMagApp.showToast("Info", "No changes detected.", "info");
        return;
    }

    const bulkAdjustmentReasonInput = document.getElementById("bulkAdjustmentReason");
    const bulkAdjustmentReason = bulkAdjustmentReasonInput ? bulkAdjustmentReasonInput.value : "";

    const bulkUpdateWarehouseSelect = document.getElementById('bulkUpdateWarehouse');
    const warehouseId = bulkUpdateWarehouseSelect ? bulkUpdateWarehouseSelect.value : null;

    const confirmBtn = document.getElementById("confirmBulkUpdateBtn");
    const original = confirmBtn.innerHTML;
    confirmBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    confirmBtn.disabled = true;

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf) {
        InventMagApp.showToast("Error", "Security token not found.", "error");
        resetButton(confirmBtn, original);
        return;
    }

    // Sanitize updates to only include necessary fields for the server
    const payloadUpdates = updates.map(({ id, stock_quantity }) => ({
        id,
        stock_quantity
    }));

    fetch("/admin/product/bulk-update-stock", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrf.getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify({
            updates: payloadUpdates,
            reason: bulkAdjustmentReason || null,
            warehouse_id: warehouseId
        }),
    })
        .then((response) => {
            if (!response.ok) {
                return response.json().then((errorData) => {
                    throw new Error(errorData.message || `Server error: ${response.status}`);
                }).catch(() => {
                    throw new Error(`Server error: ${response.status}`);
                });
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById("bulkUpdateStockModal"));
                if (modal) {
                    modal.hide();
                    
                    // Show toast
                    InventMagApp.showToast(
                        "Success",
                        `Stock updated successfully for ${data.updated_count || updates.length} products!`,
                        "success"
                    );

                    // Update the main table rows dynamically
                    if (data.changes && Array.isArray(data.changes)) {
                        data.changes.forEach((change) => {
                            const row = document.querySelector(`tr[data-id="${change.product_id}"]`);
                            if (row) {
                                const stockQuantityElement = row.querySelector(".sort-quantity .fw-bold");
                                if (stockQuantityElement) {
                                    // Determine which stock quantity to show based on global filter
                                    const globalWarehouseSelect = document.querySelector('select[name="warehouse_id"]');
                                    const globalWarehouseId = globalWarehouseSelect ? globalWarehouseSelect.value : '';
                                    
                                    let displayStock = change.new_stock_quantity; // Default to total stock
                                    if (globalWarehouseId && data.warehouse_id == globalWarehouseId) {
                                        displayStock = change.new_warehouse_stock;
                                    }
                                    
                                    stockQuantityElement.textContent = displayStock;

                                    const badgeElement = row.querySelector(".sort-quantity .badge");
                                    if (badgeElement) {
                                        // Recalculate badge for the displayed stock context
                                        const [badgeClass, badgeText] = getStockClassAndText(displayStock, change.low_stock_threshold);
                                        badgeElement.className = `badge ${badgeClass}`;
                                        badgeElement.textContent = badgeText;
                                    }
                                }
                            }
                        });
                    }
                    clearProductSelection();
                }
            } else {
                InventMagApp.showToast("Error", data.message || "Update failed.", "error");
            }
        })
        .catch((error) => {
            InventMagApp.showToast("Error", `An error occurred: ${error.message}`, "error");
        })
        .finally(() => {
            resetButton(confirmBtn, original);
        });
}
