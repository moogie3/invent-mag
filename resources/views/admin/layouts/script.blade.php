<script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}" defer></script>
<script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}" defer></script>
<script src="{{ asset('tabler/dist/js/demo-theme.min.js?1692870487') }}"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
{{-- SCRIPT ONLY FOR SETTING PROFILE --}}
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
{{-- SCRIPT ONLY FOR ADMIN SALES CREATE --}}
@if (request()->is('admin/sales/create'))
    <script>
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

            let products = []; // Array to store added products

            productSelect.addEventListener('change', function() {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                const sellprice = selectedOption.getAttribute('data-selling-price');
                const pastprice = selectedOption.getAttribute('data-past-price');
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
                        if (data && data.past_price !== null) {
                            pastPriceField.value = data.past_price;
                        } else {
                            pastPriceField.value = "0";
                        }
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
                    existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(quantity);
                    existingProduct.total = parseFloat(existingProduct.price) * existingProduct.quantity;
                    updateTotalPrice();
                    updateHiddenField();
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
                updateHiddenField();

                // Append new row to the table
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${productName}</td>
                    <td>${quantity}</td>
                    <td>${formatCurrency(price)}</td>
                    <td>${formatCurrency(total)}</td>
                    <td style="text-align:center"><button type="button" class="btn btn-danger btn-sm removeProduct">Remove</button></td>
                `;
                productTableBody.appendChild(row);

                updateTotalPrice();

                // Reset fields
                productSelect.value = '';
                quantityField.value = '';
                priceField.value = '';
                sellPriceField.value = '';
                newPriceField.value = '';

                row.querySelector('.removeProduct').addEventListener('click', function() {
                    row.remove();
                    products = products.filter(p => p.id !== productId);
                    updateHiddenField();
                    updateTotalPrice();
                });
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
        });
    </script>
@endif
{{-- SCRIPT ONLY FOR ADMIN PO CREATE --}}
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
        //automatically input the product
        document.addEventListener('DOMContentLoaded', function() {
            const productSelect = document.getElementById('product_id');
            const priceField = document.getElementById('last_price');
            const quantityField = document.getElementById('quantity');
            const newPriceField = document.getElementById('new_price');
            const addProductButton = document.getElementById('addProduct');
            const productTableBody = document.getElementById('productTableBody');
            const productsField = document.getElementById('productsField');
            const totalPriceElement = document.getElementById('totalPrice');

            let products = []; // Array to store added products

            // Auto-fill price on product selection
            productSelect.addEventListener('change', function() {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                priceField.value = price ? price : '';
            });

            // Add product to the table
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

                // Prevent duplicate products
                if (products.some(p => p.id == productId)) {
                    alert('Product already added.');
                    return;
                }

                // Add product to the list
                const productData = {
                    id: productId,
                    name: productName,
                    quantity: quantity,
                    price: price,
                    total: total
                };
                products.push(productData);
                updateHiddenField();

                // Append new row to the table
                const row = document.createElement('tr');
                row.innerHTML = `
                <td>${productName}</td>
                <td>${quantity}</td>
                <td>${formatCurrency(price)}</td>
                <td>${formatCurrency(total)}</td>
                <td style="text-align:center"><button type="button" class="btn btn-danger btn-sm removeProduct">Remove</button></td>
            `;
                productTableBody.appendChild(row);

                updateTotalPrice();

                // Reset fields
                productSelect.value = '';
                quantityField.value = '';
                priceField.value = '';
                newPriceField.value = '';

                // Remove product event
                row.querySelector('.removeProduct').addEventListener('click', function() {
                    row.remove();
                    products = products.filter(p => p.id !== productId);
                    updateHiddenField();
                    updateTotalPrice();
                });
            });

            // Update hidden input field with JSON data
            function updateHiddenField() {
                productsField.value = JSON.stringify(products);
            }

            // Calculate and update the total price
            function updateTotalPrice() {
                const total = products.reduce((sum, product) => sum + product.total, 0);
                totalPriceElement.innerHTML = formatCurrency(total) || 0;
            }

            // You can either call your server-side method via AJAX or handle it entirely in JS
            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(amount);
            }

        });
    </script>
@endif
{{-- SCRIPT ONLY FOR ADMIN DASHBOARD --}}
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
            var errorModal = new bootstrap.Modal(errorModalElement, {
                backdrop: "static",
                keyboard: false
            });

            setTimeout(() => {
                errorModal.show();
                document.body.insertAdjacentHTML("beforeend",
                    '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
            }, 100);

            setTimeout(() => {
                errorModal.hide();
                document.querySelector(".modal-backdrop-custom")?.remove();
            }, 1800);
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

            setTimeout(() => {
                successModal.show();
                document.body.insertAdjacentHTML("beforeend",
                    '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
            }, 100);

            setTimeout(() => {
                successModal.hide();
                document.querySelector(".modal-backdrop-custom")?.remove();
            }, 2000);
        });
    </script>
@endif
{{-- DELETE MODAL --}}
<script>
    function setDeleteFormAction(action) {
        document.getElementById('deleteForm').setAttribute('action', action);
    }
</script>
