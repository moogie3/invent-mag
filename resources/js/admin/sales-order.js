/**
 * SalesOrderModule - Core module for sales order functionality
 * Provides shared utility functions and calculations for all SO related pages
 */
class SalesOrderModule {
    constructor(config = {}) {
        this.config = {
            currency: "IDR",
            locale: "id-ID",
            ...config,
        };

        // Get tax rate from hidden input
        this.taxRate =
            parseFloat(document.getElementById("taxRateInput")?.value) || 0;
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
     * Calculate discount amount based on price, quantity, discount value and type
     * @param {number} price - Unit price
     * @param {number} quantity - Quantity
     * @param {number} discount - Discount value
     * @param {string} discountType - 'fixed' or 'percentage'
     * @returns {number} Total discount amount
     */
    calculateDiscountAmount(price, quantity, discount, discountType) {
        if (discountType === "percentage") {
            return ((price * discount) / 100) * quantity;
        }
        return discount * quantity; // Fixed amount
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
        const discountAmount = this.calculateDiscountAmount(
            price,
            quantity,
            discount,
            discountType
        );
        return price * quantity - discountAmount;
    }

    /**
     * Calculate order discount
     * @param {number} subtotal - Subtotal amount
     * @param {number} discount - Discount value
     * @param {string} discountType - 'fixed' or 'percentage'
     * @returns {number} Calculated order discount
     */
    calculateOrderDiscount(subtotal, discount, discountType) {
        if (discountType === "percentage") {
            return (subtotal * discount) / 100;
        }
        return discount;
    }

    /**
     * Initialize flatpickr for date fields
     * @param {HTMLElement} orderDateElement - Order date input element
     * @param {HTMLElement} dueDateElement - Due date input element
     */
    initFlatpickr(orderDateElement, dueDateElement) {
        // Initialize flatpickr for order date with our preferred format
        if (orderDateElement) {
            flatpickr(orderDateElement, {
                dateFormat: "Y-m-d", // Database format
                altInput: true,
                altFormat: "d-m-Y", // Fancy alternate format
                defaultDate: new Date(), // Auto-fill with now
                allowInput: true, // Allow typing manually
            });
        }

        // Initialize flatpickr for due date with the same format
        if (dueDateElement) {
            flatpickr(dueDateElement, {
                dateFormat: "Y-m-d", // Database format
                altInput: true,
                altFormat: "d-m-Y", // Fancy alternate format
                allowInput: true, // Allow typing manually
            });
        }
    }

    safeGetElement(id) {
        const element = document.getElementById(id);
        if (!element) {
            console.warn(`Element with ID '${id}' not found`);
        }
        return element;
    }
}

/**
 * SalesOrderCreate - Manages the sales order creation functionality
 * Extends core module functionality for create page
 */
class SalesOrderCreate extends SalesOrderModule {
    constructor(config = {}) {
        super(config);

        // Store DOM elements with safe getter
        this.elements = this.initializeElements();

        // In-memory data storage (replaces localStorage)
        this.products = [];
        this.orderDiscount = 0;
        this.orderDiscountType = "fixed";
        this.sessionJustSubmitted = false;
        this.currentStock = 0; // Track current selected product stock

        // Check if we need to clear storage after submission
        this.checkSessionState();

        // Initialize flatpickr for date fields
        this.initFlatpickr(this.elements.orderDate, this.elements.dueDate);

        // Initialize event listeners
        this.initEventListeners();

        // Initial render
        this.renderTable();
    }

    initializeElements() {
        const elementIds = [
            "order_date",
            "due_date",
            "customer_id",
            "product_id",
            "customer_price",
            "past_price",
            "price",
            "selling_price",
            "quantity",
            "discount",
            "discount_type",
            "addProduct",
            "clearProducts",
            "productTableBody",
            "productsField",
            "discountTotalValue",
            "discountTotalType",
            "applyTotalDiscount",
            "stock_available",
            "quantity_warning",
        ];

        const elements = {};
        elementIds.forEach((id) => {
            elements[id] = this.safeGetElement(id);
        });

        // Map some elements to match the existing code structure
        elements.orderDate = elements.order_date;
        elements.dueDate = elements.due_date;
        elements.customerSelect = elements.customer_id;
        elements.productSelect = elements.product_id;
        elements.customerPriceField = elements.customer_price;
        elements.pastPriceField = elements.past_price;
        elements.priceField = elements.price;
        elements.sellingPriceField = elements.selling_price;
        elements.addProductBtn = elements.addProduct;
        elements.clearProductsBtn = elements.clearProducts;
        elements.form = document.querySelector("form");

        return elements;
    }

    checkSessionState() {
        // Use in-memory flag instead of localStorage/sessionStorage
        if (this.sessionJustSubmitted) {
            this.products = [];
            this.orderDiscount = 0;
            this.orderDiscountType = "fixed";
            this.sessionJustSubmitted = false;
        }
    }

