import { getSelectedProductIds, clearProductSelection } from './selection.js';
import { resetButton } from '../utils/ui.js';
import { fetchProductMetrics } from '../stats.js';
import { originalProductData } from '../search/state.js';
import { extractProductDataFromRow } from '../utils/helpers.js';

export function bulkUpdateStock() {
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
}

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

    const products = validIds
        .map((id) => {
            const idStr = id.toString();

            if (originalProductData.has(idStr)) {
                return originalProductData.get(idStr);
            }

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

        const elements = {
            img: row.querySelector(".product-image"),
            name: row.querySelector(".product-name"),
            code: row.querySelector(".product-code"),
            currentStock: row.querySelector(".current-stock"),
            newStockInput: row.querySelector(".new-stock-input"),
        };

        if (elements.img) {
            if (product.image && product.image.trim() !== '' && product.image.toLowerCase() !== 'null' && product.image.toLowerCase() !== 'undefined') {
                elements.img.src = product.image;
                elements.img.onerror = () => {
                    elements.img.outerHTML = `<div class="d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 1px solid #ccc; border-radius: 5px;"><i class="ti ti-photo fs-1 text-muted"></i></div>`;
                };
            } else {
                elements.img.outerHTML = `<div class="d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border: 1px solid #ccc; border-radius: 5px;"><i class="ti ti-photo fs-1 text-muted"></i></div>`;
            }
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
        showToast("Error", "No valid updates found.", "error");
        return;
    }

    const hasChanges = updates.some(
        (u) => u.original_stock !== u.stock_quantity
    );
    if (!hasChanges) {
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
            if (!response.ok) {
                return response
                    .json()
                    .then((errorData) => {
                        throw new Error(
                            errorData.message ||
                                `Server error: ${response.status} ${response.statusText}`
                        );
                    })
                    .catch(() => {
                        throw new Error(
                            `Server error: ${response.status} ${response.statusText}`
                        );
                    });
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(
                        document.getElementById("bulkUpdateStockModal")
                    );
                    if (modal) {
                        modal.hide();
                        modal._element.addEventListener(
                            "hidden.bs.modal",
                            function handler() {
                                modal._element.removeEventListener(
                                    "hidden.bs.modal",
                                    handler
                                );
                                const backdrops =
                                    document.querySelectorAll(
                                        ".modal-backdrop"
                                    );
                                backdrops.forEach((backdrop) =>
                                    backdrop.remove()
                                );
                            }
                        );
                    }
                    showToast(
                        "Success",
                        `Stock updated successfully for ${
                            data.updated_count || updates.length
                        } products!`,
                        "success"
                    );
                }, 300);
                updates.forEach((updatedProduct) => {
                    const row = document.querySelector(
                        `tr[data-id="${updatedProduct.id}"]`
                    );
                    if (row) {
                        const quantityElement =
                            row.querySelector(".sort-quantity");
                        if (quantityElement) {
                            quantityElement.textContent =
                                updatedProduct.stock_quantity;
                            const badge =
                                quantityElement.querySelector(".badge");
                            if (badge) {
                                const threshold =
                                    updatedProduct.low_stock_threshold || 10;
                                if (
                                    updatedProduct.stock_quantity <= threshold
                                ) {
                                    badge.className = "badge bg-danger-lt";
                                    badge.textContent = "Low Stock";
                                } else {
                                    badge.remove();
                                }
                            }
                        }
                    }
                });
                clearProductSelection();
                fetchProductMetrics();
            } else {
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
            resetButton(confirmBtn, original);
        });
}

window.bulkUpdateStock = bulkUpdateStock;
