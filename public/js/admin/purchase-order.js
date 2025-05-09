/**
 * PurchaseOrderModule - Core module for purchase order functionality
 * Provides shared utility functions and calculations for all PO related pages
 */
class PurchaseOrderModule {
    constructor(config = {}) {
        this.config = {
            currency: "IDR",
            locale: "id-ID",
            ...config,
        };
    }

    /**
     * Format currency amount according to locale settings
     * @param {number} amount - Amount to format
     * @returns {string} Formatted currency string
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat(this.config.locale, {
            style: "currency",
            currency: this.config.currency,
            maximumFractionDigits: 0,
        }).format(amount);
    }

    /**
     * Calculate total price for a product
     * @param {number} price - Unit price
     * @param {number} quantity - Quantity
     * @param {number} discount - Discount amount/percentage
     * @param {string} discountType - 'fixed' or 'percentage'
     * @returns {number} Total price after discount
     */
    calculateTotal(price, quantity, discount, discountType) {
        // IMPORTANT: This is the central calculation logic that should match in PHP
        const discountPerUnit =
            discountType === "percentage" ? (price * discount) / 100 : discount;
        return (price - discountPerUnit) * quantity;
    }

    /**
     * Calculate discount amount based on value and type
     * @param {number} subtotal - Subtotal amount
     * @param {number} discountValue - Discount value
     * @param {string} discountType - 'fixed' or 'percentage'
     * @returns {number} Calculated discount amount
     */
    calculateDiscount(subtotal, discountValue, discountType) {
        return discountType === "percentage"
            ? (subtotal * discountValue) / 100
            : discountValue;
    }
}

/**
 * PurchaseOrderCreate - Manages the purchase order creation functionality
 * Extends core module functionality for create page
 */
class PurchaseOrderCreate extends PurchaseOrderModule {
    constructor(config = {}) {
        super(config);

        // Store DOM elements
        this.elements = {
            orderDate: document.getElementById("order_date"),
            dueDate: document.getElementById("due_date"),
            supplierSelect: document.getElementById("supplier_id"),
            productSelect: document.getElementById("product_id"),
            lastPrice: document.getElementById("last_price"),
            quantity: document.getElementById("quantity"),
            newPrice: document.getElementById("new_price"),
            discount: document.getElementById("discount"),
            discountType: document.getElementById("discount_type"),
            addProductBtn: document.getElementById("addProduct"),
            clearProductsBtn: document.getElementById("clearProducts"),
            productTableBody: document.getElementById("productTableBody"),
            productsField: document.getElementById("productsField"),
            discountTotalValue: document.getElementById("discountTotalValue"),
            discountTotalType: document.getElementById("discountTotalType"),
            applyTotalDiscount: document.getElementById("applyTotalDiscount"),
            invoice: document.getElementById("invoice"),
            form: document.getElementById("invoiceForm"),
        };

        // Data storage
        this.products = [];
        this.orderDiscount = { value: 0, type: "fixed" };

        // Check if we need to clear storage after submission
        this.checkSessionState();

        // Initialize flatpickr for date fields
        this.initFlatpickr();

        // Initialize event listeners
        this.initEventListeners();

        // Load data from storage
        this.loadFromStorage();

        // Initial render
        this.renderTable();
    }

    initFlatpickr() {
        // Initialize flatpickr for order date with our preferred format
        if (this.elements.orderDate) {
            flatpickr(this.elements.orderDate, {
                dateFormat: "Y-m-d", // Database format
                altInput: true,
                altFormat: "d-m-Y", // Fancy alternate format
                defaultDate: new Date(), // Auto-fill with now
                allowInput: true, // Allow typing manually
            });
        }

        // Initialize flatpickr for due date with the same format
        if (this.elements.dueDate) {
            flatpickr(this.elements.dueDate, {
                dateFormat: "Y-m-d", // Database format
                altInput: true,
                altFormat: "d-m-Y", // Fancy alternate format
                allowInput: true, // Allow typing manually
            });
        }
    }

