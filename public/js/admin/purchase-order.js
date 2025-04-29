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
        } else if (pathname.includes("/admin/po/edit")) {
            // Initialize edit page functionality
            window.poApp = new PurchaseOrderEdit();
            console.log("Purchase Order Edit App initialized");
        } else if (
            pathname.includes("/admin/po") &&
            (pathname.match(/\/\d+$/) || pathname.includes("/show"))
        ) {
            // For view page, we don't need interactive functionality
            console.log("Purchase Order View page detected");
        }
    } catch (error) {
        console.error("Error initializing Purchase Order App:", error);
    }
});