    initEventListeners() {
        // Due date calculation - modified to work with flatpickr
        this.elements.customerSelect.addEventListener("change", () =>
            this.calculateDueDate()
        );

        // Modified to work with flatpickr's change event
        if (this.elements.orderDate && this.elements.orderDate._flatpickr) {
            this.elements.orderDate._flatpickr.config.onChange.push(() =>
                this.calculateDueDate()
            );
        } else {
            // Fallback for non-flatpickr implementation
            this.elements.orderDate.addEventListener("change", () =>
                this.calculateDueDate()
            );
        }

        // Product selection events
        this.elements.productSelect.addEventListener("change", () => {
            this.updateProductPrices();
            this.updateStockDisplay();
        });
        this.elements.customerSelect.addEventListener("change", () =>
            this.fetchCustomerPastPrice()
        );

        // Quantity validation
        if (this.elements.quantity) {
            this.elements.quantity.addEventListener("input", () =>
                this.validateQuantity()
            );
            this.elements.quantity.addEventListener("change", () =>
                this.validateQuantity()
            );
        }

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
        this.elements.form.addEventListener("submit", (e) =>
            this.handleSubmit(e)
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
            this.elements.customerSelect.options[
                this.elements.customerSelect.selectedIndex
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

    updateProductPrices() {
        const selectedOption =
            this.elements.productSelect.options[
                this.elements.productSelect.selectedIndex
            ];

        if (!selectedOption || selectedOption.value === "") {
            this.elements.priceField.value = "";
            this.elements.sellingPriceField.value = "";
            this.elements.customerPriceField.value = "";
            return;
        }

        // Set prices from data attributes
        this.elements.priceField.value =
            selectedOption.getAttribute("data-price");
        this.elements.sellingPriceField.value =
            selectedOption.getAttribute("data-selling-price");

        // Also fetch the customer's past price
        this.fetchCustomerPastPrice();
    }

    updateStockDisplay() {
        if (!this.elements.productSelect || !this.elements.stock_available) {
            return;
        }

        const selectedOption =
            this.elements.productSelect.options[
                this.elements.productSelect.selectedIndex
            ];

        if (selectedOption && selectedOption.value) {
            const stock =
                parseInt(selectedOption.getAttribute("data-stock")) || 0;
            this.currentStock = stock;

            // Calculate remaining stock (available stock minus already ordered quantity)
            const orderedQuantity = this.getOrderedQuantityForProduct(
                selectedOption.value
            );
            const remainingStock = Math.max(0, stock - orderedQuantity);

            this.elements.stock_available.textContent = remainingStock;

            // Update stock display styling based on availability
            this.updateStockStyling(remainingStock);

            // Reset quantity field and warning
            if (this.elements.quantity) {
                this.elements.quantity.max = remainingStock;
                this.elements.quantity.value = "";
            }
            this.hideQuantityWarning();
        } else {
            this.elements.stock_available.textContent = "-";
            this.currentStock = 0;
            if (this.elements.quantity) {
                this.elements.quantity.removeAttribute("max");
            }
            this.hideQuantityWarning();
        }
    }

    getOrderedQuantityForProduct(productId) {
        return this.products
            .filter((product) => product.id === productId)
            .reduce((total, product) => total + product.quantity, 0);
    }

    updateStockStyling(remainingStock) {
        if (!this.elements.stock_available) return;

        // Remove existing classes
        this.elements.stock_available.classList.remove(
            "text-primary",
            "text-warning",
            "text-danger"
        );

        // Apply styling based on stock level
        if (remainingStock === 0) {
            this.elements.stock_available.classList.add("text-danger");
        } else if (remainingStock <= 5) {
            this.elements.stock_available.classList.add("text-warning");
        } else {
            this.elements.stock_available.classList.add("text-primary");
        }
    }

    validateQuantity() {
        if (!this.elements.quantity || !this.elements.productSelect) {
            return true;
        }

        const quantity = parseInt(this.elements.quantity.value) || 0;
        const selectedOption =
            this.elements.productSelect.options[
                this.elements.productSelect.selectedIndex
            ];

        if (!selectedOption || !selectedOption.value) {
            this.hideQuantityWarning();
            return true;
        }

        const productId = selectedOption.value;
        const stock = parseInt(selectedOption.getAttribute("data-stock")) || 0;
        const orderedQuantity = this.getOrderedQuantityForProduct(productId);
        const remainingStock = Math.max(0, stock - orderedQuantity);

        if (quantity > remainingStock) {
            this.showQuantityWarning();
            this.disableAddButton();
            return false;
        } else {
            this.hideQuantityWarning();
            this.enableAddButton();
            return true;
        }
    }

    showQuantityWarning() {
        if (this.elements.quantity_warning) {
            this.elements.quantity_warning.classList.remove("d-none");
        }
        if (this.elements.quantity) {
            this.elements.quantity.classList.add("is-invalid");
        }
    }

    hideQuantityWarning() {
        if (this.elements.quantity_warning) {
            this.elements.quantity_warning.classList.add("d-none");
        }
        if (this.elements.quantity) {
            this.elements.quantity.classList.remove("is-invalid");
        }
    }

    disableAddButton() {
        if (this.elements.addProductBtn) {
            this.elements.addProductBtn.disabled = true;
            this.elements.addProductBtn.classList.add("disabled");
        }
    }

    enableAddButton() {
        if (this.elements.addProductBtn) {
            this.elements.addProductBtn.disabled = false;
            this.elements.addProductBtn.classList.remove("disabled");
        }
    }

    fetchCustomerPastPrice() {
        const customerId = this.elements.customerSelect.value;
        const productId = this.elements.productSelect.value;

        if (!customerId || !productId) {
            this.elements.pastPriceField.value = "";
            return;
        }

        // Make AJAX request to get past price
        fetch(`/admin/sales/get-customer-price/${customerId}/${productId}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.past_price) {
                    this.elements.pastPriceField.value = data.past_price;
                    this.elements.customerPriceField.value = data.past_price;
                } else {
                    this.elements.pastPriceField.value = "0";
                }
            })
            .catch((error) => {
                console.error("Error fetching past price:", error);
                this.elements.pastPriceField.value = "0";
            });
    }

    updateTotalPrice() {
        let subtotal = 0;
        let totalBeforeDiscounts = 0;
        let itemDiscount = 0;

        this.products.forEach((product) => {
            // This is the raw subtotal before any discounts
            const productSubtotal =
                Number(product.price) * Number(product.quantity);
            totalBeforeDiscounts += productSubtotal;

            // This is after per-product discounts
            subtotal += product.total;

            const productDiscount = this.calculateDiscountAmount(
                product.price,
                product.quantity,
                product.discount,
                product.discountType
            );

            itemDiscount += productDiscount;
        });

        const orderDiscountAmount = this.calculateOrderDiscount(
            totalBeforeDiscounts,
            this.orderDiscount,
            this.orderDiscountType
        );
        const totalDiscount = itemDiscount + orderDiscountAmount;
        const taxableAmount = subtotal - orderDiscountAmount;
        const taxAmount = taxableAmount * (this.taxRate / 100);
        const finalTotal = taxableAmount + taxAmount;

        // Update UI
        document.getElementById("subtotal").innerText =
            this.formatCurrency(subtotal);
        document.getElementById("orderDiscountTotal").innerText =
            this.formatCurrency(orderDiscountAmount);
        document.getElementById("taxTotal").innerText =
            this.formatCurrency(taxAmount);
        document.getElementById("finalTotal").innerText =
            this.formatCurrency(finalTotal);

        // Update hidden inputs for form submission
        document.getElementById("totalDiscountInput").value = itemDiscount;
        document.getElementById("orderDiscountInput").value =
            this.orderDiscount;
        document.getElementById("orderDiscountTypeInput").value =
            this.orderDiscountType;
        document.getElementById("taxInput").value = taxAmount;
        this.elements.productsField.value = JSON.stringify(this.products);
    }

    addProduct() {
        if (
            !this.elements.productSelect ||
            !this.elements.quantity ||
            !this.elements.customerPriceField
        ) {
            console.warn("Required form elements not found for adding product");
            return;
        }

        // Validate quantity first
        if (!this.validateQuantity()) {
            return;
        }

        const productId = this.elements.productSelect.value;
        const productName =
            this.elements.productSelect.options[
                this.elements.productSelect.selectedIndex
            ].text;
        const quantity = parseInt(this.elements.quantity.value) || 0;
        const price = parseFloat(this.elements.customerPriceField.value) || 0;
        const discount = parseFloat(this.elements.discount?.value) || 0;
        const discountType = this.elements.discount_type?.value || "fixed";

        // Get stock for the product
        const selectedOption =
            this.elements.productSelect.options[
                this.elements.productSelect.selectedIndex
            ];
        const stock = parseInt(selectedOption.getAttribute("data-stock")) || 0;

        // Generate unique ID for better product tracking
        const uniqueId = `${Date.now()}-${Math.random()
            .toString(36)
            .substring(2, 7)}`;

        // Calculate the total using the correct discount type
        const total = this.calculateTotal(
            price,
            quantity,
            discount,
            discountType
        );

        this.products.push({
            id: productId,
            uniqueId,
            name: productName,
            quantity,
            price,
            discount,
            discountType,
            total,
            stock,
        });

        this.renderTable();
        this.clearProductForm();
        this.updateStockDisplay(); // Refresh stock display after adding
    }

    clearProductForm() {
        const formFields = [
            "productSelect",
            "quantity",
            "customerPriceField",
            "discount",
            "priceField",
            "sellingPriceField",
            "pastPriceField",
        ];

        formFields.forEach((fieldName) => {
            if (this.elements[fieldName]) {
                this.elements[fieldName].value = "";
            }
        });

        // Reset stock display
        if (this.elements.stock_available) {
            this.elements.stock_available.textContent = "-";
        }
        this.hideQuantityWarning();
        this.enableAddButton();
    }

    clearProducts() {
        this.products = [];
        this.orderDiscount = 0;
        this.orderDiscountType = "fixed";

        // Reset discount UI
        if (this.elements.discountTotalValue)
            this.elements.discountTotalValue.value = 0;
        if (this.elements.discountTotalType)
            this.elements.discountTotalType.value = "fixed";

        this.renderTable();
        this.updateStockDisplay(); // Refresh stock display after clearing
    }

    applyOrderDiscount() {
        this.orderDiscount =
            parseFloat(this.elements.discountTotalValue.value) || 0;
        this.orderDiscountType = this.elements.discountTotalType.value;
        this.updateTotalPrice();
    }

    handleSubmit(e) {
        // Prevent form submission if no products added
        if (this.products.length === 0) {
            e.preventDefault();
            alert("Please add at least one product before submitting.");
            return false;
        }

        // Set flag to clear data after submission (similar to PurchaseOrderCreate)
        this.sessionJustSubmitted = true;
    }

    renderTable() {
        this.elements.productTableBody.innerHTML = "";

        this.products.forEach((product, index) => {
            // Calculate remaining stock for this product
            const totalOrderedForProduct = this.products
                .filter((p) => p.id === product.id)
                .reduce((sum, p) => sum + p.quantity, 0);
            const remainingStock = Math.max(
                0,
                product.stock - totalOrderedForProduct + product.quantity
            );

            const row = document.createElement("tr");
            row.innerHTML = `
                <td class="text-center">${index + 1}</td>
                <td>${product.name}</td>
                <td class="text-center">
                    <span class="badge ${
                        remainingStock === 0
                            ? "bg-danger"
                            : remainingStock <= 5
                            ? "bg-warning"
                            : "bg-success"
                    }">
                        ${remainingStock}
                    </span>
                </td>
                <td class="text-center">
                    <input type="number" class="form-control quantity-input text-center"
                        value="${product.quantity}" data-unique-id="${
                product.uniqueId
            }"
                        min="1" max="${product.stock}" style="width:80px;" />
                </td>
                <td class="text-center">
                    <input type="number" class="form-control price-input text-center"
                        value="${product.price}" data-unique-id="${
                product.uniqueId
            }"
                        min="0" step="0.01" style="width:100px;" />
                </td>
                <td class="text-center">
                    <div class="input-group" style="width:200px;">
                        <input type="number" class="form-control discount-input text-center"
                            value="${product.discount}" data-unique-id="${
                product.uniqueId
            }"
                            min="0" step="0.01" />
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
                <td class="text-end product-total fw-bold">${this.formatCurrency(
                    product.total
                )}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm removeProduct"
                        data-unique-id="${
                            product.uniqueId
                        }" title="Remove Product">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            `;
            this.elements.productTableBody.appendChild(row);
        });

        this.updateTotalPrice();
        this.attachTableEventListeners();
    }

    attachTableEventListeners() {
        // Remove existing listeners to prevent duplicates
        const newTableBody = this.elements.productTableBody.cloneNode(true);
        this.elements.productTableBody.parentNode.replaceChild(
            newTableBody,
            this.elements.productTableBody
        );
        this.elements.productTableBody = newTableBody;

        // Event delegation for table interactions
        this.elements.productTableBody.addEventListener("input", (event) => {
            this.handleTableInput(event);
        });

        this.elements.productTableBody.addEventListener("change", (event) => {
            this.handleTableChange(event);
        });

        this.elements.productTableBody.addEventListener("click", (event) => {
            this.handleTableClick(event);
        });
    }

    handleTableInput(event) {
        const target = event.target;
        const uniqueId = target.dataset.uniqueId;
        if (!uniqueId) return;

        const product = this.products.find((p) => p.uniqueId === uniqueId);
        if (!product) return;

        if (target.classList.contains("quantity-input")) {
            const newQuantity = parseInt(target.value) || 1;

            // Validate quantity against stock for this specific product
            const totalOrderedForProduct = this.products
                .filter((p) => p.id === product.id && p.uniqueId !== uniqueId)
                .reduce((sum, p) => sum + p.quantity, 0);

            if (newQuantity + totalOrderedForProduct > product.stock) {
                target.classList.add("is-invalid");
                // Revert to previous valid value
                target.value = product.quantity;
                return;
            } else {
                target.classList.remove("is-invalid");
                product.quantity = newQuantity;
            }
        } else if (target.classList.contains("price-input")) {
            product.price = parseFloat(target.value) || 0;
        } else if (target.classList.contains("discount-input")) {
            product.discount = parseFloat(target.value) || 0;
        }

        this.updateProductInTable(product, target);
        this.updateStockDisplay(); // Refresh stock display after any change
    }

    handleTableChange(event) {
        const target = event.target;
        if (!target.classList.contains("discount-type")) return;

        const uniqueId = target.dataset.uniqueId;
        const product = this.products.find((p) => p.uniqueId === uniqueId);
        if (!product) return;

        product.discountType = target.value;
        this.updateProductInTable(product, target);
    }

    handleTableClick(event) {
        const target = event.target.closest(".removeProduct");
        if (!target) return;

        const uniqueId = target.dataset.uniqueId;
        this.products = this.products.filter((p) => p.uniqueId !== uniqueId);
        this.renderTable();
        this.updateStockDisplay(); // Refresh stock display after removal
    }

    updateProductInTable(product, targetElement) {
        // Recalculate the product total
        product.total = this.calculateTotal(
            product.price,
            product.quantity,
            product.discount,
            product.discountType
        );

        // Update the row's total display
        const totalElement = targetElement
            .closest("tr")
            .querySelector(".product-total");
        if (totalElement) {
            totalElement.innerText = this.formatCurrency(product.total);
        }

        this.updateTotalPrice();
    }
}