    checkSessionState() {
        if (sessionStorage.getItem("poJustSubmitted") === "true") {
            localStorage.removeItem("poProducts");
            localStorage.removeItem("poOrderDiscount");
            sessionStorage.removeItem("poJustSubmitted");
        }
    }

    initEventListeners() {
        // Due date calculation - modified to work with flatpickr
        this.elements.supplierSelect.addEventListener("change", () =>
            this.calculateDueDate()
        );

        // Modified to work with flatpickr's change event
        if (this.elements.orderDate && this.elements.orderDate._flatpickr) {
            this.elements.orderDate._flatpickr.config.onChange.push(() =>
                this.calculateDueDate()
            );
        }

        // Product selection
        this.elements.productSelect.addEventListener("change", () =>
            this.updateProductPrice()
        );

        // Add product button
        this.elements.addProductBtn.addEventListener("click", () =>
            this.addProduct()
        );

        // Clear products
        this.elements.clearProductsBtn.addEventListener("click", () =>
            this.clearProducts()
        );

        // Apply order discount
        this.elements.applyTotalDiscount.addEventListener("click", () =>
            this.applyOrderDiscount()
        );

        // Form submission
        this.elements.form.addEventListener("submit", (e) => {
            // Ensure all products are included in the form submission
            this.elements.productsField.value = JSON.stringify(this.products);
            sessionStorage.setItem("poJustSubmitted", "true");
        });
    }

    loadFromStorage() {
        // Load products from localStorage
        const savedProducts = localStorage.getItem("poProducts");
        if (savedProducts) {
            try {
                this.products = JSON.parse(savedProducts);
            } catch (e) {
                console.error("Error parsing saved products:", e);
                this.products = [];
            }
        }

        // Load order discount from localStorage
        const savedOrderDiscount = localStorage.getItem("poOrderDiscount");
        if (savedOrderDiscount) {
            try {
                this.orderDiscount = JSON.parse(savedOrderDiscount);
                this.elements.discountTotalValue.value =
                    this.orderDiscount.value;
                this.elements.discountTotalType.value = this.orderDiscount.type;
            } catch (e) {
                console.error("Error parsing saved order discount:", e);
                this.orderDiscount = { value: 0, type: "fixed" };
            }
        }
    }

    saveToStorage() {
        localStorage.setItem("poProducts", JSON.stringify(this.products));
        localStorage.setItem(
            "poOrderDiscount",
            JSON.stringify(this.orderDiscount)
        );
    }

    calculateDueDate() {
        // Get date from flatpickr instance if available
        let orderDateValue;
        if (this.elements.orderDate._flatpickr) {
            const selectedDates =
                this.elements.orderDate._flatpickr.selectedDates;
            orderDateValue = selectedDates.length > 0 ? selectedDates[0] : null;
        } else {
            orderDateValue = this.elements.orderDate.value;
        }

        const selectedOption =
            this.elements.supplierSelect.options[
                this.elements.supplierSelect.selectedIndex
            ];

        if (!orderDateValue || !selectedOption) return;

        const orderDate = new Date(orderDateValue);
        const paymentTerms = selectedOption.dataset.paymentTerms;

        if (paymentTerms) {
            orderDate.setDate(orderDate.getDate() + parseInt(paymentTerms));

            // Update flatpickr instance if available
            if (this.elements.dueDate._flatpickr) {
                this.elements.dueDate._flatpickr.setDate(orderDate);
            } else {
                this.elements.dueDate.value = orderDate
                    .toISOString()
                    .split("T")[0];
            }
        }
    }

    updateProductPrice() {
        const selectedOption =
            this.elements.productSelect.options[
                this.elements.productSelect.selectedIndex
            ];

        if (selectedOption && selectedOption.getAttribute("data-price")) {
            this.elements.lastPrice.value =
                selectedOption.getAttribute("data-price") || "";

            // Auto-fill the new price with the last price
            this.elements.newPrice.value = this.elements.lastPrice.value;
        }
    }

