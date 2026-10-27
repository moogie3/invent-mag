import { initModals } from './modals/init.js';
import { initProductModal, loadExpiringSoonProductsModal } from './modals/product.js'; // Import loadExpiringSoonProductsModal
import { initBulkSelection, clearProductSelection, restoreCheckboxStates, updateBulkActionsBarVisibility } from './bulkActions/selection.js';


import { initKeyboardShortcuts, initExport } from './events.js';
import { escapeHtml } from '../../../utils/sanitize.js';
import { bulkUpdateStock } from './bulkActions/stock.js'; // Import bulkUpdateStock
import { bulkDeleteProducts } from './bulkActions/delete.js'; // Import bulkDeleteProducts
import { getStockClassAndText } from './utils/ui.js';

export function initProductPage() {
        initModals();
        initProductModal();
        initBulkSelection();
        initKeyboardShortcuts();
        initExport();

        // Product's table swap is driven by the shared module in
        // partials/sorting/serverSearch.js (sorting.js). It re-initializes
        // List.js itself and dispatches `listsearch:updated` on the table
        // container, which is what this page listens for to rebuild its bulk
        // selection. Attaching to the container (not `document`) keeps the
        // hook scoped to this page and out of the way of every other list.
        const bulkRebindTarget = document.getElementById("invoiceTableContainer");
        if (bulkRebindTarget && !bulkRebindTarget.dataset.bulkRebindAttached) {
            bulkRebindTarget.dataset.bulkRebindAttached = "true";
            bulkRebindTarget.addEventListener("listsearch:updated", () => {
                initBulkSelection();
                restoreCheckboxStates();
                updateBulkActionsBarVisibility();
            });
        }

        const expiringSoonModalElement = document.getElementById('expiringSoonModal');
        if (expiringSoonModalElement && !expiringSoonModalElement.dataset.handlerAttached) {
            expiringSoonModalElement.dataset.handlerAttached = 'true';
            expiringSoonModalElement.addEventListener('show.bs.modal', function () {
                const tableBody = document.getElementById('expiringSoonProductsTableBody');
                if (tableBody) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Loading expiring products...
                            </td>
                        </tr>
                    `;
                }

                fetch('/admin/product/expiring-soon')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        loadExpiringSoonProductsModal(data);
                    })
                    .catch(error => {
                        if (tableBody) {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="6" class="text-center text-danger py-4">
                                        Error loading products. Please try again.
                                    </td>
                                </tr>
                            `;
                        }
                    });
            });
        }

        // Low-stock modal: fetch its rows on open instead of server-rendering
        // the whole list into every product page (was ~1.1MB / seconds per load).
        const lowStockModalElement = document.getElementById('lowStockModal');
        if (lowStockModalElement && !lowStockModalElement.dataset.handlerAttached) {
            lowStockModalElement.dataset.handlerAttached = 'true';
            lowStockModalElement.addEventListener('show.bs.modal', function () {
                const tbody = document.getElementById('lowStockModalBody');
                if (!tbody) return;

                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted small mb-0">Loading...</p>
                        </td>
                    </tr>`;

                fetch('/admin/product/low-stock')
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (!Array.isArray(data) || data.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">-</td>
                                </tr>`;
                            return;
                        }

                        tbody.innerHTML = data
                            .map((item) => {
                                return `
                                    <tr>
                                        <td>${escapeHtml(item.name ?? '')}</td>
                                        <td class="text-center">
                                            ${
                                                item.warehouse
                                                    ? `<span class="badge bg-primary-lt text-primary">${escapeHtml(item.warehouse)}</span>`
                                                    : '<span class="text-muted">N/A</span>'
                                            }
                                        </td>
                                        <td class="text-center">${item.quantity}</td>
                                        <td class="text-center">${item.threshold ?? 10}</td>
                                        <td class="text-end">
                                            <a href="/admin/product/edit/${item.id}"
                                               class="btn btn-icon btn-ghost-primary" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        </td>
                                    </tr>`;
                            })
                            .join('');
                    })
                    .catch(() => {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center text-danger py-4">
                                    Error loading low stock products. Please try again.
                                </td>
                            </tr>`;
                    });
            });
        }

        const selectAllCheckbox = document.getElementById("selectAll");
        if (
            selectAllCheckbox &&
            !selectAllCheckbox.hasAttribute("data-bulk-initialized")
        ) {
            initBulkSelection();
            selectAllCheckbox.setAttribute("data-bulk-initialized", "true");
        }

        if (typeof selectedProductIds === "undefined") {
            window.selectedProductIds = new Set();
        }

        initAdjustStockModal();

         window.bulkDeleteProducts = bulkDeleteProducts; // Expose globally
         window.clearProductSelection = clearProductSelection; // Expose globally
}

function initAdjustStockModal() {
        function updateCurrentStockForWarehouse(productId, warehouseId) {
            const adjustCurrentStockInput = document.getElementById('adjustCurrentStock');
            if (!adjustCurrentStockInput) return;
            
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
                    adjustCurrentStockInput.value = quantity;
                    if (typeof updateAdjustmentPreview === 'function') {
                        updateAdjustmentPreview();
                    }
                })
                .catch(error => {
                    adjustCurrentStockInput.value = 0;
                    if (typeof updateAdjustmentPreview === 'function') {
                        updateAdjustmentPreview();
                    }
                });
        }

        window.openAdjustStockModal = function(productId, productName, currentStock) {
            const adjustProductId = document.getElementById('adjustProductId');
            const adjustProductName = document.getElementById('adjustProductName');
            const adjustCurrentStock = document.getElementById('adjustCurrentStock');
            const adjustmentType = document.getElementById('adjustmentType');
            const adjustmentAmount = document.getElementById('adjustmentAmount');
            const adjustmentReason = document.getElementById('adjustmentReason');
            const adjustmentAmountLabel = document.getElementById('adjustmentAmountLabel');

            if (adjustProductId) adjustProductId.value = productId;
            if (adjustProductName) adjustProductName.textContent = productName;
            if (adjustCurrentStock) adjustCurrentStock.value = currentStock;
            if (adjustmentType) adjustmentType.value = 'increase';
            if (adjustmentAmount) adjustmentAmount.value = 1;
            if (adjustmentReason) adjustmentReason.value = '';
            if (adjustmentAmountLabel) adjustmentAmountLabel.textContent = 'Adjustment Amount';
            
            const globalWarehouseSelect = document.querySelector('select[name="warehouse_id"]');
            const globalWarehouseId = globalWarehouseSelect ? globalWarehouseSelect.value : '';
            
            const adjustmentWarehouseSelect = document.getElementById('adjustmentWarehouse');
            
            if (adjustmentWarehouseSelect) {
                if (globalWarehouseId) {
                    adjustmentWarehouseSelect.value = globalWarehouseId;
                    adjustmentWarehouseSelect.disabled = true;
                } else {
                    adjustmentWarehouseSelect.disabled = false;
                    if (adjustmentWarehouseSelect.options.length > 0 && !adjustmentWarehouseSelect.value) {
                        adjustmentWarehouseSelect.selectedIndex = 0;
                    }
                }
                
                if (!adjustmentWarehouseSelect.dataset.handlerAttached) {
                    adjustmentWarehouseSelect.dataset.handlerAttached = 'true';
                    adjustmentWarehouseSelect.addEventListener('change', function() {
                        const pid = document.getElementById('adjustProductId').value;
                        updateCurrentStockForWarehouse(pid, this.value);
                    });
                }

                updateCurrentStockForWarehouse(productId, adjustmentWarehouseSelect.value);
            }
            
            updateAdjustmentPreview();

            const adjustStockModalEl = document.getElementById('adjustStockModal');
            if (adjustStockModalEl) {
                new bootstrap.Modal(adjustStockModalEl).show();
            }
        };

        function updateAdjustmentPreview() {
            const adjustCurrentStockInput = document.getElementById('adjustCurrentStock');
            const adjustmentTypeSelect = document.getElementById('adjustmentType');
            const adjustmentAmountInput = document.getElementById('adjustmentAmount');
            const correctionAmountInput = document.getElementById('correctionAmount');
            const adjustmentPreviewBadge = document.getElementById('adjustmentPreviewBadge');
            
            if (!adjustCurrentStockInput || !adjustmentTypeSelect || !adjustmentPreviewBadge) return;
            
            const currentStock = parseFloat(adjustCurrentStockInput.value) || 0;
            const adjustmentType = adjustmentTypeSelect.value;
            let adjustmentAmount = 0;
            
            if (adjustmentType === 'correction') {
                adjustmentAmount = parseFloat(correctionAmountInput?.value) || 0;
            } else {
                adjustmentAmount = parseFloat(adjustmentAmountInput?.value) || 0;
            }

            let newStock = currentStock;
            let change = 0;

            if (adjustmentType === 'increase') {
                newStock = currentStock + adjustmentAmount;
                change = adjustmentAmount;
            } else if (adjustmentType === 'decrease') {
                newStock = Math.max(0, currentStock - adjustmentAmount);
                change = -adjustmentAmount;
            } else if (adjustmentType === 'correction') {
                newStock = adjustmentAmount;
                change = newStock - currentStock;
            }

            adjustmentPreviewBadge.textContent = `New Stock: ${newStock} (${change >= 0 ? '+' : ''}${change})`;
            adjustmentPreviewBadge.classList.remove('bg-secondary-lt', 'bg-success-lt', 'bg-danger-lt');
            if (change === 0) {
                adjustmentPreviewBadge.classList.add('bg-secondary-lt');
            } else if (change > 0) {
                adjustmentPreviewBadge.classList.add('bg-success-lt');
            } else {
                adjustmentPreviewBadge.classList.add('bg-danger-lt');
            }
        }

        const adjustmentTypeSelect = document.getElementById('adjustmentType');
        const adjustmentAmountInput = document.getElementById('adjustmentAmount');
        const correctionAmountInput = document.getElementById('correctionAmount');

        if (adjustmentTypeSelect && !adjustmentTypeSelect.dataset.handlerAttached) {
            adjustmentTypeSelect.dataset.handlerAttached = 'true';
            adjustmentTypeSelect.addEventListener('change', function() {
                const adjustmentAmountContainer = document.getElementById('adjustmentAmountContainer');
                const correctionAmountContainer = document.getElementById('correctionAmountContainer');
                const type = this.value;
                if (type === 'correction') {
                    if (adjustmentAmountContainer) adjustmentAmountContainer.style.display = 'none';
                    if (correctionAmountContainer) correctionAmountContainer.style.display = 'block';
                } else {
                    if (adjustmentAmountContainer) adjustmentAmountContainer.style.display = 'block';
                    if (correctionAmountContainer) correctionAmountContainer.style.display = 'none';
                }
                updateAdjustmentPreview();
            });
        }

        if (adjustmentAmountInput && !adjustmentAmountInput.dataset.handlerAttached) {
            adjustmentAmountInput.dataset.handlerAttached = 'true';
            adjustmentAmountInput.addEventListener('input', updateAdjustmentPreview);
        }
        if (correctionAmountInput && !correctionAmountInput.dataset.handlerAttached) {
            correctionAmountInput.dataset.handlerAttached = 'true';
            correctionAmountInput.addEventListener('input', updateAdjustmentPreview);
        }

        const confirmAdjustStockBtn = document.getElementById('confirmAdjustStockBtn');
        
        if (confirmAdjustStockBtn && !confirmAdjustStockBtn.dataset.handlerAttached) {
            confirmAdjustStockBtn.dataset.handlerAttached = 'true';
            confirmAdjustStockBtn.addEventListener('click', function() {
                const productId = document.getElementById('adjustProductId').value;
                const adjustmentType = document.getElementById('adjustmentType').value;
                const reason = document.getElementById('adjustmentReason').value;
                
                const adjustmentWarehouseSelect = document.getElementById('adjustmentWarehouse');
                const warehouseId = adjustmentWarehouseSelect ? adjustmentWarehouseSelect.value : null;

                let adjustmentAmount = 0;

                if (adjustmentType === 'correction') {
                    adjustmentAmount = parseFloat(document.getElementById('correctionAmount').value) || 0;
                } else {
                    adjustmentAmount = parseFloat(document.getElementById('adjustmentAmount').value) || 0;
                }

                if (!adjustmentAmount || adjustmentAmount < 0) {
                    InventMagApp.showToast("Warning", 'Please enter a valid adjustment amount.', "warning");
                    return;
                }

                const originalText = confirmAdjustStockBtn.innerHTML;
                confirmAdjustStockBtn.disabled = true;
                confirmAdjustStockBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';

                fetch('/admin/product/adjust-stock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        warehouse_id: warehouseId,
                        adjustment_type: adjustmentType,
                        adjustment_amount: parseFloat(adjustmentAmount),
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.product) {
                        const product = data.product;
                        const row = document.querySelector(`tr[data-id="${product.id}"]`);

                        if (row) {
                            const stockQuantityElement = row.querySelector(".sort-quantity .fw-bold");
                            if (stockQuantityElement) {
                                const globalWarehouseSelect = document.querySelector('select[name="warehouse_id"]');
                                const globalWarehouseId = globalWarehouseSelect ? globalWarehouseSelect.value : '';
                                
                                let displayStock = product.total_stock;
                                if (globalWarehouseId && product.warehouse_id == globalWarehouseId) {
                                    displayStock = product.warehouse_stock;
                                }
                                
                                stockQuantityElement.textContent = displayStock;

                                let badgeElement = row.querySelector(".sort-quantity .badge");
                                if (!badgeElement) {
                                    const badgeContainer = row.querySelector(".sort-quantity");
                                    if (badgeContainer) {
                                        badgeElement = document.createElement("span");
                                        badgeContainer.appendChild(badgeElement);
                                    }
                                }

                                if (badgeElement) {
                                    const [badgeClass, badgeText] = getStockClassAndText(displayStock, product.low_stock_threshold);
                                    badgeElement.className = `badge ${badgeClass}`;
                                    badgeElement.textContent = badgeText;
                                }
                            }

                            const thresholdElement = row.querySelector(".sort-quantity small.text-muted");
                            if (thresholdElement && product.low_stock_threshold !== undefined) {
                                thresholdElement.textContent = `Threshold: ${product.low_stock_threshold}`;
                            }
                        }

                        const modalEl = document.getElementById('adjustStockModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }

                        InventMagApp.showToast('Success', data.message, 'success');
                    } else {
                        InventMagApp.showToast('Error', data.message || 'Failed to update stock.', 'error');
                    }
                })
                .catch(error => {
                    InventMagApp.showToast('Error', 'An error occurred during stock adjustment.', 'error');
                })
                .finally(() => {
                    confirmAdjustStockBtn.disabled = false;
                    confirmAdjustStockBtn.innerHTML = originalText;
                });
            });
        }
}