/**
 * SalesOrderEdit - Manages the sales order edit functionality
 * Extends core module for edit page
 */
class SalesOrderEdit extends SalesOrderModule {
    constructor(config = {}) {
        super(config);

        // Initialize DOM elements
        this.elements = {
            orderDate: document.getElementById("order_date"),
            dueDate: document.getElementById("due_date"),
            customerSelect: document.getElementById("customer_id"),
            discountTotalValue: document.getElementById("discountTotalValue"),
            discountTotalType: document.getElementById("discountTotalType"),
        };

        // Initialize flatpickr for date fields
        this.initFlatpickr(this.elements.orderDate, this.elements.dueDate);

        this.initEventListeners();
        this.calculateTotals();
    }

    initEventListeners() {
        // Due date calculation - modified to work with flatpickr
        if (this.elements.customerSelect) {
            this.elements.customerSelect.addEventListener("change", () =>
                this.calculateDueDate()
            );
        }

        // Modified to work with flatpickr's change event
        if (this.elements.orderDate && this.elements.orderDate._flatpickr) {
            this.elements.orderDate._flatpickr.config.onChange.push(() =>
                this.calculateDueDate()
            );
        } else if (this.elements.orderDate) {
            // Fallback for non-flatpickr implementation
            this.elements.orderDate.addEventListener("change", () =>
                this.calculateDueDate()
            );
        }

        // Input event listeners for calculations
        document.addEventListener("input", (event) => {
            if (
                event.target.matches(
                    ".quantity-input, .price-input, .discount-input, #discountTotalValue"
                )
            ) {
                this.calculateTotals();
            }
        });

        // Change event listeners for select inputs
        document.addEventListener("change", (event) => {
            if (
                event.target.matches(".discount-type-input, #discountTotalType")
            ) {
                this.calculateTotals();
            }
        });
    }

