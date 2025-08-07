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
        return new Intl.NumberFormat(currencySettings.locale, {
            style: "currency",
            currency: currencySettings.currency_code,
            maximumFractionDigits: currencySettings.decimal_places,
        }).format(amount);
    }

    /**
     * Calculate total price for a product with proper rounding to avoid floating-point issues
     * @param {number} price - Unit price
     * @param {number} quantity - Quantity
     * @param {number} discount - Discount amount/percentage
     * @param {string} discountType - 'fixed' or 'percentage'
     * @returns {number} Total price after discount
     */
    calculateTotal(price, quantity, discount, discountType) {
        // Convert to integers to avoid floating-point issues (multiply by 100 for cents)
        const priceInCents = Math.round(price * 100);
        const discountInCents =
            discountType === "percentage"
                ? Math.round((priceInCents * discount) / 100)
                : Math.round(discount * 100);

        const totalPerUnitInCents = priceInCents - discountInCents;
        const totalInCents = totalPerUnitInCents * quantity;

        // Convert back to currency units
        return Math.round(totalInCents / 100);
    }

    /**
     * Calculate discount amount based on value and type
     * @param {number} subtotal - Subtotal amount
     * @param {number} discountValue - Discount value
     * @param {string} discountType - 'fixed' or 'percentage'
     * @returns {number} Calculated discount amount
     */
    calculateDiscount(subtotal, discountValue, discountType) {
        if (discountType === "percentage") {
            return Math.round((subtotal * discountValue) / 100);
        }
        return discountValue;
    }

    /**
     * Safe element getter with error handling
     * @param {string} id - Element ID
     * @returns {HTMLElement|null} Element or null
     */
    safeGetElement(id) {
        try {
            return document.getElementById(id);
        } catch (error) {
            console.warn(`Element with ID '${id}' not found:`, error);
            return null;
        }
    }
}

/**
 * PurchaseOrderCreate - Manages the purchase order creation functionality
 * Uses in-memory storage instead of localStorage/sessionStorage
 */
class PurchaseOrderCreate extends PurchaseOrderModule {
    constructor(config = {}) {
        super(config);

        // Store DOM elements with safe getter
        this.elements = this.initializeElements();

        // In-memory data storage (replaces localStorage)
        this.products = [];
        this.orderDiscount = { value: 0, type: "fixed" };
        this.sessionJustSubmitted = false;
        this.currentStock = 0; // Track current selected product stock

        // Check if we need to clear storage after submission
        this.checkSessionState();

        // Initialize components
        this.initFlatpickr();
        this.initEventListeners();
        this.renderTable();
    }

    initializeElements() {
        const elementIds = [
            "order_date",
            "due_date",
            "supplier_id",
            "product_id",
            "last_price",
            "quantity",
            "new_price",
            "discount",
            "discount_type",
            "addProduct",
            "clearProducts",
            "productTableBody",
            "productsField",
            "discountTotalValue",
            "discountTotalType",
            "applyTotalDiscount",
            "invoice",
            "invoiceForm",
            "stock_available",
            "quantity_warning",
        ];

        const elements = {};
        elementIds.forEach((id) => {
            elements[id] = this.safeGetElement(id);
        });

        return elements;
    }

    initFlatpickr() {
        // Check if flatpickr is available
        if (typeof flatpickr === "undefined") {
            console.warn("Flatpickr library not loaded");
            return;
        }

        const flatpickrConfig = {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d-m-Y",
            allowInput: true,
        };

        // Initialize order date
        if (this.elements.order_date) {
            try {
                this.elements.order_date._flatpickr = flatpickr(
                    this.elements.order_date,
                    {
                        ...flatpickrConfig,
                        defaultDate: new Date(),
                        onChange: () => this.calculateDueDate(),
                    }
                );
            } catch (error) {
                console.warn(
                    "Failed to initialize order date flatpickr:",
                    error
                );
            }
        }

        // Initialize due date
        if (this.elements.due_date) {
            try {
                this.elements.due_date._flatpickr = flatpickr(
                    this.elements.due_date,
                    flatpickrConfig
                );
            } catch (error) {
                console.warn("Failed to initialize due date flatpickr:", error);
            }
        }
    }

    checkSessionState() {
        // Use in-memory flag instead of sessionStorage
        if (this.sessionJustSubmitted) {
            this.products = [];
            this.orderDiscount = { value: 0, type: "fixed" };
            this.sessionJustSubmitted = false;
        }
    }

    initEventListeners() {
        // Due date calculation
        if (this.elements.supplier_id) {
            this.elements.supplier_id.addEventListener("change", () =>
                this.calculateDueDate()
            );
        }

        // Product selection
        if (this.elements.product_id) {
            this.elements.product_id.addEventListener("change", () => {
                this.updateProductPrice();
                this.updateStockDisplay();
            });
        }

        // Add product button
        if (this.elements.addProduct) {
            this.elements.addProduct.addEventListener("click", () =>
                this.addProduct()
            );
        }

        // Clear products
        if (this.elements.clearProducts) {
            this.elements.clearProducts.addEventListener("click", () =>
                this.clearProducts()
            );
        }

        // Apply order discount
        if (this.elements.applyTotalDiscount) {
            this.elements.applyTotalDiscount.addEventListener("click", () =>
                this.applyOrderDiscount()
            );
        }

        // Form submission
        if (this.elements.invoiceForm) {
            this.elements.invoiceForm.addEventListener("submit", (e) => {
                if (this.elements.productsField) {
                    this.elements.productsField.value = JSON.stringify(
                        this.products
                    );
                }
                this.sessionJustSubmitted = true;
            });
        }
    }

