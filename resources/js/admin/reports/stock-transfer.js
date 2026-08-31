import { onPageLoad } from '../utils/onPageLoad.js';

onPageLoad(function() {
    if (!window.location.pathname.includes("/admin/reports/stock-transfer")) return;
    const fromWarehouseSelect = document.querySelector('select[name="from_warehouse_id"]');
    const toWarehouseSelect = document.querySelector('select[name="to_warehouse_id"]');
    const productSelect = document.getElementById('productSelect');
    const availableQtyInput = document.getElementById('availableQuantity');
    const quantityInput = document.getElementById('quantityToTransfer');
    const quantityWarning = document.getElementById('quantity_warning');
    const form = document.getElementById('stockTransferForm');

    if (!fromWarehouseSelect || !productSelect) {
        return;
    }

    // Validate warehouses and show toast if same
    function validateWarehouses() {
        const fromWarehouse = fromWarehouseSelect.value;
        const toWarehouse = toWarehouseSelect.value;

        if (fromWarehouse && toWarehouse && fromWarehouse === toWarehouse) {
            const errorMsg = form.dataset.sameWarehouseError || 'Source and destination warehouses must be different';
            
            // Try to use toast, fallback to alert
            if (typeof window.InventMagApp !== 'undefined' && window.InventMagApp.showToast) {
                window.InventMagApp.showToast('Warning', errorMsg, 'warning');
            } else {
                alert(errorMsg);
            }
            
            // Clear the to_warehouse selection
            toWarehouseSelect.value = '';
            return false;
        }
        return true;
    }

    // Load products for selected warehouse
    function loadProducts(warehouseId) {
        const defaultText = productSelect.dataset.placeholder || 'Select Product';
        productSelect.innerHTML = '<option value="">' + defaultText + '</option>';
        availableQtyInput.value = '';
        quantityInput.value = '';
        quantityInput.removeAttribute('max');
        if (quantityWarning) {
            quantityWarning.classList.add('d-none');
        }
        quantityInput.classList.remove('is-invalid');

        if (!warehouseId) {
            return;
        }

        // Show loading state
        productSelect.innerHTML = '<option value="">Loading...</option>';

        fetch('/admin/reports/stock-transfer/products/' + warehouseId)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function(products) {
                productSelect.innerHTML = '<option value="">' + defaultText + '</option>';
                
                if (!products || products.length === 0) {
                    productSelect.innerHTML = '<option value="">No products available</option>';
                    return;
                }
                
                products.forEach(function(product) {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = product.name + ' (' + product.code + ') - Stock: ' + product.quantity;
                    option.dataset.quantity = product.quantity;
                    productSelect.appendChild(option);
                });
            })
            .catch(function() {
                productSelect.innerHTML = '<option value="">Error loading products</option>';
                
                if (typeof window.InventMagApp !== 'undefined' && window.InventMagApp.showToast) {
                    window.InventMagApp.showToast('Error', 'Failed to load products', 'error');
                }
            });
    }

    // Event listener for From Warehouse change
    fromWarehouseSelect.addEventListener('change', function() {
        loadProducts(this.value);
        validateWarehouses();
    });

    // Event listener for To Warehouse change
    if (toWarehouseSelect) {
        toWarehouseSelect.addEventListener('change', function() {
            validateWarehouses();
        });
    }

    // Event listener for Product selection
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const availableQty = selectedOption ? (parseInt(selectedOption.dataset.quantity) || 0) : 0;
        availableQtyInput.value = availableQty;
        
        if (quantityInput) {
            quantityInput.value = '';
            if (availableQty > 0) {
                quantityInput.setAttribute('max', availableQty);
            } else {
                quantityInput.removeAttribute('max');
            }
        }
        if (quantityWarning) {
            quantityWarning.classList.add('d-none');
        }
        quantityInput.classList.remove('is-invalid');
    });

    // Event listener for Quantity input validation (matches sales order pattern)
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            const max = parseInt(this.getAttribute('max')) || 0;
            let value = parseInt(this.value) || 0;
            const originalValue = value;

            if (max > 0 && value > max) {
                this.value = max;
                value = max;
            }

            // Show warning if user tried to exceed max
            if (max > 0 && originalValue > max) {
                this.classList.add('is-invalid');
                if (quantityWarning) {
                    quantityWarning.classList.remove('d-none');
                }
            } else {
                this.classList.remove('is-invalid');
                if (quantityWarning) {
                    quantityWarning.classList.add('d-none');
                }
            }
        });

        quantityInput.addEventListener('blur', function() {
            const max = parseInt(this.getAttribute('max')) || 0;
            let value = parseInt(this.value) || 0;
            if (max > 0 && value > max) {
                this.value = max;
                this.classList.add('is-invalid');
                if (quantityWarning) {
                    quantityWarning.classList.remove('d-none');
                }
            } else if (value > 0) {
                this.classList.remove('is-invalid');
                if (quantityWarning) {
                    quantityWarning.classList.add('d-none');
                }
            }
        });
    }

    // Form submission validation
    if (form) {
        form.addEventListener('submit', function(e) {
            const fromWarehouse = fromWarehouseSelect.value;
            const toWarehouse = toWarehouseSelect.value;

            if (fromWarehouse === toWarehouse) {
                e.preventDefault();
                const errorMsg = form.dataset.sameWarehouseError || 'Source and destination warehouses must be different';
                
                if (typeof window.InventMagApp !== 'undefined' && window.InventMagApp.showToast) {
                    window.InventMagApp.showToast('Warning', errorMsg, 'warning');
                } else {
                    alert(errorMsg);
                }
            }

            // Validate quantity before submit
            const max = parseInt(quantityInput.getAttribute('max')) || 0;
            const value = parseInt(quantityInput.value) || 0;
            if (max > 0 && value > max) {
                e.preventDefault();
                if (typeof window.InventMagApp !== 'undefined' && window.InventMagApp.showToast) {
                    window.InventMagApp.showToast('Warning', 'Quantity exceeds available stock', 'warning');
                }
            }
        });
    }

    // Load products if warehouse already selected (e.g., after validation error)
    if (fromWarehouseSelect.value) {
        loadProducts(fromWarehouseSelect.value);
    }
});