    calculateDueDate() {
        // Get date from flatpickr instance if available
        let orderDateValue;
        if (this.elements.orderDate && this.elements.orderDate._flatpickr) {
            const selectedDates =
                this.elements.orderDate._flatpickr.selectedDates;
            orderDateValue = selectedDates.length > 0 ? selectedDates[0] : null;
        } else if (this.elements.orderDate) {
            orderDateValue = this.elements.orderDate.value;
        }

        const selectedOption =
            this.elements.customerSelect.options[
                this.elements.customerSelect.selectedIndex
            ];

        if (!orderDateValue || !selectedOption) return;

        const orderDate = new Date(orderDateValue);
        const paymentTerms = parseInt(selectedOption.dataset.paymentTerms) || 0;

        if (paymentTerms > 0) {
            orderDate.setDate(orderDate.getDate() + paymentTerms);

            // Update flatpickr instance if available
            if (this.elements.dueDate && this.elements.dueDate._flatpickr) {
                this.elements.dueDate._flatpickr.setDate(orderDate);
            } else if (this.elements.dueDate) {
                this.elements.dueDate.value = orderDate
                    .toISOString()
                    .split("T")[0];
            }
        }
    }

    calculateTotals() {
        let subtotal = 0;
        let totalUnitDiscount = 0;

        // Calculate per-item amounts
        document.querySelectorAll("tbody tr").forEach((row) => {
            const itemId = row.querySelector(".quantity-input")?.dataset.itemId;
            if (!itemId) return;

            const quantity =
                parseFloat(
                    row.querySelector(
                        `.quantity-input[data-item-id='${itemId}']`
                    ).value
                ) || 0;
            const price =
                parseFloat(
                    row.querySelector(`.price-input[data-item-id='${itemId}']`)
                        .value
                ) || 0;
            const discountInput = row.querySelector(
                `.discount-input[data-item-id='${itemId}']`
            );
            const discountTypeSelect = row.querySelector(
                `.discount-type-input[data-item-id='${itemId}']`
            );

            const discountValue = parseFloat(discountInput?.value) || 0;
            const discountType = discountTypeSelect?.value || "percentage";

            // Calculate discount and net amount
            const discountAmount = this.calculateDiscountAmount(
                price,
                1,
                discountValue,
                discountType
            );
            const netUnitPrice =
                price -
                (discountType === "percentage"
                    ? (price * discountValue) / 100
                    : discountValue);
            const netAmount = netUnitPrice * quantity;

            // Update the amount field
            const amountInput = row.querySelector(
                `.amount-input[data-item-id='${itemId}']`
            );
            if (amountInput) {
                amountInput.value = Math.floor(netAmount);
            }

            // Add to running totals
            subtotal += netAmount;
            totalUnitDiscount += discountAmount * quantity;
        });

        // Calculate order discount
        const discountTotalValue =
            parseFloat(this.elements.discountTotalValue?.value) || 0;
        const discountTotalType =
            this.elements.discountTotalType?.value || "percentage";
        let orderDiscountAmount = 0;

        // Calculate order discount
        if (discountTotalType === "percentage") {
            orderDiscountAmount = subtotal * (discountTotalValue / 100);
        } else {
            orderDiscountAmount = discountTotalValue;
        }

        // Calculate final amounts
        const totalAfterAllDiscounts = subtotal - orderDiscountAmount;
        const taxAmount = totalAfterAllDiscounts * (this.taxRate / 100);
        const grandTotal = totalAfterAllDiscounts + taxAmount;

        // Update displays
        document.getElementById("subtotal").innerText =
            Math.floor(subtotal).toLocaleString("id-ID");
        document.getElementById("orderDiscountTotal").innerText =
            Math.floor(orderDiscountAmount).toLocaleString("id-ID");

        if (document.getElementById("totalTax")) {
            document.getElementById("totalTax").innerText =
                Math.floor(taxAmount).toLocaleString("id-ID");
        }

        document.getElementById("finalTotal").innerText =
            Math.floor(grandTotal).toLocaleString("id-ID");

        // Update hidden inputs
        document.getElementById("grandTotalInput").value =
            Math.floor(grandTotal);
        document.getElementById("totalDiscountInput").value = Math.floor(
            totalUnitDiscount + orderDiscountAmount
        );
        document.getElementById("taxInput").value = Math.floor(taxAmount);

        // Update total_tax input if it exists
        const totalTaxInput = document.getElementById("total_tax_input");
        if (totalTaxInput) {
            totalTaxInput.value = Math.floor(taxAmount);
        }
    }
}

