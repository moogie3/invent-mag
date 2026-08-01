import { SalesOrderModule } from "../common/SalesOrderModule.js";
import { formatCurrency } from "../../../../utils/currencyFormatter.js";
import { validateForm } from "../../../utils/formValidator.js";

export class SalesOrderCreate extends SalesOrderModule {
    formatPrice(amount) {
        const num = parseFloat(amount) || 0;
        return 'Rp ' + num.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }
    constructor(config = {}) {
        super(config);

        this.elements = this.initializeElements();

        this.products = [];
        this.orderDiscount = 0;
        this.orderDiscountType = "fixed";
        this.sessionJustSubmitted = false;
        this.currentStock = 0;
        this.customerCreditUsed = 0;

        this.checkSessionState();

        this.initFlatpickr(this.elements.orderDate, this.elements.dueDate);

        this.initEventListeners();

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
            "customerCreditAmount",
            "customerCreditInput",
        ];

        const elements = {};
        elementIds.forEach((id) => {
            elements[id] = this.safeGetElement(id);
        });

        // Include explicit element IDs for form validation
        const formId = "invoiceForm";

        elements.form = document.getElementById(formId);
        elements.invoiceForm = elements.form;

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

        // Ensure form exists before returning
        if (!elements.form) {
            console.warn("Form element not found in Sales Order Create page");
        }