    updateTotalPrice() {
        // Calculate subtotal from all products
        let subtotal = this.products.reduce(
            (sum, product) => sum + product.total,
            0
        );

        // Calculate order discount
        const orderDiscountAmount = this.calculateDiscount(
            subtotal,
            this.orderDiscount.value,
            this.orderDiscount.type
        );

        // Calculate final total after discount
        const finalTotal = subtotal - orderDiscountAmount;

        // Update UI displays
        document.getElementById("subtotal").innerText =
            this.formatCurrency(subtotal);
        document.getElementById("orderDiscountTotal").innerText =
            this.formatCurrency(orderDiscountAmount);
        document.getElementById("finalTotal").innerText =
            this.formatCurrency(finalTotal);

        // Update hidden form field for total discount
        document.getElementById("totalDiscountInput").value =
            orderDiscountAmount;

        // Update JSON of products for form submission
        this.elements.productsField.value = JSON.stringify(this.products);
    }

    addProduct() {
        const productId = this.elements.productSelect.value;
        const productName =
            this.elements.productSelect.options[
                this.elements.productSelect.selectedIndex
            ].text;
        const quantity = parseInt(this.elements.quantity.value);
        const price = parseFloat(this.elements.newPrice.value);
        const discount = parseFloat(this.elements.discount.value) || 0;
        const discountType = this.elements.discountType.value;

        // Generate unique ID and calculate total
        const uniqueId = `${Date.now()}-${Math.random()
            .toString(36)
            .substring(2, 7)}`;
        const total = this.calculateTotal(
            price,
            quantity,
            discount,
            discountType
        );

        // Add to products array
        this.products.push({
            id: productId,
            uniqueId,
            name: productName,
            quantity,
            price,
            discount,
            discountType,
            total,
        });

        // Save and update UI
        this.saveToStorage();
        this.renderTable();
        this.clearProductForm();
    }

    clearProductForm() {
        this.elements.productSelect.value = "";
        this.elements.quantity.value = "";
        this.elements.newPrice.value = "";
        this.elements.discount.value = "";
        this.elements.lastPrice.value = "";
    }

    clearProducts() {
        // Clear all products
        this.products = [];
        this.orderDiscount = { value: 0, type: "fixed" };

        // Clear localStorage
        localStorage.removeItem("poProducts");
        localStorage.removeItem("poOrderDiscount");

        // Reset discount UI
        this.elements.discountTotalValue.value = 0;
        this.elements.discountTotalType.value = "fixed";

        // Update UI
        this.renderTable();
    }

    applyOrderDiscount() {
        // Get discount values from UI
        this.orderDiscount = {
            value: parseFloat(this.elements.discountTotalValue.value) || 0,
            type: this.elements.discountTotalType.value,
        };

        // Save to storage and update totals
        this.saveToStorage();
        this.updateTotalPrice();
    }

    attachTableEventListeners() {
        // Event delegation for table rows
        this.elements.productTableBody.addEventListener("input", (event) => {
            const target = event.target;
            const uniqueId = target.dataset.uniqueId;
            if (!uniqueId) return;

            const product = this.products.find((p) => p.uniqueId === uniqueId);
            if (!product) return;

            if (target.classList.contains("quantity-input")) {
                product.quantity = parseInt(target.value) || 1;
            } else if (target.classList.contains("price-input")) {
                product.price = parseFloat(target.value) || 0;
            } else if (target.classList.contains("discount-input")) {
                product.discount = parseFloat(target.value) || 0;
            }

            // Recalculate the product total
            product.total = this.calculateTotal(
                product.price,
                product.quantity,
                product.discount,
                product.discountType
            );

            // Update the row's total display
            target.closest("tr").querySelector(".product-total").innerText =
                this.formatCurrency(product.total);

            // Save and update totals
            this.saveToStorage();
            this.updateTotalPrice();
        });

        // Handle discount type changes
        this.elements.productTableBody.addEventListener("change", (event) => {
            const target = event.target;
            if (!target.classList.contains("discount-type")) return;

            const uniqueId = target.dataset.uniqueId;
            const product = this.products.find((p) => p.uniqueId === uniqueId);
            if (!product) return;

            product.discountType = target.value;

            // Recalculate the product total
            product.total = this.calculateTotal(
                product.price,
                product.quantity,
                product.discount,
                product.discountType
            );

            // Update the row's total display
            target.closest("tr").querySelector(".product-total").innerText =
                this.formatCurrency(product.total);

            // Save and update totals
            this.saveToStorage();
            this.updateTotalPrice();
        });

        // Handle remove buttons
        this.elements.productTableBody.addEventListener("click", (event) => {
            const target = event.target.closest(".removeProduct");
            if (!target) return;

            const uniqueId = target.dataset.uniqueId;

            // Remove the product from the array
            this.products = this.products.filter(
                (p) => p.uniqueId !== uniqueId
            );

            // Save and update UI
            this.saveToStorage();
            this.renderTable();
        });
    }

