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
            this.elements.product_id.addEventListener("change", () =>
                this.updateProductPrice()
            );
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

        // Update UI
        this.renderTable();
        this.clearProductForm();
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
            product.quantity = parseInt(target.value) || 1;
        } else if (target.classList.contains("price-input")) {
            product.price = parseFloat(target.value) || 0;
        } else if (target.classList.contains("discount-input")) {
            product.discount = parseFloat(target.value) || 0;
        }

        this.updateProductInTable(product, target);
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
                    <td>${index + 1}</td>
                    <td>${product.name}</td>
                    <td>
                        <input type="number" class="form-control quantity-input"
                            value="${product.quantity}" data-unique-id="${
                product.uniqueId
            }"
                            min="1" style="width:80px;" />
                    </td>
                    <td>
                        <input type="number" class="form-control price-input"
                            value="${product.price}" data-unique-id="${
                product.uniqueId
            }"
                            min="0" style="width:100px;" />
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
                        <button type="button" class="btn btn-danger btn-icon removeProduct"
                            data-unique-id="${product.uniqueId}" title="Remove">
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
            amountInputs: document.querySelectorAll(".amount-input"),
        };
    }

    initEventListeners() {
        // Add listeners for item inputs
        ["quantityInputs", "priceInputs", "discountInputs"].forEach(
            (inputType) => {
                this.elements[inputType].forEach((input) => {
                    const eventType =
                        inputType === "discountTypeInputs" ? "change" : "input";
                    input.addEventListener(eventType, () => {
                        this.updateItemAmount(input.dataset.itemId);
                    });
                });
            }
        );

        this.elements.discountTypeInputs.forEach((select) => {
            select.addEventListener("change", () => {
                this.updateItemAmount(select.dataset.itemId);
            });
        });

        // Order-level discount listeners
        if (this.elements.discountTotalValue) {
            this.elements.discountTotalValue.addEventListener("input", () =>
                this.calculateOrderTotal()
            );
        }
        if (this.elements.discountTotalType) {
            this.elements.discountTotalType.addEventListener("change", () =>
                this.calculateOrderTotal()
            );
        }
    }

    updateItemAmount(itemId) {
        const elements = {
            quantity: document.querySelector(
                `.quantity-input[data-item-id="${itemId}"]`
            ),
            price: document.querySelector(
                `.price-input[data-item-id="${itemId}"]`
            ),
            discount: document.querySelector(
                `.discount-input[data-item-id="${itemId}"]`
            ),
            discountType: document.querySelector(
                `.discount-type-input[data-item-id="${itemId}"]`
            ),
            amount: document.querySelector(
                `.amount-input[data-item-id="${itemId}"]`
            ),
        };

        // Check if all elements exist
        const missingElements = Object.keys(elements).filter(
            (key) => !elements[key]
        );
        if (missingElements.length > 0) {
            console.error(
                `Missing elements for item ${itemId}:`,
                missingElements
            );
            return;
        }

        // Get values
        const quantity = parseInt(elements.quantity.value) || 0;
        const price = parseFloat(elements.price.value) || 0;
        const discount = parseFloat(elements.discount.value) || 0;
        const discountType = elements.discountType.value;

        // Calculate total
        const total = this.calculateTotal(
            price,
            quantity,
            discount,
            discountType
        );
        elements.amount.value = Math.round(total);

        this.calculateOrderTotal();
    }

    calculateAllAmounts() {
        this.elements.quantityInputs.forEach((input) => {
            const itemId = input.dataset.itemId;
            if (itemId) {
                this.updateItemAmount(itemId);
            }
        });
        this.calculateOrderTotal();
    }

    calculateOrderTotal() {
        // Calculate subtotal
        let subtotal = 0;
        this.elements.amountInputs.forEach((input) => {
            subtotal += parseFloat(input.value) || 0;
        });

        // Get order discount values
        const discountValue =
            parseFloat(this.elements.discountTotalValue?.value) || 0;
        const discountType = this.elements.discountTotalType?.value || "fixed";

        // Calculate order discount and final total
        const orderDiscountAmount = this.calculateDiscount(
            subtotal,
            discountValue,
            discountType
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
        console.log("Setting delete form action to:", url); // Debug log
        if (this.elements.deleteForm) {
            this.elements.deleteForm.action = url;
            console.log("Form action set successfully"); // Debug log
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
            console.log("Bulk selection already initialized");
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

            if (
                !this.selectAllCheckbox ||
                this.rowCheckboxes.length === 0 ||
                !this.bulkActionsBar ||
                !this.selectedCount
            ) {
                if (attempts < maxAttempts) {
                    console.log(
                        `Bulk selection init attempt ${attempts}/${maxAttempts} - retrying...`
                    );
                    setTimeout(tryInit, 300);
                    return;
                }

                console.warn(
                    "Bulk selection elements not found after",
                    maxAttempts,
                    "attempts"
                );
                return;
            }

            this.setupEventListeners();
            this.updateUI();
            this.isInitialized = true;
            console.log("Bulk selection initialized successfully");
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

    updateBulkActionsBar() {
        const checkedCount = document.querySelectorAll(
            ".row-checkbox:checked"
        ).length;

        if (checkedCount > 0) {
            this.bulkActionsBar.style.display = "block";
            this.selectedCount.textContent = checkedCount;
        } else {
            this.bulkActionsBar.style.display = "none";
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

window.bulkDeletePO = function () {
    console.log("bulkDeletePO function called");

    const selected = getSelectedIds();
    console.log("Selected IDs:", selected);

    // Validate selection
    if (!selected || selected.length === 0) {
        showToast(
            "Warning",
            "Please select at least one purchase order to delete.",
            "warning"
        );
        return;
    }

    // Update modal with selection count
    const bulkDeleteCount = document.getElementById("bulkDeleteCount");
    if (bulkDeleteCount) {
        bulkDeleteCount.textContent = selected.length;
    }

    // Show confirmation modal
    const bulkDeleteModal = new bootstrap.Modal(
        document.getElementById("bulkDeleteModal")
    );
    bulkDeleteModal.show();

    // Handle confirmation button
    const confirmBtn = document.getElementById("confirmBulkDeleteBtn");
    if (confirmBtn) {
        // Remove any existing event listeners by cloning the button
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener("click", function () {
            console.log("Confirm button clicked");
            performBulkDelete(selected, this, bulkDeleteModal);
        });
    }
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
    console.log("bulkMarkAsPaidPO function called");

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

    console.log("Final selected IDs:", finalSelected);

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
    console.log("confirmBulkMarkAsPaidPO called with IDs:", selectedIds);

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
                // Close modal
                modal.hide();

                // Show success message
                showToast(
                    "Success",
                    `${
                        data.updated_count || selectedIds.length
                    } purchase order(s) marked as paid successfully!`,
                    "success"
                );

                // Clear selection
                if (typeof clearSelection === "function") {
                    clearSelection();
                } else if (
                    bulkSelection &&
                    typeof bulkSelection.clearSelection === "function"
                ) {
                    bulkSelection.clearSelection();
                }

                // Reload page after short delay
                setTimeout(() => {
                    location.reload();
                }, 1000);
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
        });
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
function showToast(title, message, type = "info", duration = 4000) {
    // Create a toast container if it doesn't exist
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
        toastContainer = document.createElement("div");
        toastContainer.id = "toast-container";
        toastContainer.className =
            "toast-container position-fixed bottom-0 end-0 p-3";
        toastContainer.style.zIndex = "1050";
        document.body.appendChild(toastContainer);

        // Add animation styles once
        if (!document.getElementById("toast-styles")) {
            const style = document.createElement("style");
            style.id = "toast-styles";
            style.textContent = `
                    .toast-enter {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    .toast-show {
                        transform: translateX(0);
                        opacity: 1;
                        transition: transform 0.3s ease, opacity 0.3s ease;
                    }
                    .toast-exit {
                        transform: translateX(100%);
                        opacity: 0;
                        transition: transform 0.3s ease, opacity 0.3s ease;
                    }
                `;
            document.head.appendChild(style);
        }
    }

    // Create toast element
    const toast = document.createElement("div");
    toast.className =
        "toast toast-enter align-items-center text-white bg-" +
        getToastColor(type) +
        " border-0";
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");

    toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong>: ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

    toastContainer.appendChild(toast);

    // Force reflow to ensure animation works
    void toast.offsetWidth;

    // Show with animation
    toast.classList.add("toast-show");

    // Initialize Bootstrap toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: duration,
    });
    bsToast.show();

    // Handle close button clicks
    const closeButton = toast.querySelector(".btn-close");
    closeButton.addEventListener("click", () => {
        hideToast(toast);
    });

    // Auto hide after duration
    const hideTimeout = setTimeout(() => {
        hideToast(toast);
    }, duration);

    // Store timeout on toast element for cleanup
    toast._hideTimeout = hideTimeout;
}

// Helper function to hide toast with animation
function hideToast(toast) {
    // Clear any existing timeout
    if (toast._hideTimeout) {
        clearTimeout(toast._hideTimeout);
    }

    // Add exit animation
    toast.classList.remove("toast-show");
    toast.classList.add("toast-exit");

    // Remove after animation completes
    setTimeout(() => {
        toast.remove();
    }, 300);
}

// Helper function to get the appropriate Bootstrap color class
function getToastColor(type) {
    switch (type) {
        case "success":
            return "success";
        case "error":
            return "danger";
        case "warning":
            return "warning";
        default:
            return "info";
    }
}

// Keep the existing DOMContentLoaded initialization
document.addEventListener("DOMContentLoaded", function () {
    // Add a small delay to ensure all elements are loaded
    setTimeout(() => {
        // Determine current page
        const pathname = window.location.pathname;

        try {
            if (pathname.includes("/admin/po/create")) {
                // Initialize create page functionality
                window.poApp = new PurchaseOrderCreate();
                console.log("Purchase Order Create App initialized");
            } else if (
                pathname.includes("/admin/po/edit") ||
                (pathname.includes("/admin/po") &&
                    pathname.match(/\/\d+\/edit$/))
            ) {
                // Initialize edit page functionality
                window.poApp = new PurchaseOrderEdit();
                console.log("Purchase Order Edit App initialized");
            } else if (
                pathname.includes("/admin/po/modal") ||
                (pathname.includes("/admin/po") && pathname.match(/\/\d+$/)) ||
                pathname.includes("/admin/po/show")
            ) {
                // Initialize view functionality for modal or show pages
                window.poApp = new PurchaseOrderView();
                console.log("Purchase Order View App initialized");
            } else if (
                pathname === "/admin/po" ||
                pathname.includes("/admin/po?") ||
                pathname.includes("/admin/po/")
            ) {
                // Initialize bulk selection for index page
                console.log("Initializing Purchase Order Index page...");

                // Initialize view functionality even on index page for modals
                window.poApp = new PurchaseOrderView();

                // Also initialize bulk selection if the class exists
                if (typeof PurchaseOrderBulkSelection !== "undefined") {
                    bulkSelection = new PurchaseOrderBulkSelection();
                    console.log(
                        "Purchase Order Index bulk selection initialized"
                    );
                }

                console.log("Purchase Order Index page initialized");
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