/**
 * SalesOrderView - Manages the sales order view functionality and modals
 * Extends core module for view/modal pages
 */
class SalesOrderView extends SalesOrderModule {
    constructor(config = {}) {
        super(config);

        this.elements = {
            deleteForm: this.safeGetElement("deleteForm"),
            viewSalesModalContent: this.safeGetElement("viewSalesModalContent"),
            salesModalEdit: this.safeGetElement("salesModalEdit"),
            salesModalFullView: this.safeGetElement("salesModalFullView"),
            salesModalPrint: this.safeGetElement("salesModalPrint"),
        };

        this.formatAllCurrencyValues();
        this.initModalListeners();
        this.initGlobalFunctions();

        // Add event listener for view sales details buttons
        document.querySelectorAll('.view-sales-details-btn').forEach(button => {
            button.addEventListener('click', function() {
                const salesId = this.dataset.id;
                window.showSalesDetailsModal(salesId);
            });
        });
    }

    formatAllCurrencyValues() {
        const currencyElements = document.querySelectorAll(".currency-value");
        currencyElements.forEach((element) => {
            const value = parseFloat(element.dataset.value) || 0;
            element.textContent = this.formatCurrency(value);
        });
    }

    initModalListeners() {
        if (this.elements.salesModalPrint) {
            this.elements.salesModalPrint.addEventListener("click", () =>
                this.printModalContent()
            );
        }

        // Event listener for when the sales view modal is shown
        const viewSalesModalElement = document.getElementById('viewSalesModal');
        if (viewSalesModalElement) {
            viewSalesModalElement.addEventListener('shown.bs.modal', (event) => {
                const salesId = viewSalesModalElement.dataset.salesId;
                if (salesId) {
                    this.loadSalesDetails(salesId);
                }
            });
        }
    }

    // Global function to trigger the sales details modal
    // Ensure this is defined after DOMContentLoaded for global access
    // (Moved to the end of the file or within a DOMContentLoaded block if not already there)
    // For now, we'll define it as a method of the class and expose it via initGlobalFunctions
    showSalesDetailsModal(salesId) {
        const viewSalesModalElement = document.getElementById('viewSalesModal');
        if (viewSalesModalElement) {
            viewSalesModalElement.dataset.salesId = salesId; // Store the ID
            const salesViewModal = new bootstrap.Modal(viewSalesModalElement);
            salesViewModal.show();
        }
    }

    // Add this method to ensure global functions are available
    initGlobalFunctions() {
        window.setDeleteFormAction = (url) => this.setDeleteFormAction(url);
        window.showSalesDetailsModal = (salesId) => this.showSalesDetailsModal(salesId);
    }

    setDeleteFormAction(url) {
        console.log("Setting delete form action to:", url); // Debug log
        if (this.elements.deleteForm) {
            this.elements.deleteForm.action = url;
            console.log("Form action set successfully"); // Debug log
        } else {
            console.error("Delete form element not found");
        }
    }