    renderTable() {
        // Clear table body
        this.elements.productTableBody.innerHTML = "";

        // Add rows for each product
        this.products.forEach((product, index) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${product.name}</td>
                <td>
                    <input type="number" class="form-control quantity-input"
                        value="${product.quantity}" data-unique-id="${
                product.uniqueId
            }" min="1" style="width:80px;" />
                </td>
                <td>
                    <input type="number" class="form-control price-input"
                        value="${product.price}" data-unique-id="${
                product.uniqueId
            }" min="0" style="width:100px;" />
                </td>
                <td>
                    <div class="input-group" style="width:200px;">
                        <input type="number" class="form-control discount-input"
                            value="${product.discount}" data-unique-id="${
                product.uniqueId
            }" min="0" />
                        <select class="form-select discount-type" data-unique-id="${
                            product.uniqueId
                        }">
                            <option value="fixed" ${
                                product.discountType === "fixed"
                                    ? "selected"
                                    : ""
                            }>Rp</option>
                            <option value="percentage" ${
                                product.discountType === "percentage"
                                    ? "selected"
                                    : ""
                            }>%</option>
                        </select>
                    </div>
                </td>
                <td class="product-total">${this.formatCurrency(
                    product.total
                )}</td>
                <td style="text-align:center">
                    <button type="button" class="btn btn-danger btn-icon removeProduct" data-unique-id="${
                        product.uniqueId
                    }" title="Remove">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            `;
            this.elements.productTableBody.appendChild(row);
        });

        // Update totals
        this.updateTotalPrice();

        // Attach event listeners to the table rows
        this.attachTableEventListeners();
    }
}

/**
 * PurchaseOrderEdit - Manages the purchase order edit functionality
 * Extends core module functionality for edit page
 */
class PurchaseOrderEdit extends PurchaseOrderModule {
    constructor(config = {}) {
        super(config);

        // Store main DOM elements
        this.elements = {
            discountTotalValue: document.getElementById("discountTotalValue"),
            discountTotalType: document.getElementById("discountTotalType"),
            subtotalElement: document.getElementById("subtotal"),
            orderDiscountTotalElement:
                document.getElementById("orderDiscountTotal"),
            finalTotalElement: document.getElementById("finalTotal"),
            totalDiscountInput: document.getElementById("totalDiscountInput"),
            // All quantity, price and discount inputs (will be selected in init)
            quantityInputs: [],
            priceInputs: [],
            discountInputs: [],
            discountTypeInputs: [],
            amountInputs: [],
        };

        // Initialize
        this.initElementSelections();
        this.initEventListeners();

        // Initial calculations
        this.calculateAllAmounts();
    }

    initElementSelections() {
        // Select all item rows and their inputs for calculation
        this.elements.quantityInputs =
            document.querySelectorAll(".quantity-input");
        this.elements.priceInputs = document.querySelectorAll(".price-input");
        this.elements.discountInputs =
            document.querySelectorAll(".discount-input");
        this.elements.discountTypeInputs = document.querySelectorAll(
            ".discount-type-input"
        );
        this.elements.amountInputs = document.querySelectorAll(".amount-input");
    }

