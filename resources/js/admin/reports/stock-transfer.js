export function initStockTransfer() {
    const fromWarehouseSelect = document.querySelector('select[name="from_warehouse_id"]');
    const productSelect = document.getElementById('productSelect');
    const availableQtyInput = document.getElementById('availableQuantity');
    const form = document.getElementById('stockTransferForm');

    if (!fromWarehouseSelect || !productSelect) {
        return;
    }

    fromWarehouseSelect.addEventListener('change', function() {
        const warehouseId = this.value;
        productSelect.innerHTML = '<option value="">{{ __('messages.select_product') }}</option>';
        availableQtyInput.value = '';

        if (warehouseId) {
            fetch(`/admin/reports/stock-transfer/products/${warehouseId}`)
                .then(response => response.json())
                .then(products => {
                    products.forEach(product => {
                        const option = document.createElement('option');
                        option.value = product.id;
                        option.textContent = `${product.name} (${product.code}) - Stock: ${product.quantity}`;
                        option.dataset.quantity = product.quantity;
                        productSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                });
        }
    });

    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        availableQtyInput.value = selectedOption.dataset.quantity || '0';
    });

    if (form) {
        form.addEventListener('submit', function(e) {
            const fromWarehouse = fromWarehouseSelect.value;
            const toWarehouse = this.querySelector('select[name="to_warehouse_id"]').value;

            if (fromWarehouse === toWarehouse) {
                e.preventDefault();
                alert('{{ __('messages.stock_transfer_same_warehouse_error') }}');
            }
        });
    }

    const selectedWarehouseId = fromWarehouseSelect.value;
    if (selectedWarehouseId) {
        fromWarehouseSelect.dispatchEvent(new Event('change'));
    }
}

export function initStockTransferEventListeners() {
    document.addEventListener('DOMContentLoaded', initStockTransfer);
}
