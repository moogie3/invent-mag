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

            // event listener for supplier selection change
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
        //automaticly inputted the product to the table
        document.addEventListener('DOMContentLoaded', function() {
            const productSelect = document.getElementById('product_id');
            const customerSelect = document.getElementById('customer_id');
            const priceField = document.getElementById('price');
            const quantityField = document.getElementById('quantity');
            const sellPriceField = document.getElementById('selling_price');
            const newPriceField = document.getElementById('new_price');
            const pastPriceField = document.getElementById('past_price');
            const addProductButton = document.getElementById('addProduct');
            const productTableBody = document.getElementById('productTableBody');
            const productsField = document.getElementById('productsField');
            const totalPriceElement = document.getElementById('totalPrice');

            let products = JSON.parse(localStorage.getItem('products')) || []; // Load previous products

            // Load previously saved products into the table
            function loadProductsFromStorage() {
                productTableBody.innerHTML = ''; // Clear table before inserting
                products.forEach(product => {
                    addProductRow(product);
                });
                updateHiddenField();
                updateTotalPrice();
            }

            function addProductRow(product) {
                const row = document.createElement('tr');
                row.innerHTML = `
            <td>${product.name}</td>
            <td>${product.quantity}</td>
            <td>${formatCurrency(product.price)}</td>
            <td>${formatCurrency(product.total)}</td>
            <td style="text-align:center">
                <button type="button" class="btn btn-danger btn-sm removeProduct">Remove</button>
            </td>
        `;
                productTableBody.appendChild(row);

                row.querySelector('.removeProduct').addEventListener('click', function() {
                    row.remove();
                    products = products.filter(p => p.id !== product.id);
                    updateHiddenField();
                    updateTotalPrice();
                    localStorage.setItem('products', JSON.stringify(products)); // Update storage
                });
            }

            productSelect.addEventListener('change', function() {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                const sellprice = selectedOption.getAttribute('data-selling-price');
                const customerId = customerSelect.value;
                const productId = productSelect.value;

                priceField.value = price ? price : '';
                sellPriceField.value = sellprice ? sellprice : '';

                if (customerId && productId) {
                    fetchPastCustomerPrice(customerId, productId);
                }
            });

            customerSelect.addEventListener('change', function() {
                const customerId = customerSelect.value;
                const productId = productSelect.value;

                if (customerId && productId) {
                    fetchPastCustomerPrice(customerId, productId);
                }
            });

            function fetchPastCustomerPrice(customerId, productId) {
                fetch(`/admin/sales/get-past-price?customer_id=${customerId}&product_id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        pastPriceField.value = data.past_price ? data.past_price : "0";
                    })
                    .catch(error => {
                        console.error('Error fetching past price:', error);
                        pastPriceField.value = "0";
                    });
            }

            addProductButton.addEventListener('click', function() {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const productId = productSelect.value;
                const productName = selectedOption.text;
                const quantity = quantityField.value;
                const price = newPriceField.value;
                const total = (parseFloat(price) * parseInt(quantity)) || 0;

                if (!productId || !quantity || !price) {
                    alert('Please select a product and enter quantity and price.');
                    return;
                }

                let existingProduct = products.find(p => p.id == productId);
                if (existingProduct) {
                    alert(`Product "${productName}" has already been added!`);
                    return;
                }

                const productData = {
                    id: productId,
                    name: productName,
                    quantity,
                    price,
                    customer_price: newPriceField.value,
                    total
                };

                products.push(productData);
                addProductRow(productData);
                updateHiddenField();
                updateTotalPrice();

                localStorage.setItem('products', JSON.stringify(products)); // Save to localStorage

                // Reset input fields
                productSelect.value = '';
                quantityField.value = '';
                priceField.value = '';
                sellPriceField.value = '';
                newPriceField.value = '';
            });

            function updateHiddenField() {
                productsField.value = JSON.stringify(products);
            }

            function updateTotalPrice() {
                const total = products.reduce((sum, product) => sum + product.total, 0);
                totalPriceElement.innerHTML = formatCurrency(total) || 0;
            }

            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(amount);
            }

            // Load products on page load
            loadProductsFromStorage();

            // Preserve products even when the form is submitted
            document.getElementById('invoiceForm').addEventListener('submit', function() {
                localStorage.setItem('products', JSON.stringify(products));
            });
        });
    </script>
@endif
{{-- SCRIPT FOR ADMIN PO CREATE --}}
@if (request()->is('admin/po/create'))
    <script>
        //automatically input the due date
        document.addEventListener('DOMContentLoaded', function() {
            const orderDateField = document.getElementById('order_date');
            const dueDateField = document.getElementById('due_date');
            const supplierSelect = document.getElementById('supplier_id');

            // event listener for supplier selection change
            supplierSelect.addEventListener('change', function() {
                calculateDueDate();
            });

            // event listener for order date selection change
            orderDateField.addEventListener('change', function() {
                calculateDueDate();
            });

            // function to calculate the due date
            function calculateDueDate() {
                const orderDateValue = orderDateField.value;
                const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];

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
            const productSelect = document.getElementById('product_id');
            const priceField = document.getElementById('last_price');
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

                products.forEach(product => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${product.name}</td>
                        <td>${product.quantity}</td>
                        <td>${formatCurrency(product.price)}</td>
                        <td>${formatCurrency(product.total)}</td>
                        <td><button class="btn btn-danger btn-sm removeProduct" data-id="${product.id}">Remove</button></td>
                    `;
                    productTableBody.appendChild(row);
                    total += product.total;
                });

                totalPriceElement.innerText = formatCurrency(total);
                productsField.value = JSON.stringify(products);

                document.querySelectorAll('.removeProduct').forEach(button => {
                    button.addEventListener('click', function() {
                        products = products.filter(p => p.id != this.dataset.id);
                        saveToLocalStorage();
                        renderTable();
                    });
                });
            }

            productSelect.addEventListener('change', function() {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                priceField.value = selectedOption.getAttribute('data-price');
            });

            addProductButton.addEventListener('click', function() {
                const productId = productSelect.value;
                const productName = productSelect.options[productSelect.selectedIndex].text;
                const quantity = parseInt(quantityField.value);
                const price = parseFloat(newPriceField.value);
                const total = price * quantity;

                if (!productId || !quantity || !price) return alert('Complete all fields!');

                if (products.some(p => p.id == productId)) return alert('Product already added.');

                products.push({
                    id: productId,
                    name: productName,
                    quantity,
                    price,
                    total
                });
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
