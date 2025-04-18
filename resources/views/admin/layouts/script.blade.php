<script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}" defer></script>
<script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}" defer></script>
<script src="{{ asset('tabler/dist/js/demo-theme.min.js?1692870487') }}"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
{{-- SCRIPT FOR ADMIN POS  --}}
@if (request()->is('admin/pos/'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productSelect = document.getElementById('product_id');
            const quantityField = document.getElementById('quantity');
            const newPriceField = document.getElementById('new_price');
            const addProductButton = document.getElementById('addProduct');
            const productTableBody = document.getElementById('productTableBody');
            const totalPriceElement = document.getElementById('totalPrice');
            const productsField = document.getElementById('productsField');

            let products = JSON.parse(localStorage.getItem('poProducts')) || [];

            function saveToLocalStorage() {
                localStorage.setItem('poProducts', JSON.stringify(products));
            }

            function renderTable() {
                productTableBody.innerHTML = '';
                let total = 0;

                products.forEach((product, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${product.name}</td>
                        <td>${product.quantity}</td>
                        <td>${formatCurrency(product.price)}</td>
                        <td>${formatCurrency(product.total)}</td>
                        <td><button class="btn btn-danger btn-sm removeProduct" data-index="${index}">Remove</button></td>
                    `;
                    productTableBody.appendChild(row);
                    total += product.total;
                });

                totalPriceElement.innerText = formatCurrency(total);
                productsField.value = JSON.stringify(products);

                document.querySelectorAll('.removeProduct').forEach(button => {
                    button.addEventListener('click', function() {
                        products.splice(this.dataset.index, 1);
                        saveToLocalStorage();
                        renderTable();
                    });
                });
            }

            productSelect.addEventListener('change', function() {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                newPriceField.value = selectedOption.getAttribute('data-price');
            });

            addProductButton.addEventListener('click', function() {
                const productId = productSelect.value;
                const productName = productSelect.options[productSelect.selectedIndex].text;
                const quantity = parseInt(quantityField.value);
                const price = parseFloat(newPriceField.value);
                const total = price * quantity;

                if (!productId || !quantity || !price) return alert('Complete all fields!');

                const existingProduct = products.find(p => p.id == productId);
                if (existingProduct) {
                    existingProduct.quantity += quantity;
                    existingProduct.total += total;
                } else {
                    products.push({
                        id: productId,
                        name: productName,
                        quantity,
                        price,
                        total
                    });
                }

                saveToLocalStorage();
                renderTable();
                productSelect.value = '';
                quantityField.value = '';
                newPriceField.value = '';
            });

            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                }).format(amount);
            }

            renderTable();
        });
    </script>
@endif
{{-- SCRIPT FOR SETTING PROFILE --}}
@if (request()->is('admin/setting/profile'))
    <script>
        function togglePasswordModal() {
            let newPassword = document.getElementById('new_password').value;
            let confirmPasswordContainer = document.getElementById('confirmPasswordContainer');

            if (newPassword) {
                confirmPasswordContainer.style.display = 'block';
            } else {
                confirmPasswordContainer.style.display = 'none';
            }
        }

        document.getElementById('profileForm').addEventListener('submit', function(event) {
            let newPassword = document.getElementById('new_password').value;

            if (newPassword) {
                event.preventDefault();
                openPasswordModal();
            }
        });

        function openPasswordModal() {
            const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
            modal.show();
        }

        function submitProfileForm() {
            let currentPassword = document.getElementById('modal_current_password').value;
            let newPassword = document.getElementById('new_password').value;
            let confirmNewPassword = document.getElementById('confirm_new_password').value;

            if (!currentPassword) {
                alert('Please enter your current password.');
                return;
            }

            if (newPassword && newPassword !== confirmNewPassword) {
                alert('New password and re-entered password do not match.');
                return;
            }

            document.getElementById('current_password').value = currentPassword;
            document.getElementById('profileForm').submit();
        }
    </script>
@endif
{{-- SCRIPT FOR ADMIN SALES CREATE --}}
@if (request()->is('admin/sales/create'))
    <script>
        //automatically input the due date
        document.addEventListener('DOMContentLoaded', function() {
            const orderDateField = document.getElementById('order_date');
            const dueDateField = document.getElementById('due_date');
            const customerSelect = document.getElementById('customer_id');

            // event listener for customer selection change
            customerSelect.addEventListener('change', function() {
                calculateDueDate();
            });

            // event listener for order date selection change
            orderDateField.addEventListener('change', function() {
                calculateDueDate();
            });

            // function to calculate the due date
            function calculateDueDate() {
                const orderDateValue = orderDateField.value;
                const selectedOption = customerSelect.options[customerSelect.selectedIndex];

                if (!orderDateValue || !selectedOption) {
                    return; // Exit if either field is empty
                }

                const orderDate = new Date(orderDateValue);
                const paymentTerms = selectedOption.dataset.paymentTerms;

                if (paymentTerms) {
                    // calculate the due date by adding payment terms (in days) to the order date
                    orderDate.setDate(orderDate.getDate() + parseInt(paymentTerms));

                    // format the due date to YYYY-MM-DD
                    const dueDate = orderDate.toISOString().split('T')[0];
                    dueDateField.value = dueDate;
                }
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const taxRateInput = document.getElementById('taxRateInput');
            const taxRate = parseFloat(taxRateInput.value) || 0; // Get tax rate from hidden input
            const customerSelect = document.getElementById('customer_id');
            const productSelect = document.getElementById('product_id');
            const customerPriceField = document.getElementById('customer_price');
            const pastPriceField = document.getElementById('past_price');
            const priceField = document.getElementById('price');
            const sellingPriceField = document.getElementById('selling_price');
            const quantityField = document.getElementById('quantity');
            const addProductButton = document.getElementById('addProduct');
            const clearProductsButton = document.getElementById('clearProducts');
            const productTableBody = document.getElementById('productTableBody');
            const productsField = document.getElementById('productsField');
            const discountTotalValue = document.getElementById('discountTotalValue');
            const discountTotalType = document.getElementById('discountTotalType');
            const applyTotalDiscount = document.getElementById('applyTotalDiscount');
            const orderDiscountInput = document.getElementById('orderDiscountInput');
            const orderDiscountTypeInput = document.getElementById('orderDiscountTypeInput');

            let products = JSON.parse(localStorage.getItem('salesProducts')) || [];
            let orderDiscount = 0;
            let orderDiscountType = 'fixed';

            function saveToLocalStorage() {
                localStorage.setItem('salesProducts', JSON.stringify(products));
            }

            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0
                }).format(amount);
            }

            function calculateDiscountAmount(price, quantity, discount, discountType) {
                // Calculate the discount amount based on discount type
                if (discountType === 'percentage') {
                    return (price * discount / 100) * quantity;
                }
                return discount * quantity; // Fixed amount
            }

            function calculateTotal(price, quantity, discount, discountType) {
                const discountAmount = calculateDiscountAmount(price, quantity, discount, discountType);
                return (price * quantity) - discountAmount;
            }

            function calculateOrderDiscount(subtotal) {
                if (orderDiscountType === 'percentage') {
                    return subtotal * orderDiscount / 100;
                }
                return orderDiscount;
            }

            function updateTotalPrice() {
                let subtotal = 0;
                let totalBeforeDiscounts = 0;
                let itemDiscount = 0;

                products.forEach(product => {
                    // This is the raw subtotal before any discounts
                    const productSubtotal = Number(product.price) * Number(product.quantity);
                    totalBeforeDiscounts += productSubtotal;

                    // This is after per-product discounts
                    subtotal += product.total;

                    const productDiscount = calculateDiscountAmount(
                        product.price,
                        product.quantity,
                        product.discount,
                        product.discountType
                    );

                    itemDiscount += productDiscount;
                });

                const orderDiscountAmount = calculateOrderDiscount(totalBeforeDiscounts);
                const totalDiscount = itemDiscount + orderDiscountAmount;
                const taxableAmount = subtotal - orderDiscountAmount;
                const taxAmount = taxableAmount * (taxRate / 100);
                const finalTotal = taxableAmount + taxAmount;

                // Update UI
                document.getElementById('subtotal').innerText = formatCurrency(subtotal);
                document.getElementById('orderDiscountTotal').innerText = formatCurrency(orderDiscountAmount);
                document.getElementById('taxTotal').innerText = formatCurrency(taxAmount);
                document.getElementById('finalTotal').innerText = formatCurrency(finalTotal);

                // Update hidden inputs for form submission
                document.getElementById('totalDiscountInput').value = itemDiscount;
                document.getElementById('orderDiscountInput').value = orderDiscount;
                document.getElementById('orderDiscountTypeInput').value = orderDiscountType;
                document.getElementById('taxInput').value = taxAmount;
                productsField.value = JSON.stringify(products);
            }

            function renderTable() {
                productTableBody.innerHTML = '';

                products.forEach((product, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                                <td>${index + 1}</td>
                                <td>${product.name}</td>
                                <td>
                                    <input type="number" class="form-control quantity-input"
                                        value="${product.quantity}" data-id="${product.id}" min="1" style="width:80px;" />
                                </td>
                                <td>
                                    <input type="number" class="form-control price-input"
                                        value="${product.price}" data-id="${product.id}" min="0" style="width:100px;" />
                                </td>
                                <td>
                                    <div class="input-group" style="width:200px;">
                                        <input type="number" class="form-control discount-input"
                                            value="${product.discount}" data-id="${product.id}" min="0" />
                                        <select class="form-select discount-type" data-id="${product.id}">
                                            <option value="fixed" ${product.discountType === 'fixed' ? 'selected' : ''}>Rp</option>
                                            <option value="percentage" ${product.discountType === 'percentage' ? 'selected' : ''}>%</option>
                                        </select>
                                    </div>
                                </td>
                                <td class="product-total">${formatCurrency(product.total)}</td>
                                <td style="text-align:center">
                                    <button class="btn btn-danger btn-sm removeProduct" data-id="${product.id}">Remove</button>
                                </td>
                            `;
                    productTableBody.appendChild(row);
                });

                updateTotalPrice();
                productsField.value = JSON.stringify(products);

                // Event listeners
                document.querySelectorAll('.removeProduct').forEach(button => {
                    button.addEventListener('click', function() {
                        products = products.filter(p => p.id != this.dataset.id);
                        saveToLocalStorage();
                        renderTable();
                    });
                });

                document.querySelectorAll('.quantity-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const id = this.dataset.id;
                        const value = parseInt(this.value) || 1;
                        const product = products.find(p => p.id == id);
                        if (product) {
                            product.quantity = value;
                            product.total = calculateTotal(product.price, product.quantity, product
                                .discount, product.discountType);
                            saveToLocalStorage();
                            this.closest('tr').querySelector('.product-total').innerText =
                                formatCurrency(product.total);
                            updateTotalPrice();
                        }
                    });
                });

                document.querySelectorAll('.price-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const id = this.dataset.id;
                        const value = parseFloat(this.value) || 0;
                        const product = products.find(p => p.id == id);
                        if (product) {
                            product.price = value;
                            product.total = calculateTotal(product.price, product.quantity, product
                                .discount, product.discountType);
                            saveToLocalStorage();
                            this.closest('tr').querySelector('.product-total').innerText =
                                formatCurrency(product.total);
                            updateTotalPrice();
                        }
                    });
                });

                document.querySelectorAll('.discount-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const id = this.dataset.id;
                        const value = parseFloat(this.value) || 0;
                        const product = products.find(p => p.id == id);
                        if (product) {
                            product.discount = value;
                            product.total = calculateTotal(product.price, product.quantity, product
                                .discount, product.discountType);
                            saveToLocalStorage();
                            this.closest('tr').querySelector('.product-total').innerText =
                                formatCurrency(product.total);
                            updateTotalPrice();
                        }
                    });
                });

                document.querySelectorAll('.discount-type').forEach(select => {
                    select.addEventListener('change', function() {
                        const id = this.dataset.id;
                        const value = this.value;
                        const product = products.find(p => p.id == id);
                        if (product) {
                            product.discountType = value;
                            product.total = calculateTotal(product.price, product.quantity, product
                                .discount, product.discountType);
                            saveToLocalStorage();
                            this.closest('tr').querySelector('.product-total').innerText =
                                formatCurrency(product.total);
                            updateTotalPrice();
                        }
                    });
                });
            }

            // Apply order discount
            applyTotalDiscount.addEventListener('click', function() {
                orderDiscount = parseFloat(discountTotalValue.value) || 0;
                orderDiscountType = discountTotalType.value;
                updateTotalPrice();
            });

            // Function to fetch customer's past purchase price for a product
            function fetchCustomerPastPrice() {
                const customerId = customerSelect.value;
                const productId = productSelect.value;

                if (!customerId || !productId) {
                    pastPriceField.value = '';
                    return;
                }

                // Make AJAX request to get past price
                fetch(`/admin/sales/get-customer-price/${customerId}/${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.past_price) {
                            pastPriceField.value = data.past_price;
                            // Optionally populate the customer price field with past price
                            customerPriceField.value = data.past_price;
                        } else {
                            pastPriceField.value = '0';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching past price:', error);
                        pastPriceField.value = '0';
                    });
            }

            // When product is selected, populate price fields
            productSelect.addEventListener('change', function() {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                if (!selectedOption || selectedOption.value === '') {
                    priceField.value = '';
                    sellingPriceField.value = '';
                    customerPriceField.value = '';
                    return;
                }

                // Set prices from data attributes
                priceField.value = selectedOption.getAttribute('data-price');
                sellingPriceField.value = selectedOption.getAttribute('data-selling-price');

                // Also fetch the customer's past price
                fetchCustomerPastPrice();
            });

            // Call fetchCustomerPastPrice when customer changes
            customerSelect.addEventListener('change', fetchCustomerPastPrice);

            // Add product to the list
            addProductButton.addEventListener('click', function() {
                const productId = productSelect.value;
                const productName = productSelect.options[productSelect.selectedIndex].text;
                const quantity = parseInt(quantityField.value);
                const price = parseFloat(customerPriceField.value);
                const discount = parseFloat(document.getElementById('discount').value) || 0;
                const discountType = document.getElementById('discount_type').value;

                // Calculate the total using the correct discount type
                const total = calculateTotal(price, quantity, discount, discountType);

                products.push({
                    id: productId,
                    name: productName,
                    quantity,
                    price,
                    discount,
                    discountType,
                    total
                });

                saveToLocalStorage();
                renderTable();

                // Reset form fields
                productSelect.value = '';
                quantityField.value = '';
                customerPriceField.value = '';
                document.getElementById('discount').value = '';
                priceField.value = '';
                sellingPriceField.value = '';
                pastPriceField.value = '';
            });

            // Clear products button
            clearProductsButton.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear all products?')) {
                    products = [];
                    saveToLocalStorage();
                    renderTable();
                }
            });

            // Clear localStorage when form is submitted
            document.querySelector('form').addEventListener('submit', function(e) {
                // Prevent form submission if no products added
                if (products.length === 0) {
                    e.preventDefault();
                    alert('Please add at least one product before submitting.');
                    return false;
                }

                localStorage.removeItem('salesProducts');
            });

            // Initial render
            renderTable();
        });
    </script>
@endif
{{-- SCRIPT FOR ADMIN SALES EDIT --}}
@if (request()->is('admin/sales/edit/*'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function calculateTotals() {
                let subtotal = 0;
                let totalUnitDiscount = 0;

                // Calculate per-item amounts
                document.querySelectorAll("tbody tr").forEach(row => {
                    let itemId = row.querySelector(".quantity-input").dataset.itemId;
                    let quantity = parseFloat(row.querySelector(`.quantity-input[data-item-id='${itemId}']`)
                        .value) || 0;
                    let price = parseFloat(row.querySelector(`.price-input[data-item-id='${itemId}']`)
                        .value) || 0;
                    let discountInput = row.querySelector(`.discount-input[data-item-id='${itemId}']`);
                    let discountTypeSelect = row.querySelector(
                        `.discount-type-input[data-item-id='${itemId}']`);

                    let discountValue = parseFloat(discountInput?.value) || 0;
                    let discountType = discountTypeSelect?.value || "percentage";
                    let discountAmount = 0;
                    let netUnitPrice = price;

                    // Apply the correct discount type PER UNIT
                    if (discountType === "percentage") {
                        // For percentage, apply to each unit's price
                        discountAmount = price * (discountValue / 100);
                        netUnitPrice = price - discountAmount;
                    } else {
                        // For fixed, apply the full discount to each unit
                        discountAmount = discountValue; // Apply full fixed discount to each unit
                        netUnitPrice = price - discountAmount;
                    }

                    // Calculate total item amount
                    let netAmount = netUnitPrice * quantity;

                    // Update the amount field
                    row.querySelector(`.amount-input[data-item-id='${itemId}']`).value = Math.floor(
                        netAmount);

                    // Add to running totals
                    subtotal += netAmount;
                    totalUnitDiscount += discountAmount * quantity;
                });

                // Rest of your function remains unchanged
                const discountTotalValue = parseFloat(document.getElementById("discountTotalValue")?.value) || 0;
                const discountTotalType = document.getElementById("discountTotalType")?.value || "percentage";
                let orderDiscountAmount = 0;

                // Calculate order discount
                if (discountTotalType === "percentage") {
                    orderDiscountAmount = subtotal * (discountTotalValue / 100);
                } else {
                    orderDiscountAmount = discountTotalValue;
                }

                // Update order discount total display
                document.getElementById("orderDiscountTotal").innerText = Math.floor(orderDiscountAmount)
                    .toLocaleString('id-ID');

                // Calculate final amounts - subtotal is already after unit discounts
                const totalAfterAllDiscounts = subtotal - orderDiscountAmount;

                // Update subtotal display
                document.getElementById("subtotal").innerText = Math.floor(subtotal).toLocaleString(
                    'id-ID');

                // Get tax rate from hidden input or fetch from API
                const taxRateInput = document.getElementById("taxRateInput");
                const taxRate = parseFloat(taxRateInput?.value) || 0;

                // Calculate tax amount
                const taxAmount = totalAfterAllDiscounts * (taxRate / 100);

                // Calculate grand total
                const grandTotal = totalAfterAllDiscounts + taxAmount;

                // Update displays
                if (document.getElementById("totalTax")) {
                    document.getElementById("totalTax").innerText = Math.floor(taxAmount).toLocaleString('id-ID');
                }
                document.getElementById("finalTotal").innerText = Math.floor(grandTotal).toLocaleString('id-ID');

                // Update hidden inputs
                document.getElementById("grandTotalInput").value = Math.floor(grandTotal);
                document.getElementById("totalDiscountInput").value = Math.floor(totalUnitDiscount +
                    orderDiscountAmount);
                document.getElementById("taxInput").value = Math.floor(taxAmount);

                // Update total_tax input if it exists
                const totalTaxInput = document.getElementById("total_tax_input");
                if (totalTaxInput) {
                    totalTaxInput.value = Math.floor(taxAmount);
                }
            }

            // Use Event Delegation to Handle All Input Events
            document.addEventListener("input", function(event) {
                if (event.target.matches(
                        ".quantity-input, .price-input, .discount-input, .discount-type-input, #discountTotalValue, #discountTotalType"
                    )) {
                    calculateTotals();
                }
            });

            // Automatically Trigger Calculation on Page Load
            calculateTotals();

            // Due Date Calculation
            const orderDateField = document.getElementById('order_date');
            const dueDateField = document.getElementById('due_date');
            const customerSelect = document.getElementById('customer_id');

            customerSelect?.addEventListener('change', calculateDueDate);
            orderDateField?.addEventListener('change', calculateDueDate);

            function calculateDueDate() {
                const orderDateValue = orderDateField.value;
                const selectedOption = customerSelect.options[customerSelect.selectedIndex];

                if (!orderDateValue || !selectedOption) return;

                const orderDate = new Date(orderDateValue);
                const paymentTerms = parseInt(selectedOption.dataset.paymentTerms) || 0;

                if (paymentTerms > 0) {
                    orderDate.setDate(orderDate.getDate() + paymentTerms);
                    dueDateField.value = orderDate.toISOString().split('T')[0];
                }
            }
        });
    </script>
@endif
{{-- SCRIPT FOR ADMIN PO CREATE --}}
@if (request()->is('admin/po/create', 'admin/po/edit/*'))
    <script src="{{ asset('js/admin/purchase-order.js') }}"></script>
@endif
{{-- SCRIPT FOR ADMIN DASHBOARD --}}
@if (request()->is('admin/dashboard'))
    <script src="{{ asset('tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}" defer></script>
    <script>
        window.onload = function() {
            var chartElement = document.querySelector("#chart-container");

            if (!chartElement) {
                console.error("Chart container not found! Check if #chart-container exists in the DOM.");
                return;
            }

            var invoicesData = @json($chartData);
            var earningsData = @json($chartDataEarning);

            function renderChart(type) {
                var options;
                if (type === "invoices") {
                    options = {
                        series: [{
                                name: "Invoices Count",
                                type: "bar",
                                data: invoicesData.map(item => item.invoice_count)
                            },
                            {
                                name: "Total Amount",
                                type: "line",
                                data: invoicesData.map(item => item.total_amount_raw)
                            }
                        ],
                        xaxis: {
                            categories: invoicesData.map(item => item.date)
                        }
                    };
                }

                if (type === "earnings") {
                    options = {
                        series: [{
                            name: "Daily Earnings",
                            type: "line",
                            data: earningsData.map(item => item.total_amount_raw)
                        }],
                        xaxis: {
                            categories: earningsData.map(item => item.date)
                        }
                    };
                }

                options = {
                    ...options,
                    chart: {
                        type: "line",
                        height: 400
                    },
                    stroke: {
                        width: [2, 4]
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false
                        }
                    },
                    colors: ["#206bc4", "#f59f00"],
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return "{{ \App\Helpers\CurrencyHelper::format(0) }}".replace("0", val);
                            }
                        }
                    }
                };

                if (window.chartInstance) {
                    window.chartInstance.destroy();
                }

                window.chartInstance = new ApexCharts(chartElement, options);
                window.chartInstance.render();
            }

            // Initial Load
            renderChart("invoices");

            // Tab Click Events
            document.querySelector("#invoices-tab").addEventListener("click", function() {
                renderChart("invoices");
            });

            document.querySelector("#earnings-tab").addEventListener("click", function() {
                renderChart("earnings");
            });
        };
    </script>
@endif
{{-- SCRIPT FOR WAREHOUSE --}}
@if (request()->is('admin/warehouse'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const editWarehouseModal = document.getElementById("editWarehouseModal");

            editWarehouseModal.addEventListener("show.bs.modal", function(event) {
                // Get the button that triggered the modal
                const button = event.relatedTarget;

                // Get warehouse data from the button attributes
                const warehouseId = button.getAttribute("data-id");
                const warehouseName = button.getAttribute("data-name");
                const warehouseAddress = button.getAttribute("data-address");
                const warehouseDescription = button.getAttribute("data-description");

                // Populate the form fields inside the modal
                document.getElementById("warehouseId").value = warehouseId;
                document.getElementById("warehouseNameEdit").value = warehouseName;
                document.getElementById("warehouseAddressEdit").value = warehouseAddress;
                document.getElementById("warehouseDescriptionEdit").value = warehouseDescription;

                // Set the form action dynamically
                document.getElementById("editWarehouseForm").action =
                    "{{ route('admin.warehouse.update', '') }}/" + warehouseId;
            });
        });
    </script>
@endif
{{-- SCRIPT FOR UNIT --}}
@if (request()->is('admin/setting/unit'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const editUnitModal = document.getElementById("editUnitModal");

            editUnitModal.addEventListener("show.bs.modal", function(event) {
                // Get the button that triggered the modal
                const button = event.relatedTarget;

                // Get unit data from the button attributes
                const unitId = button.getAttribute("data-id");
                const unitSymbol = button.getAttribute("data-symbol");
                const unitName = button.getAttribute("data-name");

                // Populate the form fields inside the modal
                document.getElementById("unitId").value = unitId;
                document.getElementById("unitSymbolEdit").value = unitSymbol;
                document.getElementById("unitNameEdit").value = unitName;

                // Set the form action dynamically
                document.getElementById("editUnitForm").action =
                    "{{ route('admin.setting.unit.update', '') }}/" + unitId;
            });
        });
    </script>
@endif
{{-- SCRIPT FOR CATEGORY --}}
@if (request()->is('admin/setting/category'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const editCategoryModal = document.getElementById("editCategoryModal");

            editCategoryModal.addEventListener("show.bs.modal", function(event) {
                // Get the button that triggered the modal
                const button = event.relatedTarget;

                // Get category data from the button attributes
                const categoryId = button.getAttribute("data-id");
                const categoryName = button.getAttribute("data-name");
                const categoryDescription = button.getAttribute("data-description");

                // Populate the form fields inside the modal
                document.getElementById("categoryId").value = categoryId;
                document.getElementById("categoryNameEdit").value = categoryName;
                document.getElementById("categoryDescriptionEdit").value = categoryDescription;

                // Set the form action dynamically
                document.getElementById("editCategoryForm").action =
                    "{{ route('admin.setting.category.update', '') }}/" + categoryId;
            });
        });
    </script>
@endif
{{-- SCRIPT FOR SUPPLIER --}}
@if (request()->is('admin/supplier'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const editSupplierModal = document.getElementById("editSupplierModal");

            editSupplierModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;

                if (!button) return; // Prevent errors if button is null

                // Get supplier data from the button attributes
                const supplierId = button.getAttribute("data-id") || "";
                const supplierCode = button.getAttribute("data-code") || "";
                const supplierName = button.getAttribute("data-name") || "";
                const supplierAddress = button.getAttribute("data-address") || "";
                const supplierPhone = button.getAttribute("data-phone_number") || "";
                const supplierLocation = button.getAttribute("data-location") || "";
                const supplierPayment = button.getAttribute("data-payment_terms") || "";

                // Populate the form fields inside the modal
                document.getElementById("supplierId").value = supplierId;
                if (document.getElementById("supplierCodeEdit")) {
                    document.getElementById("supplierCodeEdit").value = supplierCode;
                }
                document.getElementById("supplierNameEdit").value = supplierName;
                document.getElementById("supplierAddressEdit").value = supplierAddress;
                document.getElementById("supplierPhoneEdit").value = supplierPhone;
                document.getElementById("supplierLocationEdit").value = supplierLocation;
                document.getElementById("supplierPaymentTermsEdit").value = supplierPayment;

                // Set the form action dynamically
                document.getElementById("editSupplierForm").action =
                    "{{ route('admin.supplier.update', '') }}/" + supplierId;
            });
        });
    </script>
@endif
{{-- SCRIPT FOR CUSTOMER --}}
@if (request()->is('admin/customer'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const editCustomerModal = document.getElementById("editCustomerModal");

            editCustomerModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;

                if (!button) return; // Prevent errors if button is null

                // Get supplier data from the button attributes
                const customerId = button.getAttribute("data-id") || "";
                const customerCode = button.getAttribute("data-code") || "";
                const customerName = button.getAttribute("data-name") || "";
                const customerAddress = button.getAttribute("data-address") || "";
                const customerPhone = button.getAttribute("data-phone_number") || "";
                const customerLocation = button.getAttribute("data-location") || "";
                const customerPayment = button.getAttribute("data-payment_terms") || "";

                // Populate the form fields inside the modal
                document.getElementById("customerId").value = customerId;
                document.getElementById("customerNameEdit").value = customerName;
                document.getElementById("customerAddressEdit").value = customerAddress;
                document.getElementById("customerPhoneEdit").value = customerPhone;
                document.getElementById("customerPaymentTermsEdit").value = customerPayment;

                // Set the form action dynamically
                document.getElementById("editCustomerForm").action =
                    "{{ route('admin.customer.update', '') }}/" + customerId;
            });
        });
    </script>
@endif
{{-- SCRIPT FOR SORTING TABLE --}}
@if (request()->is(
        'admin/ds',
        'admin/po',
        'admin/sales',
        'admin/product',
        'admin/supplier',
        'admin/customer',
        'admin/unit',
        'admin/category'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize List.js
            const list = new List('invoiceTableContainer', {
                sortClass: 'table-sort',
                listClass: 'table-tbody',
                valueNames: [
                    'sort-no',
                    'sort-invoice',
                    'sort-date',
                    'sort-total',
                    'sort-supplier',
                    'sort-orderdate',
                    'sort-quantity',
                    'sort-name',
                    'sort-description',
                    'sort-phonenumber',
                    'sort-code',
                    'sort-address',
                    'sort-location',
                    'sort-paymentterms',
                    'sort-category',
                    'sort-price',
                    'sort-sellingprice',
                    'sort-unit',
                    {
                        name: 'sort-duedate',
                        attr: 'data-date'
                    },
                    {
                        name: 'sort-amount',
                        attr: 'data-amount'
                    },
                    'sort-amount',
                    'sort-payment',
                    'sort-status',
                ],
            });

            // Enhanced search for formatted and raw amounts
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('#invoiceTableBody tr');

            searchInput.addEventListener('keyup', function() {
                const searchTerm = searchInput.value.toLowerCase();

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const rawAmount = row.querySelector('.raw-amount')?.textContent.toLowerCase() ||
                        '';

                    // Match either formatted text OR raw amount
                    row.style.display = (text.includes(searchTerm) || rawAmount.includes(
                        searchTerm)) ? '' : 'none';
                });
            });
        });
    </script>
@endif
{{-- SCRIPT FOR CURRENCY SETTING --}}
@if (request()->is('admin/setting/currency'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const showModalButton = document.getElementById("showModalButton");
            const confirmSubmitButton = document.getElementById("confirmSubmit");
            const currencySettingsForm = document.getElementById("currencySettingsForm");

            showModalButton.addEventListener("click", function() {
                const confirmModal = new bootstrap.Modal(document.getElementById("confirmModal"));
                confirmModal.show();
            });

            confirmSubmitButton.addEventListener("click", function() {
                currencySettingsForm.submit();
            });
        });
    </script>
@endif
{{-- SCRIPT FOR PROFILE SETTING --}}
@if (request()->is('admin/setting/profile'))
    <script>
        function togglePasswordModal() {
            let newPassword = document.getElementById('new_password').value;
            let confirmContainer = document.getElementById('confirmPasswordContainer');
            confirmContainer.style.display = newPassword ? 'block' : 'none';
        }

        function showPasswordModal() {
            let newPassword = document.getElementById('new_password').value;
            if (newPassword) {
                let modal = new bootstrap.Modal(document.getElementById('passwordModal'));
                modal.show();
            } else {
                document.getElementById('profileForm').submit();
            }
        }

        function submitProfileForm() {
            let currentPasswordInput = document.getElementById('modal_current_password').value;
            document.getElementById('current_password').value = currentPasswordInput;
            document.getElementById('profileForm').submit();
        }
    </script>
@endif
{{-- ERROR MODAL --}}
@if ($errors->any())
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-triangle icon text-danger icon-lg mb-4"></i>
                    <h3 class="mb-3">Error!</h3>
                    <div class="text-secondary">
                        <div class="text-danger text-start text-center">
                            @foreach ($errors->all() as $error)
                                {{ $error }}<br>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger w-100" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var errorModalElement = document.getElementById("errorModal");
            var errorModal = new bootstrap.Modal(errorModalElement);
            var backdropSelector = ".modal-backdrop-custom";

            function removeBackdrop() {
                document.querySelector(backdropSelector)?.remove();
            }

            function showModal(modal) {
                document.body.style.overflow = "hidden"; // Prevent scrollbar flicker
                modal.show();
                document.body.insertAdjacentHTML("beforeend",
                    '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
            }

            function hideModal(modal) {
                modal.hide();
                removeBackdrop();
                document.body.style.overflow = ""; // Restore scrollbar
            }

            // Show error modal if it exists
            if (errorModalElement) {
                setTimeout(() => showModal(errorModal), 100);

                setTimeout(() => hideModal(errorModal), 2000);

                errorModalElement.addEventListener("hidden.bs.modal", () => {
                    removeBackdrop();
                    document.body.style.overflow = "";
                });
            }
        });
    </script>

@endif
{{-- SUCCESS MODAL --}}
@if (session('success'))
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-circle-check icon text-success icon-lg mb-4"></i>
                    <h3 class="mb-3">Success!</h3>
                    <div class="text-secondary">
                        <div class="text-success text-start text-center">
                            {{ session('success') }}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var successModalElement = document.getElementById("successModal");
            var successModal = new bootstrap.Modal(successModalElement);
            var backdropSelector = ".modal-backdrop-custom";

            function removeBackdrop() {
                document.querySelector(backdropSelector)?.remove();
            }

            function showModal(modal) {
                document.body.style.overflow = "hidden"; // Prevent scrollbar flicker
                modal.show();
                document.body.insertAdjacentHTML("beforeend",
                    '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
            }

            function hideModal(modal) {
                modal.hide();
                removeBackdrop();
                document.body.style.overflow = ""; // Restore scrollbar
            }

            // Show success modal if it exists
            if (successModalElement) {
                setTimeout(() => showModal(successModal), 100);

                setTimeout(() => hideModal(successModal), 2000);

                successModalElement.addEventListener("hidden.bs.modal", () => {
                    removeBackdrop();
                    document.body.style.overflow = "";
                });
            }
        });
    </script>
@endif
{{-- DELETE MODAL --}}
<script>
    function setDeleteFormAction(action) {
        document.getElementById('deleteForm').setAttribute('action', action);
    }
</script>
