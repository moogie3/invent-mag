document.addEventListener('DOMContentLoaded', function() {
    const fromWarehouseSelect = document.querySelector('select[name="from_warehouse_id"]');
    const toWarehouseSelect = document.querySelector('select[name="to_warehouse_id"]');
    const productSelect = document.getElementById('productSelect');
    const availableQtyInput = document.getElementById('availableQuantity');
    const form = document.getElementById('stockTransferForm');

    if (!fromWarehouseSelect || !productSelect) {
        console.error('Stock Transfer: Required elements not found');
        return;
    }

    console.log('Stock Transfer: Initializing...');

    // Validate warehouses and show toast if same
    function validateWarehouses() {
        const fromWarehouse = fromWarehouseSelect.value;
        const toWarehouse = toWarehouseSelect.value;

        console.log('Validating warehouses:', fromWarehouse, toWarehouse);

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
        console.log('Loading products for warehouse:', warehouseId);
        
        const defaultText = productSelect.dataset.placeholder || 'Select Product';
        productSelect.innerHTML = '<option value="">' + defaultText + '</option>';
        availableQtyInput.value = '';

        if (!warehouseId) {
            console.log('No warehouse selected, skipping product load');
            return;
        }

        // Show loading state
        productSelect.innerHTML = '<option value="">Loading...</option>';

        fetch('/admin/reports/stock-transfer/products/' + warehouseId)
            .then(function(response) {
                console.log('Fetch response:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function(products) {
                console.log('Products received:', products);
                
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
            .catch(function(error) {
                console.error('Error fetching products:', error);
                productSelect.innerHTML = '<option value="">Error loading products</option>';
                
                if (typeof window.InventMagApp !== 'undefined' && window.InventMagApp.showToast) {
                    window.InventMagApp.showToast('Error', 'Failed to load products', 'error');
                }
            });
    }

    // Event listener for From Warehouse change
    fromWarehouseSelect.addEventListener('change', function() {
        console.log('From warehouse changed:', this.value);
        loadProducts(this.value);
        validateWarehouses();
    });

    // Event listener for To Warehouse change
    toWarehouseSelect.addEventListener('change', function() {
        console.log('To warehouse changed:', this.value);
        validateWarehouses();
    });

    // Event listener for Product selection
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        availableQtyInput.value = selectedOption ? (selectedOption.dataset.quantity || '0') : '0';
    });

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
        });
    }

    // Load products if warehouse already selected (e.g., after validation error)
    if (fromWarehouseSelect.value) {
        loadProducts(fromWarehouseSelect.value);
    }
    
    console.log('Stock Transfer: Initialization complete');
});