    initEventListeners() {
        // Add change/input listeners to all item inputs
        this.elements.quantityInputs.forEach((input) => {
            input.addEventListener("input", () =>
                this.updateItemAmount(input.dataset.itemId)
            );
        });

        this.elements.priceInputs.forEach((input) => {
            input.addEventListener("input", () =>
                this.updateItemAmount(input.dataset.itemId)
            );
        });

        this.elements.discountInputs.forEach((input) => {
            input.addEventListener("input", () =>
                this.updateItemAmount(input.dataset.itemId)
            );
        });

        this.elements.discountTypeInputs.forEach((select) => {
            select.addEventListener("change", () =>
                this.updateItemAmount(select.dataset.itemId)
            );
        });

        // Add listener for order-level discount
        this.elements.discountTotalValue.addEventListener("input", () =>
            this.calculateOrderTotal()
        );
        this.elements.discountTotalType.addEventListener("change", () =>
            this.calculateOrderTotal()
        );
    }

    updateItemAmount(itemId) {
        // Get the related inputs for this item
        const quantityInput = document.querySelector(
            `.quantity-input[data-item-id="${itemId}"]`
        );
        const priceInput = document.querySelector(
            `.price-input[data-item-id="${itemId}"]`
        );
        const discountInput = document.querySelector(
            `.discount-input[data-item-id="${itemId}"]`
        );
        const discountTypeInput = document.querySelector(
            `.discount-type-input[data-item-id="${itemId}"]`
        );
        const amountInput = document.querySelector(
            `.amount-input[data-item-id="${itemId}"]`
        );

        if (
            !quantityInput ||
            !priceInput ||
            !discountInput ||
            !discountTypeInput ||
            !amountInput
        ) {
            console.error(`Missing input element for item ${itemId}`);
            return;
        }

        // Get values
        const quantity = parseInt(quantityInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const discountType = discountTypeInput.value; // 'percentage' or 'fixed'

        // Calculate total for this item
        const total = this.calculateTotal(
            price,
            quantity,
            discount,
            discountType
        );

        // Update amount input
        amountInput.value = Math.round(total);

        // Recalculate all totals
        this.calculateOrderTotal();
    }

    calculateAllAmounts() {
        // Calculate amount for each item
        this.elements.quantityInputs.forEach((input) => {
            const itemId = input.dataset.itemId;
            this.updateItemAmount(itemId);
        });

        // Calculate order total
        this.calculateOrderTotal();
    }

    calculateOrderTotal() {
        // Calculate subtotal from all amount inputs
        let subtotal = 0;
        this.elements.amountInputs.forEach((input) => {
            subtotal += parseFloat(input.value) || 0;
        });

        // Get order discount values
        const discountValue =
            parseFloat(this.elements.discountTotalValue.value) || 0;
        const discountType = this.elements.discountTotalType.value;

        // Calculate order discount
        const orderDiscountAmount = this.calculateDiscount(
            subtotal,
            discountValue,
            discountType
        );

        // Calculate final total
        const finalTotal = subtotal - orderDiscountAmount;

        // Update UI
        this.elements.subtotalElement.textContent =
            this.formatCurrency(subtotal);
        this.elements.orderDiscountTotalElement.textContent =
            this.formatCurrency(orderDiscountAmount);
        this.elements.finalTotalElement.textContent =
            this.formatCurrency(finalTotal);

        // Update hidden form field for total discount
        this.elements.totalDiscountInput.value = orderDiscountAmount;
    }
}

/**
 * PurchaseOrderView - Handles the view and modal functionality for purchase orders
 * Provides read-only display with proper formatting and modal interactions
 */
class PurchaseOrderView extends PurchaseOrderModule {
    constructor(config = {}) {
        super(config);

        // Initialize elements for the View/Modal functionality
        this.elements = {
            deleteForm: document.getElementById("deleteForm"),
            viewPoModalContent: document.getElementById("viewPoModalContent"),
            poModalEdit: document.getElementById("poModalEdit"),
            poModalFullView: document.getElementById("poModalFullView"),
            poModalPrint: document.getElementById("poModalPrint"),
        };

        // Format all currency values on page load
        this.formatAllCurrencyValues();

        // Initialize modal functionality if we're on a page with modals
        if (this.elements.poModalPrint) {
            this.initModalListeners();
        }
    }

