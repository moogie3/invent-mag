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
}

/**
 * SalesOrderCreate - Manages the sales order creation functionality
 * Extends core module functionality for create page
 */
class SalesOrderCreate extends SalesOrderModule {
    constructor(config = {}) {
        super(config);

        // Store DOM elements
        this.elements = {
            orderDate: document.getElementById("order_date"),
            dueDate: document.getElementById("due_date"),
            customerSelect: document.getElementById("customer_id"),
            productSelect: document.getElementById("product_id"),
            customerPriceField: document.getElementById("customer_price"),
            pastPriceField: document.getElementById("past_price"),
            priceField: document.getElementById("price"),
            sellingPriceField: document.getElementById("selling_price"),
            quantity: document.getElementById("quantity"),
            discount: document.getElementById("discount"),
            discountType: document.getElementById("discount_type"),
            addProductBtn: document.getElementById("addProduct"),
            clearProductsBtn: document.getElementById("clearProducts"),
            productTableBody: document.getElementById("productTableBody"),
            productsField: document.getElementById("productsField"),
            discountTotalValue: document.getElementById("discountTotalValue"),
            discountTotalType: document.getElementById("discountTotalType"),
            applyTotalDiscount: document.getElementById("applyTotalDiscount"),
            form: document.querySelector("form"),
        };

        // Data storage
        this.products = JSON.parse(localStorage.getItem("salesProducts")) || [];
        this.orderDiscount = 0;
        this.orderDiscountType = "fixed";

        // Initialize flatpickr for date fields
        this.initFlatpickr(this.elements.orderDate, this.elements.dueDate);

        // Initialize event listeners
        this.initEventListeners();

        // Initial render
        this.renderTable();
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
        this.elements.productSelect.addEventListener("change", () =>
            this.updateProductPrices()
        );
        this.elements.customerSelect.addEventListener("change", () =>
            this.fetchCustomerPastPrice()
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

    saveToLocalStorage() {
        localStorage.setItem("salesProducts", JSON.stringify(this.products));
    }

    addProduct() {
        const productId = this.elements.productSelect.value;
        const productName =
            this.elements.productSelect.options[
                this.elements.productSelect.selectedIndex
            ].text;
        const quantity = parseInt(this.elements.quantity.value);
        const price = parseFloat(this.elements.customerPriceField.value);
        const discount = parseFloat(this.elements.discount.value) || 0;
        const discountType = this.elements.discountType.value;

        // Calculate the total using the correct discount type
        const total = this.calculateTotal(
            price,
            quantity,
            discount,
            discountType
        );

        this.products.push({
            id: productId,
            name: productName,
            quantity,
            price,
            discount,
            discountType,
            total,
        });

        this.saveToLocalStorage();
        this.renderTable();

        // Reset form fields
        this.elements.productSelect.value = "";
        this.elements.quantity.value = "";
        this.elements.customerPriceField.value = "";
        this.elements.discount.value = "";
        this.elements.priceField.value = "";
        this.elements.sellingPriceField.value = "";
        this.elements.pastPriceField.value = "";
    }

    clearProducts() {
        this.products = [];
        this.saveToLocalStorage();
        this.renderTable();
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

        localStorage.removeItem("salesProducts");
    }

    renderTable() {
        this.elements.productTableBody.innerHTML = "";

        this.products.forEach((product, index) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${product.name}</td>
                <td>
                    <input type="number" class="form-control quantity-input"
                        value="${product.quantity}" data-id="${
                product.id
            }" min="1" style="width:80px;" />
                </td>
                <td>
                    <input type="number" class="form-control price-input"
                        value="${product.price}" data-id="${
                product.id
            }" min="0" style="width:100px;" />
                </td>
                <td>
                    <div class="input-group" style="width:200px;">
                        <input type="number" class="form-control discount-input"
                            value="${product.discount}" data-id="${
                product.id
            }" min="0" />
                        <select class="form-select discount-type" data-id="${
                            product.id
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
                    <button type="button" class="btn btn-danger btn-sm removeProduct" data-id="${
                        product.id
                    }">Remove</button>
                </td>
            `;
            this.elements.productTableBody.appendChild(row);
        });

        this.updateTotalPrice();
        this.attachTableEventListeners();
    }

    attachTableEventListeners() {
        // Remove product
        document.querySelectorAll(".removeProduct").forEach((button) => {
            button.addEventListener("click", (e) => {
                const id = e.target.dataset.id;
                this.products = this.products.filter((p) => p.id != id);
                this.saveToLocalStorage();
                this.renderTable();
            });
        });

        // Quantity change
        document.querySelectorAll(".quantity-input").forEach((input) => {
            input.addEventListener("input", (e) => {
                const id = e.target.dataset.id;
                const value = parseInt(e.target.value) || 1;
                const product = this.products.find((p) => p.id == id);

                if (product) {
                    product.quantity = value;
                    product.total = this.calculateTotal(
                        product.price,
                        product.quantity,
                        product.discount,
                        product.discountType
                    );

                    this.saveToLocalStorage();
                    e.target
                        .closest("tr")
                        .querySelector(".product-total").innerText =
                        this.formatCurrency(product.total);
                    this.updateTotalPrice();
                }
            });
        });

        // Price change
        document.querySelectorAll(".price-input").forEach((input) => {
            input.addEventListener("input", (e) => {
                const id = e.target.dataset.id;
                const value = parseFloat(e.target.value) || 0;
                const product = this.products.find((p) => p.id == id);

                if (product) {
                    product.price = value;
                    product.total = this.calculateTotal(
                        product.price,
                        product.quantity,
                        product.discount,
                        product.discountType
                    );

                    this.saveToLocalStorage();
                    e.target
                        .closest("tr")
                        .querySelector(".product-total").innerText =
                        this.formatCurrency(product.total);
                    this.updateTotalPrice();
                }
            });
        });

        // Discount change
        document.querySelectorAll(".discount-input").forEach((input) => {
            input.addEventListener("input", (e) => {
                const id = e.target.dataset.id;
                const value = parseFloat(e.target.value) || 0;
                const product = this.products.find((p) => p.id == id);

                if (product) {
                    product.discount = value;
                    product.total = this.calculateTotal(
                        product.price,
                        product.quantity,
                        product.discount,
                        product.discountType
                    );

                    this.saveToLocalStorage();
                    e.target
                        .closest("tr")
                        .querySelector(".product-total").innerText =
                        this.formatCurrency(product.total);
                    this.updateTotalPrice();
                }
            });
        });

        // Discount type change
        document.querySelectorAll(".discount-type").forEach((select) => {
            select.addEventListener("change", (e) => {
                const id = e.target.dataset.id;
                const value = e.target.value;
                const product = this.products.find((p) => p.id == id);

                if (product) {
                    product.discountType = value;
                    product.total = this.calculateTotal(
                        product.price,
                        product.quantity,
                        product.discount,
                        product.discountType
                    );

                    this.saveToLocalStorage();
                    e.target
                        .closest("tr")
                        .querySelector(".product-total").innerText =
                        this.formatCurrency(product.total);
                    this.updateTotalPrice();
                }
            });
        });
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
        } else {
            return;
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
 * Initialize appropriate module based on the current page
 */
document.addEventListener("DOMContentLoaded", function () {
    // Determine current page
    const pathname = window.location.pathname;

    try {
        if (pathname.includes("/admin/sales/create")) {
            // Initialize create page functionality
            window.salesApp = new SalesOrderCreate();
            console.log("Sales Order Create App initialized");
        } else if (pathname.includes("/admin/sales/edit")) {
            // Initialize edit page functionality
            window.salesApp = new SalesOrderEdit();
            console.log("Sales Order Edit App initialized");
        } else if (
            pathname.includes("/admin/sales") &&
            (pathname.match(/\/\d+$/) || pathname.includes("/show"))
        ) {
            // For view page, we don't need interactive functionality
            console.log("Sales Order View page detected");
        }
    } catch (error) {
        console.error("Error initializing Sales Order App:", error);
    }
});