    calculateDueDate() {
        if (
            !this.elements.order_date ||
            !this.elements.supplier_id ||
            !this.elements.due_date
        ) {
            return;
        }

        // Get date from flatpickr or input value
        let orderDateValue;
        if (this.elements.order_date._flatpickr) {
            const selectedDates =
                this.elements.order_date._flatpickr.selectedDates;
            orderDateValue = selectedDates.length > 0 ? selectedDates[0] : null;
        } else {
            orderDateValue = this.elements.order_date.value;
        }

        const selectedOption =
            this.elements.supplier_id.options[
                this.elements.supplier_id.selectedIndex
            ];

        if (!orderDateValue || !selectedOption) return;

        const orderDate = new Date(orderDateValue);
        const paymentTerms = selectedOption.dataset.paymentTerms;

        if (paymentTerms) {
            orderDate.setDate(orderDate.getDate() + parseInt(paymentTerms));

            // Update due date
            if (this.elements.due_date._flatpickr) {
                this.elements.due_date._flatpickr.setDate(orderDate);
            } else {
                this.elements.due_date.value = orderDate
                    .toISOString()
                    .split("T")[0];
            }
        }
    }

    updateProductPrice() {
        if (
            !this.elements.product_id ||
            !this.elements.last_price ||
            !this.elements.new_price
        ) {
            return;
        }

        const selectedOption =
            this.elements.product_id.options[
                this.elements.product_id.selectedIndex
            ];

        if (selectedOption && selectedOption.getAttribute("data-price")) {
            const price = selectedOption.getAttribute("data-price") || "";
            this.elements.last_price.value = price;
            this.elements.new_price.value = price;
        }
    }

    updateStockDisplay() {
        if (!this.elements.product_id || !this.elements.stock_available) {
            return;
        }

        const selectedOption =
            this.elements.product_id.options[
                this.elements.product_id.selectedIndex
            ];

        if (selectedOption && selectedOption.value) {
            const stock =
                parseInt(selectedOption.getAttribute("data-stock")) || 0;
            this.currentStock = stock;

            // Just display the current stock without any restrictions
            this.elements.stock_available.textContent = stock;

            // Update stock display styling based on current stock level
            this.updateStockStyling(stock);

            // Reset quantity field without any max limit
            if (this.elements.quantity) {
                this.elements.quantity.removeAttribute("max");
                this.elements.quantity.value = "";
            }
        } else {
            this.elements.stock_available.textContent = "-";
            this.currentStock = 0;
            if (this.elements.quantity) {
                this.elements.quantity.removeAttribute("max");
            }
        }
    }

    updateStockStyling(stock) {
        if (!this.elements.stock_available) return;

        // Remove existing classes
        this.elements.stock_available.classList.remove(
            "text-primary",
            "text-warning",
            "text-danger"
        );

        // Apply styling based on stock level
        if (stock === 0) {
            this.elements.stock_available.classList.add("text-danger");
        } else if (stock <= 5) {
            this.elements.stock_available.classList.add("text-warning");
        } else {
            this.elements.stock_available.classList.add("text-primary");
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
        const subtotalEl = document.getElementById("subtotal");
        const orderDiscountTotalEl =
            document.getElementById("orderDiscountTotal");
        const finalTotalEl = document.getElementById("finalTotal");
        const totalDiscountInputEl =
            document.getElementById("totalDiscountInput");

        if (subtotalEl) subtotalEl.innerText = this.formatCurrency(subtotal);
        if (orderDiscountTotalEl)
            orderDiscountTotalEl.innerText =
                this.formatCurrency(orderDiscountAmount);
        if (finalTotalEl)
            finalTotalEl.innerText = this.formatCurrency(finalTotal);
        if (totalDiscountInputEl)
            totalDiscountInputEl.value = orderDiscountAmount;

        // Update JSON of products for form submission
        if (this.elements.productsField) {
            this.elements.productsField.value = JSON.stringify(this.products);
        }
    }

    addProduct() {
        if (
            !this.elements.product_id ||
            !this.elements.quantity ||
            !this.elements.new_price
        ) {
            console.warn("Required form elements not found for adding product");
            return;
        }

        const productId = this.elements.product_id.value;
        const productName =
            this.elements.product_id.options[
                this.elements.product_id.selectedIndex
            ].text;
        const quantity = parseInt(this.elements.quantity.value) || 0;
        const price = parseFloat(this.elements.new_price.value) || 0;
        const discount = parseFloat(this.elements.discount?.value) || 0;
        const discountType = this.elements.discount_type?.value || "fixed";

        // Basic validation - just check if quantity is greater than 0
        if (quantity <= 0) {
            alert("Please enter a valid quantity greater than 0.");
            return;
        }

        // Get stock for the product
        const selectedOption =
            this.elements.product_id.options[
                this.elements.product_id.selectedIndex
            ];
        const stock = parseInt(selectedOption.getAttribute("data-stock")) || 0;

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
            product_id: productId,
            uniqueId,
            name: productName,
            quantity,
            price,
            discount,
            discountType,
            total,
            stock,
        });

