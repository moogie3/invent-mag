/**
 * PurchaseOrderModule - Core module for purchase order functionality
 * Provides shared utility functions and calculations for all PO related pages
 */
class PurchaseOrderModule {
    constructor(config = {}) {
        this.config = {
            currency: 'IDR',
            locale: 'id-ID',
            ...config
        };
    }

    /**
     * Format currency amount according to locale settings
     * @param {number} amount - Amount to format
     * @returns {string} Formatted currency string
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat(this.config.locale, {
            style: 'currency',
            currency: this.config.currency,
            maximumFractionDigits: 0
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
        const discountPerUnit = discountType === 'percentage' ? (price * discount / 100) : discount;
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
        return discountType === 'percentage' ? subtotal * discountValue / 100 : discountValue;
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
            orderDate: document.getElementById('order_date'),
            dueDate: document.getElementById('due_date'),
            supplierSelect: document.getElementById('supplier_id'),
            productSelect: document.getElementById('product_id'),
            lastPrice: document.getElementById('last_price'),
            quantity: document.getElementById('quantity'),
            newPrice: document.getElementById('new_price'),
            discount: document.getElementById('discount'),
            discountType: document.getElementById('discount_type'),
            addProductBtn: document.getElementById('addProduct'),
            clearProductsBtn: document.getElementById('clearProducts'),
            productTableBody: document.getElementById('productTableBody'),
            productsField: document.getElementById('productsField'),
            discountTotalValue: document.getElementById('discountTotalValue'),
            discountTotalType: document.getElementById('discountTotalType'),
            applyTotalDiscount: document.getElementById('applyTotalDiscount'),
            invoice: document.getElementById('invoice'),
            form: document.getElementById('invoiceForm')
        };

        // Data storage
        this.products = [];
        this.orderDiscount = { value: 0, type: 'fixed' };

        // Check if we need to clear storage after submission
        this.checkSessionState();

        // Initialize event listeners
        this.initEventListeners();

        // Load data from storage
        this.loadFromStorage();

        // Initial render
        this.renderTable();
    }

    checkSessionState() {
        if (sessionStorage.getItem('poJustSubmitted') === 'true') {
            localStorage.removeItem('poProducts');
            localStorage.removeItem('poOrderDiscount');
            sessionStorage.removeItem('poJustSubmitted');
        }
    }

    initEventListeners() {
        // Due date calculation
        this.elements.supplierSelect.addEventListener('change', () => this.calculateDueDate());
        this.elements.orderDate.addEventListener('change', () => this.calculateDueDate());

        // Product selection
        this.elements.productSelect.addEventListener('change', () => this.updateProductPrice());

        // Add product button
        this.elements.addProductBtn.addEventListener('click', () => this.addProduct());

        // Clear products
        this.elements.clearProductsBtn.addEventListener('click', () => this.clearProducts());

        // Apply order discount
        this.elements.applyTotalDiscount.addEventListener('click', () => this.applyOrderDiscount());

        // Form submission
        this.elements.form.addEventListener('submit', () => {
            sessionStorage.setItem('poJustSubmitted', 'true');
        });
    }

    loadFromStorage() {
        this.products = JSON.parse(localStorage.getItem('poProducts')) || [];

        const savedOrderDiscount = JSON.parse(localStorage.getItem('poOrderDiscount'));
        if (savedOrderDiscount) {
            this.orderDiscount = savedOrderDiscount;
            this.elements.discountTotalValue.value = this.orderDiscount.value;
            this.elements.discountTotalType.value = this.orderDiscount.type;
        }
    }

    saveToStorage() {
        localStorage.setItem('poProducts', JSON.stringify(this.products));
        localStorage.setItem('poOrderDiscount', JSON.stringify(this.orderDiscount));
    }

    calculateDueDate() {
        const orderDateValue = this.elements.orderDate.value;
        const selectedOption = this.elements.supplierSelect.options[this.elements.supplierSelect.selectedIndex];

        if (!orderDateValue || !selectedOption) return;

        const orderDate = new Date(orderDateValue);
        const paymentTerms = selectedOption.dataset.paymentTerms;

        if (paymentTerms) {
            orderDate.setDate(orderDate.getDate() + parseInt(paymentTerms));
            this.elements.dueDate.value = orderDate.toISOString().split('T')[0];
        }
    }

    updateProductPrice() {
        const selectedOption = this.elements.productSelect.options[this.elements.productSelect.selectedIndex];
        this.elements.lastPrice.value = selectedOption.getAttribute('data-price') || '';
    }

    updateTotalPrice() {
        let subtotal = this.products.reduce((sum, product) => sum + product.total, 0);
        const orderDiscountAmount = this.calculateDiscount(subtotal, this.orderDiscount.value, this.orderDiscount.type);
        const finalTotal = subtotal - orderDiscountAmount;

        // Update UI
        document.getElementById('subtotal').innerText = this.formatCurrency(subtotal);
        document.getElementById('orderDiscountTotal').innerText = this.formatCurrency(orderDiscountAmount);
        document.getElementById('finalTotal').innerText = this.formatCurrency(finalTotal);

        // Update form fields
        document.getElementById('totalDiscountInput').value = 0;
        document.getElementById('orderDiscountInput').value = this.orderDiscount.value;
        document.getElementById('orderDiscountTypeInput').value = this.orderDiscount.type;
        this.elements.productsField.value = JSON.stringify(this.products);
    }

    addProduct() {
        // Validate inputs
        if (!this.validateProductForm()) return;

        const productId = this.elements.productSelect.value;
        const productName = this.elements.productSelect.options[this.elements.productSelect.selectedIndex].text;
        const quantity = parseInt(this.elements.quantity.value);
        const price = parseFloat(this.elements.newPrice.value);
        const discount = parseFloat(this.elements.discount.value) || 0;
        const discountType = this.elements.discountType.value;

        // Generate unique ID and calculate total
        const uniqueId = `${Date.now()}-${Math.random().toString(36).substring(2, 7)}`;
        const total = this.calculateTotal(price, quantity, discount, discountType);

        // Add to products array
        this.products.push({
            id: productId,
            uniqueId,
            name: productName,
            quantity,
            price,
            discount,
            discountType,
            total
        });

        // Save and update UI
        this.saveToStorage();
        this.renderTable();
        this.clearProductForm();
    }

    validateProductForm() {
        const productId = this.elements.productSelect.value;

        if (!productId) {
            alert('Please select a product');
            return false;
        }

        return true;
    }

    clearProductForm() {
        this.elements.productSelect.value = '';
        this.elements.quantity.value = '';
        this.elements.newPrice.value = '';
        this.elements.discount.value = '';
        this.elements.lastPrice.value = '';
    }

    clearProducts() {
        this.products = [];
        this.orderDiscount = { value: 0, type: 'fixed' };

        localStorage.removeItem('poProducts');
        localStorage.removeItem('poOrderDiscount');

        this.elements.discountTotalValue.value = 0;
        this.elements.discountTotalType.value = 'fixed';

        this.renderTable();
    }

    applyOrderDiscount() {
        this.orderDiscount = {
            value: parseFloat(this.elements.discountTotalValue.value) || 0,
            type: this.elements.discountTotalType.value
        };

        this.saveToStorage();
        this.updateTotalPrice();
    }

    attachTableEventListeners() {
        // Event delegation for table rows
        this.elements.productTableBody.addEventListener('input', (event) => {
            const target = event.target;
            const uniqueId = target.dataset.uniqueId;
            if (!uniqueId) return;

            const product = this.products.find(p => p.uniqueId === uniqueId);
            if (!product) return;

            if (target.classList.contains('quantity-input')) {
                product.quantity = parseInt(target.value) || 1;
            } else if (target.classList.contains('price-input')) {
                product.price = parseFloat(target.value) || 0;
            } else if (target.classList.contains('discount-input')) {
                product.discount = parseFloat(target.value) || 0;
            }

            product.total = this.calculateTotal(
                product.price,
                product.quantity,
                product.discount,
                product.discountType
            );

            // Update the row's total display
            target.closest('tr').querySelector('.product-total').innerText = this.formatCurrency(product.total);

            this.saveToStorage();
            this.updateTotalPrice();
        });

        // Handle discount type changes
        this.elements.productTableBody.addEventListener('change', (event) => {
            const target = event.target;
            if (!target.classList.contains('discount-type')) return;

            const uniqueId = target.dataset.uniqueId;
            const product = this.products.find(p => p.uniqueId === uniqueId);
            if (!product) return;

            product.discountType = target.value;
            product.total = this.calculateTotal(
                product.price,
                product.quantity,
                product.discount,
                product.discountType
            );

            target.closest('tr').querySelector('.product-total').innerText = this.formatCurrency(product.total);

            this.saveToStorage();
            this.updateTotalPrice();
        });

        // Handle remove buttons
        this.elements.productTableBody.addEventListener('click', (event) => {
            const target = event.target.closest('.removeProduct');
            if (!target) return;

            const uniqueId = target.dataset.uniqueId;
            this.products = this.products.filter(p => p.uniqueId !== uniqueId);

            this.saveToStorage();
            this.renderTable();
        });
    }

    renderTable() {
        // Clear table body
        this.elements.productTableBody.innerHTML = '';

        // Add rows for each product
        this.products.forEach((product, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${product.name}</td>
                <td>
                    <input type="number" class="form-control quantity-input"
                        value="${product.quantity}" data-unique-id="${product.uniqueId}" min="1" style="width:80px;" />
                </td>
                <td>
                    <input type="number" class="form-control price-input"
                        value="${product.price}" data-unique-id="${product.uniqueId}" min="0" style="width:100px;" />
                </td>
                <td>
                    <div class="input-group" style="width:200px;">
                        <input type="number" class="form-control discount-input"
                            value="${product.discount}" data-unique-id="${product.uniqueId}" min="0" />
                        <select class="form-select discount-type" data-unique-id="${product.uniqueId}">
                            <option value="fixed" ${product.discountType === 'fixed' ? 'selected' : ''}>Rp</option>
                            <option value="percentage" ${product.discountType === 'percentage' ? 'selected' : ''}>%</option>
                        </select>
                    </div>
                </td>
                <td class="product-total">${this.formatCurrency(product.total)}</td>
                <td style="text-align:center">
                    <button type="button" class="btn btn-danger btn-icon removeProduct" data-unique-id="${product.uniqueId}" title="Remove">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            `;
            this.elements.productTableBody.appendChild(row);
        });

        // Update totals
        this.updateTotalPrice();

        // Attach event listeners
        this.attachTableEventListeners();
    }
}

/**
 * PurchaseOrderEdit - Manages the purchase order edit functionality
 * Extends core module for edit page
 */
class PurchaseOrderEdit extends PurchaseOrderModule {
    constructor(config = {}) {
        super(config);
        this.initEventListeners();
        this.updateDisplayTotals();
    }