    loadSalesDetails(id) {
        // Only load if content is not already present or is the loading spinner
        if (this.elements.viewSalesModalContent.innerHTML.includes('spinner-border') || this.elements.viewSalesModalContent.innerHTML.trim() === '') {
            // Show loading indicator
            this.elements.viewSalesModalContent.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            // Fetch sales data via AJAX
            fetch(`/admin/sales/modal-view/${id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.text(); // Get as text, as it's likely HTML
            })
            .then(html => {
                if (this.elements.viewSalesModalContent) {
                    this.elements.viewSalesModalContent.innerHTML = html;
                    // Re-format currency values after content is loaded
                    this.formatAllCurrencyValues();

                    // Set URLs for modal buttons
                    if (this.elements.salesModalEdit) {
                        this.elements.salesModalEdit.href = `/admin/sales/edit/${id}`;
                    }
                    if (this.elements.salesModalFullView) {
                        this.elements.salesModalFullView.href = `/admin/sales/view/${id}`;
                    }
                }
            })
            .catch(error => {
                console.error('Error loading sales details:', error);
                if (this.elements.viewSalesModalContent) {
                    this.elements.viewSalesModalContent.innerHTML = '<div class="alert alert-danger">Failed to load sales details.</div>';
                }
                showToast('Error', 'Failed to load sales details.', 'error');
            });
        }
    }

    printModalContent() {
        if (!this.elements.viewSalesModalContent) return;

        const printContent = this.elements.viewSalesModalContent.innerHTML;
        const originalContent = document.body.innerHTML;

        document.body.innerHTML = `
            <div class="container print-container">
                <div class="card">
                    <div class="card-body">${printContent}</div>
                </div>
            </div>
        `;

        window.print();
        document.body.innerHTML = originalContent;

        setTimeout(() => window.location.reload(), 100);
    }
}

/**
 * Bulk Selection Functionality for Sales Orders - Adapted from Purchase Orders
 */
class SalesOrderBulkSelection {
    constructor() {
        this.selectAllCheckbox = null;
        this.rowCheckboxes = null;
        this.bulkActionsBar = null;
        this.selectedCount = null;
        this.isInitialized = false;

        this.init();
    }

    init() {
        if (this.isInitialized) {
            console.log("Sales bulk selection already initialized");
            return;
        }

        const maxAttempts = 5;
        let attempts = 0;

        const tryInit = () => {
            attempts++;

            this.selectAllCheckbox = document.getElementById("selectAll");
            this.rowCheckboxes = document.querySelectorAll(".row-checkbox");
            this.bulkActionsBar = document.getElementById("bulkActionsBar");
            this.selectedCount = document.getElementById("selectedCount");

            // If no checkboxes are found, or essential elements are missing, return early
            if (
                !this.selectAllCheckbox ||
                !this.bulkActionsBar ||
                !this.selectedCount ||
                this.rowCheckboxes.length === 0
            ) {
                if (attempts < maxAttempts) {
                    console.log(
                        `Sales bulk selection init attempt ${attempts}/${maxAttempts} - retrying...`
                    );
                    setTimeout(tryInit, 300);
                    return;
                }

                console.warn(
                    "Sales bulk selection elements not found or no items to select after",
                    maxAttempts,
                    "attempts"
                );
                // Ensure bulk actions bar is hidden if no items
                if (this.bulkActionsBar) {
                    this.bulkActionsBar.style.display = "none";
                }
                return;
            }

            this.setupEventListeners();
            this.updateUI();
            this.isInitialized = true;
            console.log("Sales bulk selection initialized successfully");
        };

        tryInit();
    }

    setupEventListeners() {
        // Select all functionality
        this.selectAllCheckbox.addEventListener("change", (e) => {
            const isChecked = e.target.checked;
            this.rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = isChecked;
            });
            this.updateUI();
        });

        // Individual checkbox changes
        this.rowCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener("change", () => {
                this.updateSelectAllState();
                this.updateBulkActionsBar();
            });
        });
    }

    updateSelectAllState() {
        const totalCheckboxes = this.rowCheckboxes.length;
        const checkedCheckboxes = document.querySelectorAll(
            ".row-checkbox:checked"
        ).length;

        if (this.selectAllCheckbox) {
            if (checkedCheckboxes === 0) {
                this.selectAllCheckbox.indeterminate = false;
                this.selectAllCheckbox.checked = false;
            } else if (checkedCheckboxes === totalCheckboxes) {
                this.selectAllCheckbox.indeterminate = false;
                this.selectAllCheckbox.checked = true;
            } else {
                this.selectAllCheckbox.indeterminate = true;
                this.selectAllCheckbox.checked = false;
            }
        }
    }

    updateBulkActionsBar() {
        const checkedCount = document.querySelectorAll(
            ".row-checkbox:checked"
        ).length;

        if (this.bulkActionsBar) {
            if (checkedCount > 0) {
                this.bulkActionsBar.style.display = "block";
                this.selectedCount.textContent = checkedCount;
            } else {
                this.bulkActionsBar.style.display = "none";
            }
        }
    }

    updateUI() {
        this.updateSelectAllState();
        this.updateBulkActionsBar();
    }

    clearSelection() {
        this.selectAllCheckbox.checked = false;
        this.selectAllCheckbox.indeterminate = false;
        this.rowCheckboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });
        this.updateUI();
    }

    getSelectedIds() {
        return Array.from(
            document.querySelectorAll(".row-checkbox:checked")
        ).map((cb) => cb.value);
    }
}

// Global bulk selection instance for sales orders
let salesBulkSelection = null;

// Sales Order Bulk action functions
window.clearSalesSelection = function () {
    if (salesBulkSelection) {
        salesBulkSelection.clearSelection();
    }
};

window.getSalesSelectedIds = function () {
    return salesBulkSelection ? salesBulkSelection.getSelectedIds() : [];
};

function performBulkDeleteSales(selectedIds, confirmButton, modal) {
    console.log("performBulkDeleteSales called with IDs:", selectedIds);

    if (!selectedIds || selectedIds.length === 0) return;

    const originalText = confirmButton.innerHTML;
    confirmButton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Deleting...
        `;
    confirmButton.disabled = true;

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error("CSRF token not found");
        showToast(
            "Error",
            "Security token not found. Please refresh the page.",
            "error"
        );
        resetButton(confirmButton, originalText);
        return;
    }

    fetch("/admin/sales/bulk-delete", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken.getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify({
            ids: selectedIds,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                modal.hide();
                // Listen for the 'hidden.bs.modal' event to ensure the modal is fully closed
                modal._element.addEventListener(
                    "hidden.bs.modal",
                    function handler() {
                        modal._element.removeEventListener(
                            "hidden.bs.modal",
                            handler
                        ); // Remove the listener
                        showToast(
                            "Success",
                            `${data.deleted_count || selectedIds.length} sales order(s) deleted successfully!`,
                            "success"
                        );
                        // Explicitly remove any remaining modal backdrops
                        const backdrops =
                            document.querySelectorAll(".modal-backdrop");
                        backdrops.forEach((backdrop) => backdrop.remove());
                    }
                );

                // Remove deleted rows from the table
                selectedIds.forEach((id) => {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) {
                        row.remove();
                    }
                });

                if (salesBulkSelection) {
                    salesBulkSelection.updateUI();
                }
            } else {
                showToast(
                    "Error",
                    data.message || "Failed to delete sales orders.",
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            showToast(
                "Error",
                "An error occurred while deleting sales orders.",
                "error"
            );
        })
        .finally(() => {
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
        });
}

window.bulkDeleteSales = function () {
    const selected = getSalesSelectedIds();
    if (!selected.length) {
        showToast(
            "Warning",
            "Please select sales orders to delete.",
            "warning"
        );
        return;
    }

    document.getElementById("bulkDeleteCount").textContent = selected.length;
    const modal = new bootstrap.Modal(
        document.getElementById("bulkDeleteModal")
    );
    modal.show();

    const confirmBtn = document.getElementById("confirmBulkDeleteBtn");
    const newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

    newBtn.addEventListener("click", () =>
        performBulkDeleteSales(selected, newBtn, modal)
    );
};

window.bulkExportSales = function () {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    ).map((cb) => cb.value);

    if (selected.length === 0) {
        showToast(
            "Warning",
            "Please select at least one sales order to export.",
            "warning"
        );
        return;
    }

    const submitBtn = document.querySelector('[onclick="bulkExportSales()"]');
    const originalText = submitBtn ? submitBtn.innerHTML : "";

    if (submitBtn) {
        submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
        submitBtn.disabled = true;
    }

    // Create form and submit for export
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/sales/bulk-export";
    form.style.display = "none";

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const csrfInput = document.createElement("input");
        csrfInput.type = "hidden";
        csrfInput.name = "_token";
        csrfInput.value = csrfToken.getAttribute("content");
        form.appendChild(csrfInput);
    }

    // Add selected IDs
    selected.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "ids[]";
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();

    // Reset button after a delay
    setTimeout(() => {
        if (submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
        document.body.removeChild(form);
    }, 2000);
};

window.bulkMarkAsPaidSales = function () {
    console.log("bulkMarkAsPaidSales function called");

    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    );

    console.log("Initially selected items:", selected.length);

    // If no items are selected, perform smart selection first
    if (selected.length === 0) {
        smartSelectUnpaidOnlySales();

        // Recheck selected items after smart selection
        const newSelected = Array.from(
            document.querySelectorAll(".row-checkbox:checked")
        );

        if (newSelected.length === 0) {
            showToast(
                "Info",
                "No unpaid sales orders available to mark as paid.",
                "info"
            );
            return;
        }
    } else {
        // Check if any selected sales orders are already paid using improved detection
        const selectedPaidSales = selected.filter((checkbox) => {
            const row = checkbox.closest("tr");

            // Try multiple selectors to find the status element
            let statusElement = row.querySelector(".sort-status span");

            if (!statusElement) {
                const statusCell = row.querySelector(".sort-status");
                if (statusCell) {
                    statusElement = statusCell.querySelector("span");
                }
            }

            if (!statusElement) {
                statusElement = row.querySelector(".badge");
            }

            const status = statusElement
                ? statusElement.textContent.trim()
                : "";

            // Check for various "paid" status indicators
            return (
                status === "Paid" ||
                status.toLowerCase().includes("paid") ||
                statusElement?.classList.contains("badge-success") ||
                statusElement?.classList.contains("bg-success") ||
                statusElement?.innerHTML.toLowerCase().includes("paid")
            );
        });

        console.log("Found paid items in selection:", selectedPaidSales.length);

        if (selectedPaidSales.length > 0) {
            // Uncheck paid sales orders and show warning
            selectedPaidSales.forEach((checkbox) => {
                checkbox.checked = false;
                const row = checkbox.closest("tr");
                row.classList.add("table-warning");
                setTimeout(() => {
                    row.classList.remove("table-warning");
                }, 2000);
            });

            showToast(
                "Warning",
                `${selectedPaidSales.length} paid sales order(s) were excluded from selection.`,
                "warning"
            );

            // Check if any unpaid sales orders remain selected
            const remainingSelected = Array.from(
                document.querySelectorAll(".row-checkbox:checked")
            );

            console.log(
                "Remaining selected after filtering:",
                remainingSelected.length
            );

            if (remainingSelected.length === 0) {
                return;
            }
        }
    }

    // Get final selected count and IDs
    const finalSelected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    ).map((cb) => cb.value);

