import { initModals } from './modals/init.js';
import { initProductModal, loadExpiringSoonProductsModal } from './modals/product.js'; // Import loadExpiringSoonProductsModal
import { initBulkSelection, clearProductSelection } from './bulkActions/selection.js';


import { initializeEntriesSelector, initKeyboardShortcuts, initExport } from './events.js';
import { bulkUpdateStock } from './bulkActions/stock.js'; // Import bulkUpdateStock
import { bulkDeleteProducts } from './bulkActions/delete.js'; // Import bulkDeleteProducts

function getStockClassAndText(stockQty, threshold = 10) {
    if (stockQty <= threshold) {
        return ['bg-red text-white', 'Low Stock'];
    }
    return ['bg-green text-white', 'In Stock'];
}

export function initProductPage() {
    document.addEventListener("DOMContentLoaded", function () {
        initModals();
        initProductModal();
        initBulkSelection();
        initializeEntriesSelector();
        initKeyboardShortcuts();
        initExport();

        // Listen for the expiringSoonModal to be shown and fetch data via AJAX
        const expiringSoonModalElement = document.getElementById('expiringSoonModal');
        if (expiringSoonModalElement) {
            expiringSoonModalElement.addEventListener('show.bs.modal', function () {
                // Clear previous content and show loading indicator
                const tableBody = document.getElementById('expiringSoonProductsTableBody');
                if (tableBody) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Loading expiring products...
                            </td>
                        </tr>
                    `;
                }

                fetch('/admin/product/expiring-soon') // New endpoint to fetch expiring products
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
                        console.error('Error fetching expiring products:', error);
                        if (tableBody) {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="5" class="text-center text-danger py-4">
                                        Error loading products. Please try again.
                                    </td>
                                </tr>
                            `;
                        }
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

        // Stock Adjustment Modal Logic
        window.openAdjustStockModal = function(productId, productName, currentStock) {
            document.getElementById('adjustProductId').value = productId;
            document.getElementById('adjustProductName').textContent = productName;
            document.getElementById('adjustCurrentStock').value = currentStock;
            document.getElementById('adjustmentType').value = 'increase'; // Default to increase
            document.getElementById('adjustmentAmount').value = 1; // Default amount
            document.getElementById('adjustmentReason').value = ''; // Clear reason
            document.getElementById('adjustmentAmountLabel').textContent = 'Adjustment Amount'; // Reset label
            
            // Initialize adjustment preview
            updateAdjustmentPreview();

            new bootstrap.Modal(document.getElementById('adjustStockModal')).show();
        };

        const adjustCurrentStockInput = document.getElementById('adjustCurrentStock');
        const adjustmentTypeSelect = document.getElementById('adjustmentType');
        const adjustmentAmountInput = document.getElementById('adjustmentAmount');
        const correctionAmountInput = document.getElementById('correctionAmount');
        const adjustmentPreviewBadge = document.getElementById('adjustmentPreviewBadge');

        function updateAdjustmentPreview() {
            const currentStock = parseFloat(adjustCurrentStockInput.value) || 0;
            const adjustmentType = adjustmentTypeSelect.value;
            let adjustmentAmount = 0;
            
            if (adjustmentType === 'correction') {
                adjustmentAmount = parseFloat(correctionAmountInput.value) || 0;
            } else {
                adjustmentAmount = parseFloat(adjustmentAmountInput.value) || 0;
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

        const adjustmentAmountContainer = document.getElementById('adjustmentAmountContainer');
        const correctionAmountContainer = document.getElementById('correctionAmountContainer');

        adjustmentTypeSelect.addEventListener('change', function() {
            const type = this.value;
            if (type === 'correction') {
                adjustmentAmountContainer.style.display = 'none';
                correctionAmountContainer.style.display = 'block';
            } else {
                adjustmentAmountContainer.style.display = 'block';
                correctionAmountContainer.style.display = 'none';
            }
            updateAdjustmentPreview();
        });

        adjustmentAmountInput.addEventListener('input', updateAdjustmentPreview);

        document.getElementById('confirmAdjustStockBtn').addEventListener('click', function() {
            const productId = document.getElementById('adjustProductId').value;
            const adjustmentType = document.getElementById('adjustmentType').value;
            const reason = document.getElementById('adjustmentReason').value;
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

            fetch('/admin/product/adjust-stock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    product_id: productId,
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
                            stockQuantityElement.textContent = product.stock_quantity;
                        }

                        let badgeElement = row.querySelector(".sort-quantity .badge");
                        if (!badgeElement) {
                            const badgeContainer = row.querySelector(".sort-quantity");
                            if (badgeContainer) {
                                badgeElement = document.createElement("span");
                                badgeContainer.appendChild(badgeElement);
                            }
                        }

                        if (badgeElement) {
                            const [badgeClass, badgeText] = getStockClassAndText(product.stock_quantity, product.low_stock_threshold);
                            badgeElement.className = `badge ${badgeClass}`;
                            badgeElement.textContent = badgeText;
                        }

                        const thresholdElement = row.querySelector(".sort-quantity small.text-muted");
                        if (thresholdElement && product.low_stock_threshold !== undefined) {
                            thresholdElement.textContent = `Threshold: ${product.low_stock_threshold}`;
                        }
                    }

                    const modal = bootstrap.Modal.getInstance(document.getElementById('adjustStockModal'));
                    if (modal) {
                        modal.hide();
                    }

                    InventMagApp.showToast('Success', data.message, 'success');
                } else {
                    InventMagApp.showToast('Error', data.message || 'Failed to update stock.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                InventMagApp.showToast('Error', 'An error occurred during stock adjustment.', 'error');
            });
        });

        // Attach event listener for bulkUpdateStockBtn
        const bulkUpdateStockBtn = document.getElementById('bulkUpdateStockBtn');
        if (bulkUpdateStockBtn) {
            bulkUpdateStockBtn.addEventListener('click', function() {
                bulkUpdateStock(); // Call the function from stock.js
            });
        }

        window.bulkDeleteProducts = bulkDeleteProducts; // Expose globally
        window.clearProductSelection = clearProductSelection; // Expose globally
    });
}