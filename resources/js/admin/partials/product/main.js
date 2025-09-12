import { initModals } from './modals/init.js';
import { initProductModal, loadExpiringSoonProductsModal } from './modals/product.js'; // Import loadExpiringSoonProductsModal
import { initBulkSelection } from './bulkActions/selection.js';
import { initializeSearch } from './search/main.js';
import { initializeEntriesSelector, initKeyboardShortcuts, initExport } from './events.js';
import { bulkUpdateStock } from './bulkActions/stock.js'; // Import bulkUpdateStock

export function initProductPage() {
    document.addEventListener("DOMContentLoaded", function () {
        initModals();
        initProductModal();
        initBulkSelection();
        initializeSearch();
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

        const searchInput = document.getElementById("searchInput");
        if (searchInput && !searchInput.hasAttribute("data-search-initialized")) {
            initializeSearch();
            searchInput.setAttribute("data-search-initialized", "true");
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
        const adjustmentAmountLabel = document.getElementById('adjustmentAmountLabel');
        const adjustmentPreviewBadge = document.getElementById('adjustmentPreviewBadge');

        function updateAdjustmentPreview() {
            const currentStock = parseFloat(adjustCurrentStockInput.value) || 0;
            const adjustmentType = adjustmentTypeSelect.value;
            const adjustmentAmount = parseFloat(adjustmentAmountInput.value) || 0;
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

        adjustmentTypeSelect.addEventListener('change', function() {
            const type = this.value;
            if (type === 'correction') {
                adjustmentAmountLabel.textContent = 'Set to Exact Quantity';
                adjustmentAmountInput.min = 0;
            } else {
                adjustmentAmountLabel.textContent = 'Adjustment Amount';
                adjustmentAmountInput.min = 1;
            }
            updateAdjustmentPreview();
        });

        adjustmentAmountInput.addEventListener('input', updateAdjustmentPreview);

        document.getElementById('confirmAdjustStockBtn').addEventListener('click', function() {
            const productId = document.getElementById('adjustProductId').value;
            const adjustmentType = document.getElementById('adjustmentType').value;
            const adjustmentAmount = document.getElementById('adjustmentAmount').value;
            const reason = document.getElementById('adjustmentReason').value;

            if (!adjustmentAmount || parseFloat(adjustmentAmount) <= 0) {
                alert('Please enter a valid adjustment amount.');
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
                if (data.success) {
                    alert(data.message);
                    // Optionally update the stock display on the page without full reload
                    // For simplicity, we'll just reload the page for now
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during stock adjustment.');
            });
        });

        // Attach event listener for bulkUpdateStockBtn
        const bulkUpdateStockBtn = document.getElementById('bulkUpdateStockBtn');
        if (bulkUpdateStockBtn) {
            bulkUpdateStockBtn.addEventListener('click', function() {
                bulkUpdateStock(); // Call the function from stock.js
            });
        }
    });
}