    console.log("Final selected IDs:", finalSelected);

    if (finalSelected.length === 0) {
        showToast("Info", "No unpaid sales orders selected.", "info");
        return;
    }

    // Update the count in the modal
    const bulkPaidCount = document.getElementById("bulkPaidCount");
    if (bulkPaidCount) {
        bulkPaidCount.textContent = finalSelected.length;
    }

    // Show confirmation modal
    const bulkMarkAsPaidModal = new bootstrap.Modal(
        document.getElementById("bulkMarkAsPaidModal")
    );
    bulkMarkAsPaidModal.show();

    // Handle confirmation button
    const confirmBtn = document.getElementById("confirmBulkPaidBtn");
    if (confirmBtn) {
        // Remove any existing event listeners by cloning the button
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener("click", function () {
            console.log("Confirm bulk mark as paid button clicked");
            confirmBulkMarkAsPaidSales(
                finalSelected,
                this,
                bulkMarkAsPaidModal
            );
        });
    }
};

function smartSelectUnpaidOnlySales() {
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    let excludedCount = 0;

    rowCheckboxes.forEach((checkbox) => {
        // Get the sales order status from the row
        const row = checkbox.closest("tr");

        // Try multiple selectors to find the status element
        let statusElement = row.querySelector(".sort-status span");

        if (!statusElement) {
            // Fallback: look for any span in the status column
            const statusCell = row.querySelector(".sort-status");
            if (statusCell) {
                statusElement = statusCell.querySelector("span");
            }
        }

        if (!statusElement) {
            // Another fallback: look for badge class
            statusElement = row.querySelector(".badge");
        }

        const status = statusElement ? statusElement.textContent.trim() : "";

        // Check for various "paid" status indicators
        const isPaid =
            status === "Paid" ||
            status.toLowerCase().includes("paid") ||
            statusElement?.classList.contains("badge-success") ||
            statusElement?.classList.contains("bg-success") ||
            statusElement?.innerHTML.toLowerCase().includes("paid");

        // Only select if status is not 'Paid'
        if (isPaid) {
            checkbox.checked = false;
            // Add visual feedback for excluded items
            row.classList.add("table-warning");
            setTimeout(() => {
                row.classList.remove("table-warning");
            }, 2000);
            excludedCount++;
        } else {
            checkbox.checked = true;
        }
    });

    // Update bulk actions bar only (don't update select-all state)
    if (salesBulkSelection) {
        salesBulkSelection.updateBulkActionsBar();
    }

    // Show notification if some items were excluded
    if (excludedCount > 0) {
        showToast(
            "Info",
            `${excludedCount} paid sales order(s) were excluded from selection.`,
            "info",
            3000
        );
    }
}

