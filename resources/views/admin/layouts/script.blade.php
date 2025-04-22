<script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}" defer></script>
<script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}" defer></script>
<script src="{{ asset('tabler/dist/js/demo-theme.min.js?1692870487') }}"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
{{-- SCRIPT FOR ADMIN POS  --}}
@if (request()->is('admin/pos'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productList = document.getElementById('productList');
            const subtotalElement = document.getElementById('subtotal');
            const orderDiscountTotalElement = document.getElementById('orderDiscountTotal');
            const finalTotalElement = document.getElementById('finalTotal');
            const productGrid = document.getElementById('productGrid');
            const productsField = document.getElementById('productsField');
            const discountTotalValue = document.getElementById('discountTotalValue');
            const discountTotalType = document.getElementById('discountTotalType');
            const applyTotalDiscount = document.getElementById('applyTotalDiscount');
            const orderDiscountInput = document.getElementById('orderDiscountInput');
            const orderDiscountTypeInput = document.getElementById('orderDiscountTypeInput');
            const clearCartBtn = document.getElementById('clearCart');

            let products = JSON.parse(localStorage.getItem('cachedProducts')) || [];
            let subtotal = 0;
            let orderDiscount = 0;
            let grandTotal = 0;

            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(amount);
            }

            function saveProductsToCache() {
                localStorage.setItem('cachedProducts', JSON.stringify(products));
            }

            function calculateTotals() {
                // Calculate subtotal
                subtotal = products.reduce((sum, product) => sum + product.total, 0);

                // Calculate order discount
                if (discountTotalType.value === 'percentage') {
                    orderDiscount = (subtotal * parseFloat(discountTotalValue.value || 0)) / 100;
                } else {
                    orderDiscount = parseFloat(discountTotalValue.value || 0);
                }

                // Calculate grand total
                grandTotal = subtotal - orderDiscount;

                // Update display
                subtotalElement.innerText = formatCurrency(subtotal);
                orderDiscountTotalElement.innerText = formatCurrency(orderDiscount);
                finalTotalElement.innerText = formatCurrency(grandTotal);

                // Update hidden inputs
                orderDiscountInput.value = orderDiscount;
                orderDiscountTypeInput.value = discountTotalType.value;
            }

            function renderList() {
                productList.innerHTML = '';

                if (products.length === 0) {
                    const emptyMessage = document.createElement('div');
                    emptyMessage.classList.add('text-center', 'py-4', 'text-muted');
                    emptyMessage.innerHTML =
                        '<i class="ti ti-shopping-cart text-muted" style="font-size: 3rem;"></i><p class="mt-2">Your cart is empty</p>';
                    productList.appendChild(emptyMessage);
                } else {
                    products.forEach((product, index) => {
                        const item = document.createElement('div');
                        item.classList.add('list-group-item', 'd-flex', 'justify-content-between',
                            'align-items-center');
                        item.innerHTML = `
                            <div>
                                <strong>${product.name}</strong><br>
                                ${product.quantity} x ${formatCurrency(product.price)} / ${product.unit}
                            </div>
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <strong>${formatCurrency(product.total)}</strong>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-success increase-product" data-index="${index}">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning decrease-product" data-index="${index}">
                                        <i class="ti ti-minus"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger remove-product" data-index="${index}">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        productList.appendChild(item);
                    });
                }

                calculateTotals();
                productsField.value = JSON.stringify(products);
                saveProductsToCache();
            }

            function addToProductList(productId, productName, productPrice, productUnit) {
                let existingProduct = products.find(p => p.id === productId);

                if (existingProduct) {
                    existingProduct.quantity += 1;
                    existingProduct.total = existingProduct.quantity * existingProduct.price;
                } else {
                    products.push({
                        id: productId,
                        name: productName,
                        price: parseFloat(productPrice),
                        quantity: 1,
                        total: parseFloat(productPrice),
                        unit: productUnit
                    });
                }

                renderList();
            }

            function clearCart() {
                products = [];
                renderList();
            }

            // Product grid click handler
            productGrid.addEventListener('click', function(event) {
                const target = event.target.closest('.product-image');
                if (!target) return;

                const productId = target.dataset.productId;
                const productName = target.dataset.productName;
                const productPrice = target.dataset.productPrice;
                const productUnit = target.dataset.productUnit;

                addToProductList(productId, productName, productPrice, productUnit);
            });

            // Clear cart button
            clearCartBtn.addEventListener('click', function() {
                clearCart();
            });

            // Product list action handlers
            productList.addEventListener('click', function(event) {
                const removeBtn = event.target.closest('.remove-product');
                const increaseBtn = event.target.closest('.increase-product');
                const decreaseBtn = event.target.closest('.decrease-product');

                if (removeBtn) {
                    const index = removeBtn.dataset.index;
                    if (index !== undefined) {
                        products.splice(index, 1);
                        renderList();
                    }
                } else if (decreaseBtn) {
                    const index = decreaseBtn.dataset.index;
                    if (index !== undefined) {
                        if (products[index].quantity > 1) {
                            products[index].quantity -= 1;
                            products[index].total = products[index].quantity * products[index].price;
                        } else {
                            products.splice(index, 1); // Remove product if quantity reaches 0
                        }
                        renderList();
                    }
                } else if (increaseBtn) {
                    const index = increaseBtn.dataset.index;
                    if (index !== undefined) {
                        products[index].quantity += 1;
                        products[index].total = products[index].quantity * products[index].price;
                        renderList();
                    }
                }
            });

            // Apply order discount
            applyTotalDiscount.addEventListener('click', function() {
                calculateTotals();
            });

            // Search functionality
            const searchInput = document.getElementById('searchProduct');
            const productCards = document.querySelectorAll('#productGrid .col-md-4');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchText = this.value.toLowerCase().trim();

                    productCards.forEach(card => {
                        const productNameElement = card.querySelector('.card-title');
                        if (productNameElement) {
                            const productName = productNameElement.textContent.toLowerCase().trim();
                            card.style.display = productName.includes(searchText) ? '' : 'none';
                        }
                    });
                });
            }

            // Initialize
            renderList();
        });
    </script>
@endif
@if (request()->is('admin/login', 'admin/register', 'forgot-password'))
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('tabler/dist/js/demo-theme.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Toggle password visibility
            if (document.getElementById("toggle-password")) {
                const passwordField = document.getElementById("password");
                const togglePassword = document.getElementById("toggle-password");
                const toggleIcon = togglePassword.querySelector("i");

                togglePassword.addEventListener("click", function(e) {
                    e.preventDefault();
                    if (passwordField.type === "password") {
                        passwordField.type = "text";
                        toggleIcon.classList.remove("ti-eye");
                        toggleIcon.classList.add("ti-eye-off");
                    } else {
                        passwordField.type = "password";
                        toggleIcon.classList.remove("ti-eye-off");
                        toggleIcon.classList.add("ti-eye");
                    }
                });
            }

            // Show content after loading
            setTimeout(function() {
                const loadingContainer = document.getElementById("loading-container");
                const authContent = document.getElementById("auth-content");

                if (loadingContainer) loadingContainer.style.display = "none";
                if (authContent) authContent.style.display = "block";
            }, 800);
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
{{-- SCRIPT FOR ADMIN SALES CREATE & EDIT --}}
@if (request()->is('admin/sales/create', 'admin/sales/edit/*'))
    <script src="{{ asset('js/admin/sales-order.js') }}"></script>
@endif
{{-- SCRIPT FOR ADMIN PO CREATE & EDIT --}}
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

                // Auto-hide after 2 seconds
                setTimeout(() => hideModal(errorModal), 2000);

                // Add close handlers for buttons with data-bs-dismiss attribute
                const closeButtons = errorModalElement.querySelectorAll('[data-bs-dismiss="modal"]');
                closeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        hideModal(errorModal);
                    });
                });

                // Add close handler for Enter key
                errorModalElement.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        hideModal(errorModal);
                    }
                });

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

                // Auto-hide after 2 seconds
                setTimeout(() => hideModal(successModal), 2000);

                // Add close handlers for buttons with data-bs-dismiss attribute
                const closeButtons = successModalElement.querySelectorAll('[data-bs-dismiss="modal"]');
                closeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        hideModal(successModal);
                    });
                });

                // Add close handler for Enter key
                successModalElement.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        hideModal(successModal);
                    }
                });

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