        return elements;
    }

    checkSessionState() {
        if (this.sessionJustSubmitted) {
            this.products = [];
            this.orderDiscount = 0;
            this.orderDiscountType = "fixed";
            this.sessionJustSubmitted = false;
        }
    }

    initEventListeners() {
        if (this.elements.customerSelect) {
            this.elements.customerSelect.addEventListener("change", () =>
                this.calculateDueDate(),
            );
        }

        if (this.elements.orderDate) {
            if (this.elements.orderDate._flatpickr) {
                this.elements.orderDate._flatpickr.config.onChange.push(() =>
                    this.calculateDueDate(),
                );
            } else {
                this.elements.orderDate.addEventListener("change", () =>
                    this.calculateDueDate(),
                );
            }
        }

        if (this.elements.productSelect) {
            this.elements.productSelect.addEventListener("change", () => {
                this.updateProductPrices();
                this.updateStockDisplay();
            });
        }

        if (this.elements.customerSelect) {
            this.elements.customerSelect.addEventListener("change", () =>
                this.fetchCustomerPastPrice(),
            );
        }

        if (this.elements.quantity) {
            this.elements.quantity.addEventListener("input", (e) => {
                const max = parseInt(this.elements.quantity.max) || 0;
                let value = parseInt(this.elements.quantity.value) || 0;
                const originalValue = value;

                if (value > max && max > 0) {
                    this.elements.quantity.value = max;
                    value = max;
                }

                if (max > 0 && originalValue > max) {
                    this.elements.quantity.classList.add("is-invalid");
                    const warningEl = document.getElementById("quantity_warning");
                    if (warningEl) {
                        warningEl.style.display = "block";
                    }
                    this.disableAddButton();
                } else {
                    this.elements.quantity.classList.remove("is-invalid");
                    const warningEl = document.getElementById("quantity_warning");
                    if (warningEl) {
                        warningEl.style.display = "none";
                    }
                    this.enableAddButton();
                }
            });
            this.elements.quantity.addEventListener("change", () =>
                this.validateQuantity(),
            );
            this.elements.quantity.addEventListener("blur", () => {
                const max = parseInt(this.elements.quantity.max) || 0;
                let value = parseInt(this.elements.quantity.value) || 0;
                if (value > max && max > 0) {
                    this.elements.quantity.value = max;
                    this.elements.quantity.classList.add("is-invalid");
                    const warningEl = document.getElementById("quantity_warning");
                    if (warningEl) {
                        warningEl.style.display = "block";
                    }
                } else if (value > 0 && max > 0 && value > max) {
                    this.elements.quantity.classList.add("is-invalid");
                    const warningEl = document.getElementById("quantity_warning");
                    if (warningEl) {
                        warningEl.style.display = "block";
                    }
                } else if (value > 0) {
                    this.elements.quantity.classList.remove("is-invalid");
                    const warningEl = document.getElementById("quantity_warning");
                    if (warningEl) {
                        warningEl.style.display = "none";
                    }
                }
            });
        }

        if (this.elements.addProductBtn) {
            this.elements.addProductBtn.addEventListener("click", () =>
                this.addProduct(),
            );
        }

        if (this.elements.clearProductsBtn) {
            this.elements.clearProductsBtn.addEventListener("click", () =>
                this.clearProducts(),
            );
        }

        if (this.elements.applyTotalDiscount) {
            this.elements.applyTotalDiscount.addEventListener("click", () =>
                this.applyOrderDiscount(),
            );
        }

        // Listen for customer credit input changes
        if (this.elements.customerCreditAmount) {
            this.elements.customerCreditAmount.addEventListener("input", () => {
                const maxCredit = parseFloat(this.elements.customerCreditAmount.max) || 0;
                let creditValue = parseFloat(this.elements.customerCreditAmount.value) || 0;

                // Ensure credit doesn't exceed available or grand total
                if (creditValue > maxCredit) {
                    creditValue = maxCredit;
                    this.elements.customerCreditAmount.value = creditValue;
                }

                if (this.elements.customerCreditInput) {
                    this.elements.customerCreditInput.value = creditValue;
                }

                this.updateTotalPrice();
            });

            this.elements.customerCreditAmount.addEventListener("change", () => {
                this.updateTotalPrice();
            });
        }

        if (this.elements.form) {
            if (this.elements.form._salesSubmitHandler) {
                this.elements.form.removeEventListener('submit', this.elements.form._salesSubmitHandler);
            }
            const handler = (e) => this.handleSubmit(e);
            this.elements.form._salesSubmitHandler = handler;
            this.elements.form.addEventListener("submit", handler);
        }

        const taxSelector = document.getElementById("taxSelector");
        if (taxSelector) {
            // Use event delegation in case it gets re-rendered
            document.body.addEventListener("change", (e) => {
                if (e.target && e.target.id === "taxSelector") {
                    const opt = e.target.options[e.target.selectedIndex];
                    const rate = parseFloat(opt.dataset.rate) || 0;
                    const type = opt.dataset.type || "exclusive";
                    const taxLabel = document.getElementById("taxLabel");
                    const rateLabel = document.getElementById("taxRateLabel");
                    const typeInput = document.getElementById("taxTypeInput");
                    const rateInput = document.getElementById("taxRateInput");
                    
                    if (taxLabel) {
                        const base = taxLabel.dataset.base || taxLabel.textContent;
                        const rate = parseFloat(opt.dataset.rate) || 0;
                        taxLabel.textContent = opt.value && opt.dataset.name ? base + ' (' + opt.dataset.name + ' ' + rate + '%)' : base;
                    }
                    if (rateLabel) rateLabel.textContent = rate;
                    if (typeInput) typeInput.value = type;
                    if (rateInput) rateInput.value = rate;
                    
                    this.updateTotalPrice();
                }
            });

            // Trigger initial display
            if (taxSelector.options.length > 0 && taxSelector.selectedIndex >= 0) {
                const initialOpt = taxSelector.options[taxSelector.selectedIndex];
                if (initialOpt) {
                    const rate = parseFloat(initialOpt.dataset.rate) || 0;
                    const taxLabel = document.getElementById("taxLabel");
                    const typeInput = document.getElementById("taxTypeInput");
                    const rateInput = document.getElementById("taxRateInput");
                    const rateLabel = document.getElementById("taxRateLabel");
                    
                    if (taxLabel) {
                        const base = taxLabel.dataset.base || taxLabel.textContent;
                        const rate = parseFloat(initialOpt.dataset.rate) || 0;
                        taxLabel.textContent = initialOpt.value && initialOpt.dataset.name ? base + ' (' + initialOpt.dataset.name + ' ' + rate + '%)' : base;
                    }
                    if (rateLabel) rateLabel.textContent = rate;
                    if (typeInput) typeInput.value = initialOpt.dataset.type || "exclusive";
                    if (rateInput) rateInput.value = rate;
                }
            }
        }

        document.body.addEventListener("change", (e) => {
            if (e.target.id === "pphSelector") {
                const pphInput = document.getElementById("pphEnabledInput");
                const pphRateInput = document.getElementById("pphRateInput");
                const pphTaxIdInput = document.getElementById("pphTaxIdInput");
                if (e.target.value) {
                    const opt = e.target.options[e.target.selectedIndex];
                    pphInput.value = "1";
                    pphRateInput.value = opt.dataset.rate;
                    if (pphTaxIdInput) pphTaxIdInput.value = e.target.value;
                } else {
                    pphInput.value = "0";
                    pphRateInput.value = "0";
                    if (pphTaxIdInput) pphTaxIdInput.value = "";
                }
                this.updateTotalPrice();
            }
        });
    }

    calculateDueDate() {
        if (!this.elements.orderDate || !this.elements.customerSelect || !this.elements.dueDate) {
            return;
        }

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
            this.elements.stock_available.textContent = "-";
            this.currentStock = 0;
            this.selectedProductData = null; // Clear previously selected product data
            this.hideQuantityWarning();
            this.enableAddButton();
            return;
        }

        const productId = selectedOption.value;

        fetch(`/admin/product/modal-view/${productId}`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.json();
            })
            .then((data) => {
                this.selectedProductData = data; // Store the full product data
                this.elements.priceField.value = Math.round(data.price);
                this.elements.sellingPriceField.value = Math.round(data.selling_price);
                this.fetchCustomerPastPrice();
                this.updateStockDisplay(); // Update stock display after data is fetched
            })
            .catch((error) => {
                // // console.error('Error fetching product details:', error);
                this.elements.priceField.value = "";
                this.elements.sellingPriceField.value = "";
                this.elements.customerPriceField.value = "";
                this.elements.stock_available.textContent = "-";
                this.currentStock = 0;
                this.selectedProductData = null;
                this.hideQuantityWarning();
                this.enableAddButton();
            });
    }

    updateStockDisplay() {
        if (!this.elements.productSelect || !this.elements.stock_available) {
            return;
        }

        if (!this.selectedProductData) {
            this.elements.stock_available.textContent = "-";
            this.currentStock = 0;
            if (this.elements.quantity) {
                this.elements.quantity.removeAttribute("max");
            }
            this.hideQuantityWarning();
            return;
        }

        const totalStockQuantity = this.selectedProductData.main_warehouse_stock;
        this.currentStock = totalStockQuantity;

        const orderedQuantity = this.getOrderedQuantityForProduct(
            this.selectedProductData.id,
        );
        const availableForSale = Math.max(
            0,
            totalStockQuantity - orderedQuantity,
        ); // Changed from totalRemainingQuantity

        this.elements.stock_available.textContent = availableForSale;

        this.updateStockStyling(availableForSale);

        if (this.elements.quantity) {
            this.elements.quantity.max = availableForSale;
            this.elements.quantity.value = "";
        }
        this.hideQuantityWarning();
    }

    getOrderedQuantityForProduct(productId) {
        return this.products
            .filter((product) => product.product_id === productId)
            .reduce((total, product) => total + product.quantity, 0);
    }

    updateStockStyling(remainingStock) {
        if (!this.elements.stock_available) return;

        this.elements.stock_available.classList.remove(
            "text-primary",
            "text-warning",
            "text-danger",
        );

        if (remainingStock === 0) {
            this.elements.stock_available.classList.add("text-danger");
        } else if (remainingStock <= 5) {
            this.elements.stock_available.classList.add("text-warning");
        } else {
            this.elements.stock_available.classList.add("text-primary");
        }
    }

    validateQuantity() {
        if (
            !this.elements.quantity ||
            !this.elements.productSelect ||
            !this.selectedProductData
        ) {
            return true;
        }

        let quantity = parseInt(this.elements.quantity.value) || 0;
        const productId = this.selectedProductData.id;

        const totalStockQuantity = this.selectedProductData.main_warehouse_stock;
        const orderedQuantity = this.getOrderedQuantityForProduct(productId);
        const availableForSale = Math.max(
            0,
            totalStockQuantity - orderedQuantity,
        );

        if (quantity > availableForSale) {
            this.elements.quantity.value = availableForSale;
            this.elements.quantity.classList.add("is-invalid");
            const warningEl = document.getElementById("quantity_warning");
            if (warningEl) {
                warningEl.style.display = "block";
            }
            this.disableAddButton();
            return false;
        } else {
            this.elements.quantity.classList.remove("is-invalid");
            const warningEl = document.getElementById("quantity_warning");
            if (warningEl) {
                warningEl.style.display = "none";
            }
            this.enableAddButton();
            return true;
        }
    }

    showQuantityWarning() {
        if (this.elements.quantity_warning) {
            this.elements.quantity_warning.style.display = "block";
        }
        if (this.elements.quantity) {
            this.elements.quantity.classList.add("is-invalid");
        }
    }

    hideQuantityWarning() {
        if (this.elements.quantity_warning) {
            this.elements.quantity_warning.style.display = "none";
        }
        if (this.elements.quantity) {
            this.elements.quantity.classList.remove("is-invalid");
        }
    }

    showCreditWarning() {
        const creditInput = document.getElementById("customerCreditAmount");
        if (creditInput) {
            creditInput.classList.add("is-invalid");
        }
        const warningEl = document.getElementById("credit_warning");
        if (warningEl) {
            warningEl.style.display = "block";
        }
    }

    hideCreditWarning() {
        const creditInput = document.getElementById("customerCreditAmount");
        if (creditInput) {
            creditInput.classList.remove("is-invalid");
        }
        const warningEl = document.getElementById("credit_warning");
        if (warningEl) {
            warningEl.style.display = "none";
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

        fetch(`/admin/sales/get-customer-price/${customerId}/${productId}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.past_price) {
                    const roundedPrice = Math.round(data.past_price);
                    this.elements.pastPriceField.value = roundedPrice;
                    this.elements.customerPriceField.value = roundedPrice;
                } else {
                    this.elements.pastPriceField.value = "0";
                }
            })
            .catch((error) => {
                // console.error("Error fetching past price:", error);
                this.elements.pastPriceField.value = "0";
            });
    }

    updateTotalPrice() {
        // Try to get products from class property first, fallback to hidden field
        let products = this.products;

        // If products is empty, try reading from hidden field
        if (!products || products.length === 0) {
            const productsField = document.getElementById("productsField");
            if (productsField && productsField.value) {
                try {
                    products = JSON.parse(productsField.value);
                } catch (e) {
                    products = [];
                }
            }
        }

        // Handle empty or no products state
        if (!products || products.length === 0) {
            if (document.getElementById("subtotal")) {
                document.getElementById("subtotal").innerText = this.formatPrice(0);
            }
            if (document.getElementById("orderDiscountTotal")) {
                document.getElementById("orderDiscountTotal").innerText = this.formatPrice(0);
            }
            if (document.getElementById("taxTotal")) {
                document.getElementById("taxTotal").innerText = this.formatPrice(0);
            }
            if (document.getElementById("finalTotal")) {
                document.getElementById("finalTotal").innerText = this.formatPrice(0);
            }
            // Hide credit row when empty
            const creditRow = document.getElementById("creditUsedRow");
            if (creditRow) creditRow.style.display = "none";
            return;
        }

        let subtotal = 0;
        let itemDiscount = 0;

        products.forEach((product) => {
            subtotal += product.total;

            const productDiscount = this.calculateDiscountAmount(
                product.customer_price,
                product.quantity,
                product.discount,
                product.discountType,
            );

            itemDiscount += productDiscount;
        });

        const orderDiscountAmount = this.calculateOrderDiscount(
            subtotal,
            this.orderDiscount,
            this.orderDiscountType,
        );
        const totalDiscount = itemDiscount + orderDiscountAmount;
        const taxableAmount = subtotal - orderDiscountAmount;
        const taxAmount = this.calculateTaxAmount(taxableAmount);
        const grandTotal = this.getTaxType() === "inclusive" ? taxableAmount : taxableAmount + taxAmount;

        const pphAmount = this.calculatePphAmount(taxableAmount);
        const pphAmountInput = document.getElementById("pphAmountInput");
        if (pphAmountInput) pphAmountInput.value = pphAmount;
        const pphTotalDisplay = document.getElementById("pphTotal");
        if (pphTotalDisplay) pphTotalDisplay.textContent = formatCurrency(pphAmount);
        const totalAfterPph = grandTotal - pphAmount;

        // Get credit amount directly from DOM (not from cached elements)
        const creditInputEl = document.getElementById("customerCreditAmount");
        const creditInputValue = parseFloat(creditInputEl?.value) || 0;

        // Get available credit max from the input's max attribute
        const availableCredit = parseFloat(creditInputEl?.max) || 0;

        // Validate credit input - cap at max available if exceeded (backup validation)
        let creditAmount = creditInputValue;
        if (creditAmount > availableCredit && availableCredit > 0) {
            creditAmount = availableCredit;
            if (creditInputEl) {
                creditInputEl.value = availableCredit;
            }
        }

        this.customerCreditUsed = creditAmount;
        const finalTotal = Math.max(0, totalAfterPph - creditAmount);

        // Update credit display in summary
        const creditRow = document.getElementById("creditUsedRow");
        const creditAmountEl = document.getElementById("creditUsedAmount");
        const creditUsedInput = document.getElementById("creditUsedInput");

        if (creditAmount > 0 && creditRow && creditAmountEl) {
            creditRow.style.display = "flex";
            creditAmountEl.innerText = this.formatPrice(creditAmount);
        } else if (creditRow) {
            creditRow.style.display = "none";
        }
        if (creditUsedInput) {
            creditUsedInput.value = creditAmount;
        }

        // Update all summary amounts
        if (document.getElementById("subtotal")) {
            document.getElementById("subtotal").innerText = this.formatPrice(subtotal);
        }
        if (document.getElementById("orderDiscountTotal")) {
            document.getElementById("orderDiscountTotal").innerText = this.formatPrice(orderDiscountAmount);
        }
        if (document.getElementById("taxTotal")) {
            document.getElementById("taxTotal").innerText = this.formatPrice(taxAmount);
        }
        if (document.getElementById("finalTotal")) {
            document.getElementById("finalTotal").innerText = this.formatPrice(finalTotal);
        }

        const totalDiscountInput = document.getElementById("totalDiscountInput");
        const orderDiscountInput = document.getElementById("orderDiscountInput");
        const orderDiscountTypeInput = document.getElementById("orderDiscountTypeInput");
        const taxInput = document.getElementById("taxInput");

        if (totalDiscountInput) totalDiscountInput.value = (itemDiscount + orderDiscountAmount).toFixed(2);
        if (orderDiscountInput) orderDiscountInput.value = this.orderDiscount;
        if (orderDiscountTypeInput) orderDiscountTypeInput.value = this.orderDiscountType;
        if (taxInput) taxInput.value = taxAmount;

        // Store credit amount in hidden field for form submission
        const creditHiddenInput = document.getElementById("customerCreditInput");
        if (creditHiddenInput) {
            creditHiddenInput.value = creditAmount;
        }

        // Update discount label with type info
        const discountLabel = document.getElementById('discountLabel');
        if (discountLabel) {
            const base = discountLabel.dataset.base || 'Order Discount';
            discountLabel.textContent = this.orderDiscount > 0
                ? (this.orderDiscountType === 'percentage' ? base + ' (' + this.orderDiscount + '%)' : base + ' (Rp ' + this.orderDiscount.toLocaleString() + ')')
                : base;
        }

        if (this.elements.productsField) {
            this.elements.productsField.value = JSON.stringify(products);
        }
    }

    addProduct() {
        // Get values directly from DOM, not from stored elements
        const quantityInput = document.getElementById("quantity");
        const priceInput = document.getElementById("customer_price");

        const quantity = parseInt(quantityInput?.value) || 0;
        const price = parseFloat(priceInput?.value) || 0;

        // Early return if no product selected
        const productSelect = document.getElementById("product_id");
        if (!productSelect || !productSelect.value) {
            return;
        }

        // Early return if no quantity or price
        if (quantity === 0 || price === 0) {
            return;
        }

        // Validate quantity against stock
        if (this.selectedProductData) {
            const totalStockQuantity = this.selectedProductData.main_warehouse_stock || 0;
            const orderedQuantity = this.getOrderedQuantityForProduct(this.selectedProductData.id);
            const availableForSale = Math.max(0, totalStockQuantity - orderedQuantity);

            if (quantity > availableForSale) {
                this.showQuantityWarning();
                this.disableAddButton();
                return;
            }
        }

        const productId = productSelect.value;
        const productName = productSelect.options[productSelect.selectedIndex].text;
        const discount = parseFloat(document.getElementById("discount")?.value) || 0;
        const discountType = document.getElementById("discount_type")?.value || "fixed";

        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const stock = parseInt(selectedOption.getAttribute("data-stock")) || 0;

        const uniqueId = `${Date.now()}-${Math.random().toString(36).substring(2, 7)}`;

        const total = this.calculateTotal(price, quantity, discount, discountType);

        this.products.push({
            product_id: productId,
            uniqueId,
            name: productName,
            quantity,
            customer_price: price,
            discount,
            discountType,
            total,
            stock: this.selectedProductData.main_warehouse_stock,
        });

        // Update hidden field immediately
        const productsField = document.getElementById("productsField");
        if (productsField) {
            productsField.value = JSON.stringify(this.products);
        }

        this.renderTable();

        // Clear form without triggering events
        if (quantityInput) quantityInput.value = "";
        if (priceInput) priceInput.value = "";
        const discountInput = document.getElementById("discount");
        if (discountInput) discountInput.value = "";

        this.updateStockDisplay();
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

        if (this.elements.discountTotalValue)
            this.elements.discountTotalValue.value = 0;
        if (this.elements.discountTotalType)
            this.elements.discountTotalType.value = "fixed";

        this.renderTable();
        this.updateStockDisplay();
    }

    applyOrderDiscount() {
        this.orderDiscount =
            parseFloat(this.elements.discountTotalValue.value) || 0;
        this.orderDiscountType = this.elements.discountTotalType.value;
        this.updateTotalPrice();
    }

    handleSubmit(e) {
        if (e.defaultPrevented) return false;

        try {
            if (this.products.length === 0) {
                e.preventDefault();
                e.stopPropagation();
                InventMagApp.showToast(
                    "Warning",
                    "Please add at least one product before submitting.",
                    "warning",
                );
                return false;
            }

            this.sessionJustSubmitted = true;
        } catch (error) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }

    renderTable() {
        let tableBody = document.getElementById("productTableBody");

        if (!tableBody) {
            return;
        }

        tableBody.innerHTML = "";

        if (this.products.length === 0) {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td colspan="8" class="text-center py-5">
                    <div class="empty-state">
                        <i class="ti ti-shopping-cart-off fs-1 text-muted mb-3 d-block" style="font-size: 2rem;"></i>
                        <h4 class="text-muted">No items added yet</h4>
                        <p class="text-muted small">Select a product above to add it to the order</p>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
            this.elements.productTableBody = tableBody;
            this.updateTotalPrice();
            return;
        }

        this.products.forEach((product, index) => {
            const totalStockQuantity = product.stock; // Changed from totalRemainingQuantity
            const totalOrderedForProduct = this.products
                .filter((p) => p.product_id === product.product_id)
                .reduce((sum, p) => sum + p.quantity, 0);
            const availableForSale = Math.max(
                0,
                totalStockQuantity - totalOrderedForProduct + product.quantity,
            ); // Add back current product's quantity for its own row

            const row = document.createElement("tr");
            row.innerHTML = `
                <td class="text-center">${index + 1}</td>
                <td>${product.name}</td>
                <td class="text-center">
                    <span class="badge text-white ${
                        availableForSale === 0
                            ? "bg-danger"
                            : availableForSale <= 5
                              ? "bg-warning"
                              : "bg-success"
                    }">
                        ${availableForSale}
                    </span>
                </td>
                <td class="text-center">
                    <input type="number" class="form-control quantity-input text-center"
                        value="${product.quantity}" data-unique-id="${
                            product.uniqueId
                        }"
                        min="1" max="${availableForSale}" style="width:80px;" />
                </td>
                <td class="text-center">
                    <input type="number" class="form-control price-input text-center"
                        value="${product.customer_price}" data-unique-id="${
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
                            min="0" step="1" />
                        <select class="form-select discount-type" data-unique-id="${
                            product.uniqueId
                        }">
                            <option value="fixed" ${
                                product.discountType === "fixed"
                                    ? "selected"
                                    : ""
                            }>${window.currencySettings.currency_symbol}</option>
                            <option value="percentage" ${
                                product.discountType === "percentage"
                                    ? "selected"
                                    : ""
                            }>%</option>
                        </select>
                    </div>
                </td>
                <td class="text-end product-total fw-bold">${formatCurrency(
                    product.total,
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
            tableBody.appendChild(row);
        });

        this.elements.productTableBody = tableBody;

        this.updateTotalPrice();
        this.attachTableEventListeners();
    }

    attachTableEventListeners() {
        let tableBody = document.getElementById("productTableBody");

        if (!tableBody) {
            return;
        }

        this.elements.productTableBody = tableBody;

        // Add event listeners directly to the table body (event delegation)
        tableBody.addEventListener("input", (event) => {
            this.handleTableInput(event);
        });

        tableBody.addEventListener("change", (event) => {
            this.handleTableChange(event);
        });

        tableBody.addEventListener("click", (event) => {
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

            const totalOrderedForProduct = this.products
                .filter((p) => p.id === product.id && p.uniqueId !== uniqueId)
                .reduce((sum, p) => sum + p.quantity, 0);

            if (newQuantity + totalOrderedForProduct > product.stock) {
                target.classList.add("is-invalid");
                target.value = product.quantity;
                return;
            } else {
                target.classList.remove("is-invalid");
                product.quantity = newQuantity;
            }
        } else if (target.classList.contains("price-input")) {
            product.customer_price = parseFloat(target.value) || 0;
        } else if (target.classList.contains("discount-input")) {
            product.discount = parseFloat(target.value) || 0;
        }

        this.updateProductInTable(product, target);
        this.updateStockDisplay();
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
        this.updateStockDisplay();
    }

    updateProductInTable(product, targetElement) {
        product.total = this.calculateTotal(
            product.customer_price,
            product.quantity,
            product.discount,
            product.discountType,
        );

        const totalElement = targetElement
            .closest("tr")
            .querySelector(".product-total");
        if (totalElement) {
            totalElement.innerText = formatCurrency(product.total);
        }

        this.updateTotalPrice();
    }
}