function confirmBulkMarkAsPaidSales(selectedIds, confirmButton, modal) {
    console.log("confirmBulkMarkAsPaidSales called with IDs:", selectedIds);

    if (!selectedIds || selectedIds.length === 0) return;

    // Show loading state
    const originalText = confirmButton.innerHTML;
    confirmButton.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Processing...
    `;
    confirmButton.disabled = true;

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error("CSRF token not found");
        showToast(
            "Error",
            "Security token not found. Please refresh the page.",
            "error"
        );
        resetButton(confirmButton, originalText);
        return;
    }

    console.log("CSRF token found:", csrfToken.getAttribute("content"));

    // Make the API request
    fetch("/admin/sales/bulk-mark-paid", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken.getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify({
            ids: selectedIds,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Close modal
                modal.hide();

                // Listen for the 'hidden.bs.modal' event to ensure the modal is fully closed
                modal._element.addEventListener(
                    "hidden.bs.modal",
                    function handler() {
                        modal._element.removeEventListener(
                            "hidden.bs.modal",
                            handler
                        ); // Remove the listener
                        // Show success message
                        showToast(
                            "Success",
                            `${data.updated_count || selectedIds.length} sales order(s) marked as paid successfully!`,
                            "success"
                        );
                        // Explicitly remove any remaining modal backdrops
                        const backdrops =
                            document.querySelectorAll(".modal-backdrop");
                        backdrops.forEach((backdrop) => backdrop.remove());
                    }
                );

                

                // Reload page after short delay
                // setTimeout(() => {
                //     location.reload();
                // }, 1000);

                // Dynamically update UI
                selectedIds.forEach((id) => {
                    const row = document.querySelector(`tr[data-id="${id}"]`); // Assuming rows have data-id attribute
                    if (row) {
                        const statusBadge = row.querySelector(".badge");
                        if (statusBadge) {
                            const statusCell = row.querySelector('.sort-status');
                        if (statusCell) {
                            statusCell.innerHTML = '<span class="badge bg-green-lt"><span class="h4"><i class="ti ti-check me-1 fs-4"></i> Paid</span></span>';
                        }
                        }
                        
                    }
                });

                // Explicitly uncheck all checkboxes
                document.querySelectorAll(".row-checkbox").forEach((checkbox) => {
                    checkbox.checked = false;
                });

                // Update bulk action bar and select all state
                if (salesBulkSelection) {
                    salesBulkSelection.updateUI();
                }
            } else {
                showToast(
                    "Error",
                    data.message || "Failed to update sales orders.",
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            showToast(
                "Error",
                "An error occurred while updating sales orders.",
                "error"
            );
        })
        .finally(() => {
            // Reset button state
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
        });
}

function clearSelectionSales() {
    document.querySelectorAll(".row-checkbox").forEach((checkbox) => {
        checkbox.checked = false;
    });
    const selectAll = document.getElementById("selectAll");
    if (selectAll) {
        selectAll.checked = false;
    }
    const bulkActionsBar = document.getElementById("bulkActionsBar");
    if (bulkActionsBar) {
        bulkActionsBar.style.display = "none";
    }
}

function getSalesSelectedIds() {
    return Array.from(document.querySelectorAll(".row-checkbox:checked")).map(
        (cb) => cb.value
    );
}

function resetButton(button, originalText) {
    if (button) {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Keep the existing performBulkMarkAsPaid function for backward compatibility
function performBulkMarkAsPaidSales(selectedIds, confirmButton, modal) {
    confirmBulkMarkAsPaidSales(selectedIds, confirmButton, modal);
}

// Function to handle form submission via AJAX

/**
 * Initialize appropriate module based on the current page
 */
document.addEventListener("DOMContentLoaded", function () {
    // Add a small delay to ensure all elements are loaded
    setTimeout(() => {
        // Determine current page
        const pathname = window.location.pathname;
        console.log("Current pathname:", pathname); // Debug log

        try {
            if (pathname.includes("/admin/sales/create")) {
                // Initialize create page functionality
                window.salesApp = new SalesOrderCreate();
                console.log("Sales Order Create App initialized");
            } else if (
                // Fix the route matching for edit pages - matches /admin/sales/edit/46
                pathname.includes("/admin/sales/edit/") ||
                pathname.match(/\/admin\/sales\/edit\/\d+$/)
            ) {
                // Initialize edit page functionality
                window.salesApp = new SalesOrderEdit();
                console.log("Sales Order Edit App initialized");
            } else if (
                pathname.includes("/admin/sales/modal") ||
                (pathname.includes("/admin/sales") &&
                    pathname.match(/\/\d+$/)) ||
                pathname.includes("/admin/sales/show")
            ) {
                // Initialize view functionality for modal or show pages
                window.salesApp = new SalesOrderView();
                console.log("Sales Order View App initialized");
            } else if (
                pathname === "/admin/sales" ||
                pathname.includes("/admin/sales?") ||
                pathname.includes("/admin/sales/")
            ) {
                // Initialize bulk selection for index page
                console.log("Initializing Sales Order Index page...");

                // Initialize view functionality even on index page for modals
                window.salesApp = new SalesOrderView();

                // Also initialize bulk selection if the class exists
                if (typeof SalesOrderBulkSelection !== "undefined") {
                    salesBulkSelection = new SalesOrderBulkSelection();
                    console.log("Sales Order Index bulk selection initialized");
                }

                console.log("Sales Order Index page initialized");
            }

            // Debug: Force initialize SalesOrderEdit if we're on any edit page
            if (pathname.includes("edit") && pathname.includes("sales")) {
                console.log(
                    "Force initializing SalesOrderEdit due to edit page detection"
                );
                window.salesApp = new SalesOrderEdit();
            }
        } catch (error) {
            console.error("Error initializing Sales Order App:", error);

            // Emergency fallback - try to initialize SalesOrderEdit if on edit page
            if (pathname.includes("edit") && pathname.includes("sales")) {
                try {
                    console.log(
                        "Emergency fallback: Attempting to initialize SalesOrderEdit"
                    );
                    window.salesApp = new SalesOrderEdit();
                } catch (fallbackError) {
                    console.error(
                        "Fallback initialization also failed:",
                        fallbackError
                    );
                }
            }
        }
    }, 250);
});