    initEventListeners() {
        // Event listeners for quantity, price, discount inputs
        document.querySelectorAll(".quantity-input, .price-input, .discount-input, .discount-type-input")
            .forEach(input => {
                input.addEventListener("input", () => this.updateDisplayTotals());
            });

        document.querySelectorAll(".discount-type-input").forEach(select => {
            select.addEventListener("change", () => this.updateDisplayTotals());
        });

        // Event listeners for order discount fields
        const discountTotalValue = document.getElementById("discountTotalValue");
        const discountTotalType = document.getElementById("discountTotalType");

        if (discountTotalValue) {
            discountTotalValue.addEventListener("input", () => this.updateDisplayTotals());
        }

        if (discountTotalType) {
            discountTotalType.addEventListener("change", () => this.updateDisplayTotals());
        }
    }

    updateDisplayTotals() {
        let subtotal = 0;

        // Calculate product totals
        document.querySelectorAll("tbody tr").forEach(row => {
            const quantity = parseFloat(row.querySelector(".quantity-input")?.value) || 0;
            const price = parseFloat(row.querySelector(".price-input")?.value) || 0;
            const discount = parseFloat(row.querySelector(".discount-input")?.value) || 0;
            const discountType = row.querySelector(".discount-type-input")?.value || 'percentage';

            // Calculate amount using the same method as in calculateTotal
            const finalAmount = this.calculateTotal(price, quantity, discount, discountType);

            // Update row amount display
            const amountInput = row.querySelector(".amount-input");
            if (amountInput) {
                amountInput.value = Math.floor(finalAmount);
            }

            subtotal += finalAmount;
        });

        // Calculate order discount
        const discountTotalValue = parseFloat(document.getElementById("discountTotalValue")?.value) || 0;
        const discountTotalType = document.getElementById("discountTotalType")?.value || 'fixed';
        const orderDiscountAmount = this.calculateDiscount(subtotal, discountTotalValue, discountTotalType);

        // Calculate final total
        const finalTotal = subtotal - orderDiscountAmount;

        // Update totals display
        document.getElementById("subtotal").innerText = this.formatCurrency(Math.floor(subtotal));
        document.getElementById("orderDiscountTotal").innerText = this.formatCurrency(Math.floor(orderDiscountAmount));
        document.getElementById("finalTotal").innerText = this.formatCurrency(Math.floor(finalTotal));
    }
}

/**
 * Initialize appropriate module based on the current page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Determine current page
    const pathname = window.location.pathname;

    try {
        if (pathname.includes('/admin/po/create')) {
            // Initialize create page functionality
            window.poApp = new PurchaseOrderCreate();
            console.log('Purchase Order Create App initialized');
        }
        else if (pathname.includes('/admin/po/edit')) {
            // Initialize edit page functionality
            window.poApp = new PurchaseOrderEdit();
            console.log('Purchase Order Edit App initialized');
        }
        else if (pathname.includes('/admin/po') && (pathname.match(/\/\d+$/) || pathname.includes('/show'))) {
            // For view page, we don't need interactive functionality
            console.log('Purchase Order View page detected');
        }
    } catch (error) {
        console.error('Error initializing Purchase Order App:', error);
    }
});