    /**
     * Format all currency display elements
     */
    formatAllCurrencyValues() {
        // Find all elements with currency class and format them
        const currencyElements = document.querySelectorAll(".currency-value");
        currencyElements.forEach((element) => {
            const value = parseFloat(element.dataset.value) || 0;
            element.textContent = this.formatCurrency(value);
        });
    }

    /**
     * Initialize event listeners for modal functionality
     */
    initModalListeners() {
        // Attach print button listener
        if (this.elements.poModalPrint) {
            this.elements.poModalPrint.addEventListener("click", () =>
                this.printModalContent()
            );
        }
    }

    /**
     * Set the action URL for the delete form
     * @param {string} url - The URL to submit the delete form to
     */
    setDeleteFormAction(url) {
        if (this.elements.deleteForm) {
            this.elements.deleteForm.action = url;
        }
    }

    /**
     * Load purchase order details into the modal via AJAX
     * @param {number|string} id - The ID of the purchase order to load
     */
    loadPoDetails(id) {
        if (!this.elements.viewPoModalContent) {
            console.error("Modal content element not found");
            return;
        }

        // Set the edit button URL dynamically
        if (this.elements.poModalEdit) {
            this.elements.poModalEdit.href = `/admin/po/edit/${id}`;
        }

        // Set the full view button URL dynamically
        if (this.elements.poModalFullView) {
            this.elements.poModalFullView.href = `/admin/po/view/${id}`;
        }

        // Show loading spinner
        this.elements.viewPoModalContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading purchase order details...</p>
            </div>
        `;

        // Fetch PO details via AJAX
        fetch(`/admin/po/modal-view/${id}`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.text();
            })
            .then((html) => {
                this.elements.viewPoModalContent.innerHTML = html;
                // Format any currency values in the loaded content
                this.formatAllCurrencyValues();
            })
            .catch((error) => {
                this.elements.viewPoModalContent.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="ti ti-alert-circle me-2"></i> Error loading PO details: ${error.message}
                    </div>
                `;
            });
    }

    /**
     * Print the content of the modal
     */
    printModalContent() {
        const printContent = this.elements.viewPoModalContent.innerHTML;
        const originalContent = document.body.innerHTML;

        document.body.innerHTML = `
            <div class="container print-container">
                <div class="card">
                    <div class="card-body">
                        ${printContent}
                    </div>
                </div>
            </div>
        `;

        window.print();
        document.body.innerHTML = originalContent;

        // Reattach event listeners after restoring original content
        setTimeout(() => {
            // This is a hack to reload the page after printing
            window.location.reload();
        }, 100);
    }
}

/**
 * Initialize appropriate module based on the current page
 */
document.addEventListener("DOMContentLoaded", function () {
    // Determine current page
    const pathname = window.location.pathname;

    try {
        if (pathname.includes("/admin/po/create")) {
            // Initialize create page functionality
            window.poApp = new PurchaseOrderCreate();
            console.log("Purchase Order Create App initialized");
        } else if (
            pathname.includes("/admin/po/edit") ||
            (pathname.includes("/admin/po") && pathname.match(/\/\d+\/edit$/))
        ) {
            // Initialize edit page functionality
            window.poApp = new PurchaseOrderEdit();
            console.log("Purchase Order Edit App initialized");
        } else if (
            pathname.includes("/admin/po/modal") ||
            (pathname.includes("/admin/po") && pathname.match(/\/\d+$/)) ||
            pathname.includes("/admin/po/show")
        ) {
            // For view page, we don't need interactive functionality
            console.log("Purchase Order View page detected");
            // Initialize view functionality for modal or show pages
            window.poApp = new PurchaseOrderView();
            console.log("Purchase Order View App initialized");
        }

        // Expose global utility functions that might be called from inline handlers
        window.setDeleteFormAction = function (url) {
            if (window.poApp && window.poApp.setDeleteFormAction) {
                window.poApp.setDeleteFormAction(url);
            }
        };

        window.loadPoDetails = function (id) {
            if (window.poApp && window.poApp.loadPoDetails) {
                window.poApp.loadPoDetails(id);
            }
        };
    } catch (error) {
        console.error("Error initializing Purchase Order App:", error);
    }
});