        // Update UI
        this.renderTable();
        this.clearProductForm();
        this.updateStockDisplay(); // Refresh stock display after adding
    }

    clearProductForm() {
        const formFields = [
            "product_id",
            "quantity",
            "new_price",
            "discount",
            "last_price",
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
    }

    clearProducts() {
        this.products = [];
        this.orderDiscount = { value: 0, type: "fixed" };

        // Reset discount UI
        if (this.elements.discountTotalValue)
            this.elements.discountTotalValue.value = 0;
        if (this.elements.discountTotalType)
            this.elements.discountTotalType.value = "fixed";

        this.renderTable();
        this.updateStockDisplay(); // Refresh stock display after clearing
    }

    applyOrderDiscount() {
        if (
            !this.elements.discountTotalValue ||
            !this.elements.discountTotalType
        ) {
            return;
        }

        this.orderDiscount = {
            value: parseFloat(this.elements.discountTotalValue.value) || 0,
            type: this.elements.discountTotalType.value,
        };

        this.updateTotalPrice();
    }

    attachTableEventListeners() {
        if (!this.elements.productTableBody) return;

        // Remove existing listeners to prevent duplicates
        const newTableBody = this.elements.productTableBody.cloneNode(true);
        this.elements.productTableBody.parentNode.replaceChild(
            newTableBody,
            this.elements.productTableBody
        );
        this.elements.productTableBody = newTableBody;

        // Event delegation for table rows
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

            // Remove any validation styling and just update the quantity
            target.classList.remove("is-invalid");
            product.quantity = newQuantity;
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

    renderTable() {
        if (!this.elements.productTableBody) return;

        // Clear table body
        this.elements.productTableBody.innerHTML = "";

        // Add rows for each product
        this.products.forEach((product, index) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td class="text-center">${index + 1}</td>
                <td>${product.name}</td>
                <td class="text-center">
                    <span class="badge ${
                        product.stock === 0
                            ? "bg-danger"
                            : product.stock <= 5
                            ? "bg-warning"
                            : "bg-success"
                    }">
                        ${product.stock}
                    </span>
                </td>
                <td class="text-center">
                    <input type="number" class="form-control quantity-input text-center"
                        value="${product.quantity}" data-unique-id="${
                product.uniqueId
            }"
                        min="1" style="width:80px;" />
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
}

/**
 * PurchaseOrderEdit - Manages the purchase order edit functionality
 */
class PurchaseOrderEdit extends PurchaseOrderModule {
    constructor(config = {}) {
        super(config);

        this.elements = this.initializeEditElements();
        this.initEventListeners();
        this.calculateAllAmounts();
    }

    initializeEditElements() {
        return {
            discountTotalValue: this.safeGetElement("discountTotalValue"),
            discountTotalType: this.safeGetElement("discountTotalType"),
            subtotalElement: this.safeGetElement("subtotal"),
            orderDiscountTotalElement:
                this.safeGetElement("orderDiscountTotal"),
            finalTotalElement: this.safeGetElement("finalTotal"),
            totalDiscountInput: this.safeGetElement("totalDiscountInput"),
            quantityInputs: document.querySelectorAll(".quantity-input"),
            priceInputs: document.querySelectorAll(".price-input"),
            discountInputs: document.querySelectorAll(".discount-input"),
            discountTypeInputs: document.querySelectorAll(
                ".discount-type-input"
            ),
            form: document.getElementById("edit-po-form"),
            productsJsonInput: document.getElementById("products-json"),
        };
    }

    initEventListeners() {
        // Input event listeners for calculations
        document.addEventListener("input", (event) => {
            if (
                event.target.matches(
                    ".quantity-input, .price-input, .discount-input"
                )
            ) {
                const itemId = event.target.dataset.itemId;
                if (itemId) {
                    this.calculateOrderTotal(); // Always recalculate order total after item change
                }
            } else if (event.target.matches("#discountTotalValue")) {
                this.calculateOrderTotal();
            }
        });

        // Change event listeners for select inputs
        document.addEventListener("change", (event) => {
            if (event.target.matches(".discount-type-input")) {
                const itemId = event.target.dataset.itemId;
                if (itemId) {
                    this.calculateOrderTotal(); // Always recalculate order total after item change
                }
            } else if (event.target.matches("#discountTotalType")) {
                this.calculateOrderTotal();
            }
        });

        if (this.elements.form) {
            this.elements.form.addEventListener(
                "submit",
                this.serializeProducts.bind(this)
            );
        }
    }

    calculateAllAmounts() {
        this.calculateOrderTotal();
    }

    calculateOrderTotal() {
        let subtotal = 0;

        // Calculate per-item amounts and sum for subtotal
        document.querySelectorAll("tbody tr").forEach((row) => {
            const itemId = row.querySelector(".quantity-input")?.dataset.itemId;
            if (!itemId) return;

            const quantity =
                parseFloat(
                    row.querySelector(
                        `.quantity-input[data-item-id="${itemId}"]`
                    ).value
                ) || 0;
            const price =
                parseFloat(
                    row.querySelector(`.price-input[data-item-id="${itemId}"]`)
                        .value
                ) || 0;
            const discountInput = row.querySelector(
                `.discount-input[data-item-id="${itemId}"]`
            );
            const discountTypeSelect = row.querySelector(
                `.discount-type-input[data-item-id="${itemId}"]`
            );

            const discountValue = parseFloat(discountInput?.value) || 0;
            const discountType = discountTypeSelect?.value || "fixed";

            // Calculate total for this item
            const itemTotal = this.calculateTotal(
                price,
                quantity,
                discountValue,
                discountType
            );

            // Update the amount field for this item
            const amountInput = row.querySelector(
                `.amount-input[data-item-id="${itemId}"]`
            );
            if (amountInput) {
                amountInput.value = Math.round(itemTotal);
            }

            // Add to running subtotal
            subtotal += itemTotal;
        });

        // Get order discount values
        const discountTotalValue =
            parseFloat(this.elements.discountTotalValue?.value) || 0;
        const discountTotalType =
            this.elements.discountTotalType?.value || "fixed";

        // Calculate order discount and final total
        const orderDiscountAmount = this.calculateDiscount(
            subtotal,
            discountTotalValue,
            discountTotalType
        );
        const finalTotal = subtotal - orderDiscountAmount;

        // Update UI elements safely
        if (this.elements.subtotalElement) {
            this.elements.subtotalElement.textContent =
                this.formatCurrency(subtotal);
        }
        if (this.elements.orderDiscountTotalElement) {
            this.elements.orderDiscountTotalElement.textContent =
                this.formatCurrency(orderDiscountAmount);
        }
        if (this.elements.finalTotalElement) {
            this.elements.finalTotalElement.textContent =
                this.formatCurrency(finalTotal);
        }
        if (this.elements.totalDiscountInput) {
            this.elements.totalDiscountInput.value = orderDiscountAmount;
        }

        // Update JSON of products for form submission
        if (this.elements.productsJsonInput) {
            const products = [];
            document.querySelectorAll("tbody tr").forEach((row) => {
                const itemId =
                    row.querySelector(".quantity-input")?.dataset.itemId;
                if (!itemId) {
                    return;
                }

                const quantity =
                    parseFloat(
                        row.querySelector(
                            `.quantity-input[data-item-id="${itemId}"]`
                        ).value
                    ) || 0;
                const price =
                    parseFloat(
                        row.querySelector(
                            `.price-input[data-item-id="${itemId}"]`
                        ).value
                    ) || 0;
                const discount =
                    parseFloat(
                        row.querySelector(
                            `.discount-input[data-item-id="${itemId}"]`
                        ).value
                    ) || 0;
                const discountType = row.querySelector(
                    `.discount-type-input[data-item-id="${itemId}"]`
                ).value;

                products.push({
                    product_id: itemId, // Assuming itemId is the product_id
                    quantity: quantity,
                    price: price,
                    discount: discount,
                    discount_type: discountType,
                });
            });
            this.elements.productsJsonInput.value = JSON.stringify(products);
        }
    }

    serializeProducts() {
        const products = [];
        document.querySelectorAll("tbody tr").forEach((row) => {
            const itemId = row.querySelector(".quantity-input")?.dataset.itemId;
            if (!itemId) {
                return;
            }

            const quantity = parseFloat(
                row.querySelector(`.quantity-input[data-item-id="${itemId}"]`)
                    .value
            );
            const price = parseFloat(
                row.querySelector(`.price-input[data-item-id="${itemId}"]`)
                    .value
            );
            const discount = parseFloat(
                row.querySelector(`.discount-input[data-item-id="${itemId}"]`)
                    .value
            );
            const discountType = row.querySelector(
                `.discount-type-input[data-item-id="${itemId}"]`
            ).value;

            products.push({
                product_id: itemId, // Assuming itemId is the product_id
                quantity: quantity,
                price: price,
                discount: discount,
                discount_type: discountType,
            });
        });

        if (this.elements.productsJsonInput) {
            this.elements.productsJsonInput.value = JSON.stringify(products);
        }
    }
}

/**
 * PurchaseOrderView - Handles the view and modal functionality
 */
class PurchaseOrderView extends PurchaseOrderModule {
    constructor(config = {}) {
        super(config);

        this.elements = {
            deleteForm: this.safeGetElement("deleteForm"),
            viewPoModalContent: this.safeGetElement("viewPoModalContent"),
            poModalEdit: this.safeGetElement("poModalEdit"),
            poModalFullView: this.safeGetElement("poModalFullView"),
            poModalPrint: this.safeGetElement("poModalPrint"),
        };

        this.formatAllCurrencyValues();
        this.initModalListeners();
        this.initGlobalFunctions(); // Add this line
    }

    formatAllCurrencyValues() {
        const currencyElements = document.querySelectorAll(".currency-value");
        currencyElements.forEach((element) => {
            const value = parseFloat(element.dataset.value) || 0;
            element.textContent = this.formatCurrency(value);
        });
    }

    initModalListeners() {
        if (this.elements.poModalPrint) {
            this.elements.poModalPrint.addEventListener("click", () =>
                this.printModalContent()
            );
        }
    }

    // Add this method to ensure global functions are available
    initGlobalFunctions() {
        // Make sure loadPoDetails is available globally
        window.loadPoDetails = (id) => this.loadPoDetails(id);
        window.setDeleteFormAction = (url) => this.setDeleteFormAction(url);
    }

    setDeleteFormAction(url) {
        if (this.elements.deleteForm) {
            this.elements.deleteForm.action = url;
        } else {
            console.error("Delete form element not found");
        }
    }

    loadPoDetails(id) {
        if (!this.elements.viewPoModalContent) {
            console.error("Modal content element not found");
            return;
        }

        // Set URLs for modal buttons
        if (this.elements.poModalEdit) {
            this.elements.poModalEdit.href = `/admin/po/edit/${id}`;
        }
        if (this.elements.poModalFullView) {
            this.elements.poModalFullView.href = `/admin/po/view/${id}`;
        }

        // Show loading
        this.elements.viewPoModalContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading purchase order details...</p>
                </div>
            `;

        // Fetch details
        fetch(`/admin/po/modal-view/${id}`)
            .then((response) => {
                if (!response.ok)
                    throw new Error("Network response was not ok");
                return response.text();
            })
            .then((html) => {
                this.elements.viewPoModalContent.innerHTML = html;
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

    printModalContent() {
        if (!this.elements.viewPoModalContent) return;

        const printContent = this.elements.viewPoModalContent.innerHTML;
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
 * Bulk Selection Functionality - Consolidated and improved
 */
class PurchaseOrderBulkSelection {
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
                !this.selectedCount
            ) {
                console.warn("Bulk selection essential elements not found.");
                // Ensure bulk actions bar is hidden if essential elements are missing
                if (this.bulkActionsBar) {
                    this.bulkActionsBar.style.display = "none";
                }
                return;
            }

            // If there are no checkboxes, hide the bar and return
            if (this.rowCheckboxes.length === 0) {
                this.bulkActionsBar.style.display = "none";

                return;
            }

            this.setupEventListeners();
            this.updateUI();
            this.isInitialized = true;
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
        this.updateBulkActionsBar();
        this.updateSelectAllState();
    }

    clearSelection() {
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

// Global bulk selection instance
let bulkSelection = null;

// Bulk action functions
window.clearPOSelection = function () {
    if (bulkSelection) {
        bulkSelection.clearSelection();
    }
};

window.getSelectedIds = function () {
    return bulkSelection ? bulkSelection.getSelectedIds() : [];
};

function performBulkDelete(selectedIds, confirmButton, modal) {
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

    fetch("/admin/po/bulk-delete", {
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
                sessionStorage.setItem('purchaseOrderBulkDeleteSuccess', `Bulk delete ${
                    data.deleted_count || selectedIds.length
                } purchase order(s) successfully!`);
                location.reload();
            } else {
                showToast(
                    "Error",
                    data.message || "Failed to delete purchase orders.",
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            showToast(
                "Error",
                "An error occurred while deleting purchase orders.",
                "error"
            );
        })
        .finally(() => {
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
            modal.hide(); // Ensure modal is always hidden
        });
}

window.bulkDeletePO = function () {
    const selected = getSelectedIds();
    if (!selected.length) {
        showToast(
            "Warning",
            "Please select purchase orders to delete.",
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
        performBulkDelete(selected, newBtn, modal)
    );
};

window.bulkExportPO = function () {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    ).map((cb) => cb.value);

    if (selected.length === 0) {
        showToast(
            "Warning",
            "Please select at least one purchase order to export.",
            "warning"
        );
        return;
    }

    const submitBtn = document.querySelector('[onclick="bulkExportPO()"]');
    const originalText = submitBtn ? submitBtn.innerHTML : "";

    if (submitBtn) {
        submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
        submitBtn.disabled = true;
    }

    // Create form and submit for export
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/po/bulk-export";
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

// Updated bulkMarkAsPaidPO function with smart selection (same pattern as transactions)
window.bulkMarkAsPaidPO = function () {
    const selected = Array.from(
        document.querySelectorAll(".row-checkbox:checked")
    );

    // If no items are selected, perform smart selection first
    if (selected.length === 0) {
        smartSelectUnpaidOnlyPO();

        // Recheck selected items after smart selection
        const newSelected = Array.from(
            document.querySelectorAll(".row-checkbox:checked")
        );

        if (newSelected.length === 0) {
            showToast(
                "Info",
                "No unpaid purchase orders available to mark as paid.",
                "info"
            );
            return;
        }
    } else {
        // Check if any selected purchase orders are already paid
        const selectedPaidPOs = selected.filter((checkbox) => {
            const row = checkbox.closest("tr");
            const statusBadge = row.querySelector(".badge");
            const status = statusBadge ? statusBadge.textContent.trim() : "";
            return status === "Paid";
        });

        if (selectedPaidPOs.length > 0) {
            // Uncheck paid purchase orders and show warning
            selectedPaidPOs.forEach((checkbox) => {
                checkbox.checked = false;
                const row = checkbox.closest("tr");
                row.classList.add("table-warning");
                setTimeout(() => {
                    row.classList.remove("table-warning");
                }, 2000);
            });

            // Update bulk actions if function exists
            if (typeof updateBulkActions === "function") {
                updateBulkActions();
            }

            showToast(
                "Warning",
                `${selectedPaidPOs.length} paid purchase order(s) were excluded from selection.`,
                "warning"
            );

            // Check if any unpaid purchase orders remain selected
            const remainingSelected = Array.from(
                document.querySelectorAll(".row-checkbox:checked")
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
            confirmBulkMarkAsPaidPO(finalSelected, this, bulkMarkAsPaidModal);
        });
    }
};

// Smart selection function - only called when bulk mark as paid is clicked
function smartSelectUnpaidOnlyPO() {
    const rowCheckboxes = document.querySelectorAll(".row-checkbox");
    let excludedCount = 0;

    rowCheckboxes.forEach((checkbox) => {
        // Get the purchase order status from the row
        const row = checkbox.closest("tr");
        const statusBadge = row.querySelector(".badge");
        const status = statusBadge ? statusBadge.textContent.trim() : "";

        // Only select if status is not 'Paid'
        if (status === "Paid") {
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
    if (bulkSelection) {
        bulkSelection.updateBulkActionsBar();
    }

    // Show notification if some items were excluded
    if (excludedCount > 0) {
        showToast(
            "Info",
            `${excludedCount} paid purchase order(s) were excluded from selection.`,
            "info",
            3000
        );
    }
}

/**
 * Confirm bulk mark as paid function (similar to transactions)
 */
function confirmBulkMarkAsPaidPO(selectedIds, confirmButton, modal) {
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

    // Make the API request
    fetch("/admin/po/bulk-mark-paid", {
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
                sessionStorage.setItem('purchaseOrderBulkMarkAsPaidSuccess', `${
                    data.updated_count || selectedIds.length
                } purchase order(s) marked as paid successfully!`);
                location.reload();
            } else {
                showToast(
                    "Error",
                    data.message || "Failed to update purchase orders.",
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            showToast(
                "Error",
                "An error occurred while updating purchase orders.",
                "error"
            );
        })
        .finally(() => {
            // Reset button state
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
            modal.hide(); // Ensure modal is always hidden
        });
}

function updatePurchaseStoreInfo() {
    fetch("/admin/po/metrics")
        .then((response) => response.json())
        .then((data) => {
            document.getElementById("totalInvoiceCount").textContent =
                data.totalinvoice;
            document.getElementById("invoiceOutCount").textContent =
                data.outCount;
            document.getElementById("amountOutCount").textContent =
                new Intl.NumberFormat(currencySettings.locale, {
                    style: "currency",
                    currency: currencySettings.currency_code,
                    maximumFractionDigits: currencySettings.decimal_places,
                }).format(data.outCountamount);
            document.getElementById("invoiceInCount").textContent =
                data.inCount;
            document.getElementById("amountInCount").textContent =
                new Intl.NumberFormat(currencySettings.locale, {
                    style: "currency",
                    currency: currencySettings.currency_code,
                    maximumFractionDigits: currencySettings.decimal_places,
                }).format(data.inCountamount);
            document.getElementById("monthlyPurchase").textContent =
                new Intl.NumberFormat(currencySettings.locale, {
                    style: "currency",
                    currency: currencySettings.currency_code,
                    maximumFractionDigits: currencySettings.decimal_places,
                }).format(data.totalMonthly);
            document.getElementById("monthlyPayment").textContent =
                new Intl.NumberFormat(currencySettings.locale, {
                    style: "currency",
                    currency: currencySettings.currency_code,
                    maximumFractionDigits: currencySettings.decimal_places,
                }).format(data.paymentMonthly);
        })
        .catch((error) =>
            console.error("Error fetching purchase metrics:", error)
        );
}

/**
 * Clear selection function for purchase orders
 */
function clearSelectionPO() {
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

/**
 * Get selected IDs helper function
 */
function getSelectedIds() {
    return Array.from(document.querySelectorAll(".row-checkbox:checked")).map(
        (cb) => cb.value
    );
}

/**
 * Reset button to original state
 */
function resetButton(button, originalText) {
    if (button) {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Keep the existing performBulkMarkAsPaid function for backward compatibility
function performBulkMarkAsPaid(selectedIds, confirmButton, modal) {
    confirmBulkMarkAsPaidPO(selectedIds, confirmButton, modal);
}

// Use the same toast function as transactions (from paste-1.txt)
// Function to handle form submission via AJAX

// --- Start Search Functionality (Adapted from product.js) ---
let searchTimeout;
let currentRequest = null;
let isSearchActive = false;
let originalTableContent = null;
let originalPoData = new Map(); // Changed from originalProductData

function initializeSearch() {
    const searchInput = document.getElementById("searchInput");
    if (!searchInput) return;

    storeOriginalTable();

    searchInput.addEventListener("input", function () {
        clearTimeout(searchTimeout);
        if (currentRequest) {
            currentRequest.abort();
            currentRequest = null;
        }

        const query = this.value.trim();

        searchTimeout = setTimeout(() => {
            if (query.length === 0) {
                if (isSearchActive) {
                    restoreOriginalTable();
                }
                isSearchActive = false;
            } else {
                performSearch(query);
                isSearchActive = true;
            }
        }, 500);
    });
}

function storeOriginalTable() {
    if (!originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            originalTableContent = tableBody.innerHTML;

            const rows = tableBody.querySelectorAll("tr[data-id]");
            rows.forEach((row) => {
                const poId = row.dataset.id;
                const poData = extractPoDataFromRow(row); // Changed function name
                if (poData) {
                    originalPoData.set(poId, poData); // Changed map name
                }
            });
        }
    }
}

function extractPoDataFromRow(row) {
    // Changed function name
    try {
        const invoiceElement = row.querySelector(".sort-invoice");
        const supplierElement = row.querySelector(".sort-supplier");
        const orderDateElement = row.querySelector(".sort-orderdate");
        const dueDateElement = row.querySelector(".sort-duedate");
        const amountElement = row.querySelector(".sort-amount");
        const paymentElement = row.querySelector(".sort-payment");
        const statusElement = row.querySelector(".sort-status");

        if (!invoiceElement) return null;

        return {
            id: parseInt(row.dataset.id),
            invoice: invoiceElement.textContent.trim(),
            supplier_name: supplierElement?.textContent?.trim() || "N/A",
            order_date: orderDateElement?.textContent?.trim() || "N/A",
            due_date: dueDateElement?.textContent?.trim() || "N/A",
            amount: amountElement?.textContent?.trim() || "N/A",
            payment_type: paymentElement?.textContent?.trim() || "N/A",
            status: statusElement?.textContent?.trim() || "N/A",
        };
    } catch (error) {
        console.error("Error extracting PO data:", error);
        return null;
    }
}

function restoreOriginalTable() {
    if (originalTableContent) {
        const tableBody = document.querySelector("table tbody");
        if (tableBody) {
            tableBody.innerHTML = originalTableContent;
            // Reinitialize bulk selection and restore states if applicable
            setTimeout(() => {
                if (bulkSelection) {
                    // Check if bulkSelection exists
                    bulkSelection.init(); // Reinitialize to re-attach listeners
                    bulkSelection.updateUI(); // Update UI based on current state
                }
            }, 100);
        }
    }
}

function performSearch(query) {
    storeOriginalTable();

    const tableBody = document.querySelector("table tbody");

    if (!query) {
        restoreOriginalTable();
        return;
    }

    tableBody.innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-3 text-muted">Searching...</p>
        </td></tr>
    `;

    const controller = new AbortController();
    currentRequest = controller;

    // !!! IMPORTANT: This URL needs to be implemented on the backend !!!
    fetch(`/admin/po/search?q=${encodeURIComponent(query)}`, {
        signal: controller.signal,
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            currentRequest = null;
            if (data.success) {
                renderSearchResults(data.pos); // Changed from data.products
            } else {
                showNoResults(data.message);
            }
        })
        .catch((error) => {
            currentRequest = null;
            if (error.name !== "AbortError") {
                showSearchError(error.message);
            }
        });
}

function renderSearchResults(pos) {
    // Changed parameter name
    const tableBody = document.querySelector("table tbody");
    if (!pos.length) {
        showNoResults();
        return;
    }

    // Store search results in originalPoData for future use (e.g., bulk operations)
    pos.forEach((po) => {
        originalPoData.set(po.id.toString(), po);
    });

    const formatCurrency = (amount) => {
        if (!amount) return "N/A";
        return new Intl.NumberFormat(currencySettings.locale, {
            style: "currency",
            currency: currencySettings.currency_code,
            maximumFractionDigits: currencySettings.decimal_places,
        }).format(amount);
    };

    const html = pos
        .map((po, index) => {
            // Assuming po object has id, invoice, supplier_name, order_date, due_date, total, payment_type, status
            const statusClass = po.status_class; // Assuming backend provides this
            const statusText = po.status_text; // Assuming backend provides this

            return `
            <tr class="table-row" data-id="${po.id}">
                <td>
                    <input type="checkbox" class="form-check-input row-checkbox" value="${
                        po.id
                    }">
                </td>
                <td class="sort-no no-print">${index + 1}</td>
                <td class="sort-invoice">${po.invoice}</td>
                <td class="sort-supplier">${po.supplier_name}</td>
                <td class="sort-orderdate">${po.order_date}</td>
                <td class="sort-duedate" data-date="${po.due_date_raw}">
                    ${po.due_date}
                </td>
                <td class="sort-amount" data-amount="${po.total_raw}">
                    ${formatCurrency(po.total_raw)}
                    <span class="raw-amount" style="display: none;">${
                        po.total_raw
                    }</span>
                </td>
                <td class="sort-payment no-print">${po.payment_type}</td>
                <td class="sort-status">
                    <span class="${statusClass}">
                        ${statusText}
                    </span>
                </td>
                <td class="no-print" style="text-align:center">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle align-text-top"
                            data-bs-toggle="dropdown" data-bs-boundary="viewport">
                            Actions
                        </button>
                        <div class="dropdown-menu">
                            <a href="javascript:void(0)" onclick="loadPoDetails('${
                                po.id
                            }')"
                               data-bs-toggle="modal" data-bs-target="#viewPoModal" class="dropdown-item">
                                <i class="ti ti-zoom-scan me-2"></i> View
                            </a>
                            <a href="/admin/po/edit/${
                                po.id
                            }" class="dropdown-item">
                                <i class="ti ti-edit me-2"></i> Edit
                            </a>
                            <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal"
                                    data-bs-target="#deleteModal" onclick="setDeleteFormAction('/admin/po/destroy/${
                                        po.id
                                    }')">
                                <i class="ti ti-trash me-2"></i> Delete
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
            `;
        })
        .join("");

    tableBody.innerHTML = html;

    // Reinitialize bulk selection with preserved states
    setTimeout(() => {
        if (bulkSelection) {
            // Check if bulkSelection exists
            bulkSelection.init(); // Reinitialize to re-attach listeners
            bulkSelection.updateUI(); // Update UI based on current state
        }
    }, 100);
}

function showNoResults(
    message = "No purchase orders found matching your search."
) {
    document.querySelector("table tbody").innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <i class="ti ti-search-off fs-1 text-muted"></i>
            <p class="mt-3 text-muted">${message}</p>
        </td></tr>
    `;

    // Hide bulk actions bar when no results
    const bulkActionsBar = document.getElementById("bulkActionsBar");
    if (bulkActionsBar) {
        // No need for selectedProductIds.size === 0 here
        bulkActionsBar.style.display = "none";
    }
}

function showSearchError(errorMessage = "Search error occurred.") {
    document.querySelector("table tbody").innerHTML = `
        <tr><td colspan="100%" class="text-center py-5">
            <i class="ti ti-alert-circle fs-1 text-danger"></i>
            <p class="mt-3 text-danger">${errorMessage}</p>
            <button class="btn btn-outline-primary mt-2" onclick="window.location.reload()">
                <i class="ti ti-refresh me-2"></i> Refresh
            </button>
        </td></tr>
    `;
}
// --- End Search Functionality ---

// Keep the existing DOMContentLoaded initialization
document.addEventListener("DOMContentLoaded", function () {
    if (sessionStorage.getItem('purchaseOrderBulkDeleteSuccess')) {
        showToast(
            "Success",
            sessionStorage.getItem('purchaseOrderBulkDeleteSuccess'),
            "success"
        );
        sessionStorage.removeItem('purchaseOrderBulkDeleteSuccess');
    }

    if (sessionStorage.getItem('purchaseOrderBulkMarkAsPaidSuccess')) {
        showToast(
            "Success",
            sessionStorage.getItem('purchaseOrderBulkMarkAsPaidSuccess'),
            "success"
        );
        sessionStorage.removeItem('purchaseOrderBulkMarkAsPaidSuccess');
    }

    // Add a small delay to ensure all elements are loaded
    setTimeout(() => {
        // Determine current page
        const pathname = window.location.pathname;

        try {
            if (pathname.includes("/admin/po/create")) {
                // Initialize create page functionality
                window.poApp = new PurchaseOrderCreate();
            } else if (
                pathname.includes("/admin/po/edit") ||
                (pathname.includes("/admin/po") &&
                    pathname.match(/\/\d+\/edit$/))
            ) {
                // Initialize edit page functionality
                window.poApp = new PurchaseOrderEdit();
            } else if (
                pathname.includes("/admin/po/modal") ||
                (pathname.includes("/admin/po") && pathname.match(/\/\d+$/)) ||
                pathname.includes("/admin/po/show")
            ) {
                // Initialize view functionality for modal or show pages
                window.poApp = new PurchaseOrderView();
            } else if (
                pathname === "/admin/po" ||
                pathname.includes("/admin/po?") ||
                pathname.includes("/admin/po/")
            ) {
                // Initialize view functionality even on index page for modals
                window.poApp = new PurchaseOrderView();

                // Initialize bulk selection only if there are items to select
                const rowCheckboxes =
                    document.querySelectorAll(".row-checkbox");
                if (
                    typeof PurchaseOrderBulkSelection !== "undefined" &&
                    rowCheckboxes.length > 0
                ) {
                    bulkSelection = new PurchaseOrderBulkSelection();
                } else {
                    // Ensure the bulk actions bar is hidden if no items
                    const bulkActionsBar =
                        document.getElementById("bulkActionsBar");
                    if (bulkActionsBar) {
                        bulkActionsBar.style.display = "none";
                    }
                }

                // Initialize search functionality for index page
                initializeSearch();
            }

            // Expose global utility functions that might be called from inline handlers
            // These are now handled by the initGlobalFunctions method in each class
        } catch (error) {
            console.error("Error initializing Purchase Order App:", error);
            // Fallback: ensure global functions are available even if class initialization fails
            window.setDeleteFormAction = function (url) {
                const deleteForm = document.getElementById("deleteForm");
                if (deleteForm) {
                    deleteForm.action = url;
                    console.log("Fallback: Delete form action set to:", url);
                } else {
                    console.error("Fallback: Delete form not found");
                }
            };

            window.loadPoDetails = function (id) {
                console.log("Fallback loadPoDetails called for ID:", id);
                // You can implement a fallback version here if needed
            };
        }
    }, 250);
});
