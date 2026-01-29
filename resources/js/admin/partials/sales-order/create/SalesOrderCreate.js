import { SalesOrderModule } from "../common/SalesOrderModule.js";
import { formatCurrency } from "../../../../utils/currencyFormatter.js";

export class SalesOrderCreate extends SalesOrderModule {
    constructor(config = {}) {
        super(config);

        this.elements = this.initializeElements();

        this.products = [];
        this.orderDiscount = 0;
        this.orderDiscountType = "fixed";
        this.sessionJustSubmitted = false;
        this.currentStock = 0;

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
        ];

        const elements = {};
        elementIds.forEach((id) => {
            elements[id] = this.safeGetElement(id);
        });

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
        if (this.sessionJustSubmitted) {
            this.products = [];
            this.orderDiscount = 0;
            this.orderDiscountType = "fixed";
            this.sessionJustSubmitted = false;
        }
    }

    initEventListeners() {
        this.elements.customerSelect.addEventListener("change", () =>
            this.calculateDueDate(),
        );

        if (this.elements.orderDate && this.elements.orderDate._flatpickr) {
            this.elements.orderDate._flatpickr.config.onChange.push(() =>
                this.calculateDueDate(),
            );
        } else {
            this.elements.orderDate.addEventListener("change", () =>
                this.calculateDueDate(),
            );
        }

        this.elements.productSelect.addEventListener("change", () => {
            this.updateProductPrices();
            this.updateStockDisplay();
        });
        this.elements.customerSelect.addEventListener("change", () =>
            this.fetchCustomerPastPrice(),
        );

        if (this.elements.quantity) {
            this.elements.quantity.addEventListener("input", () =>
                this.validateQuantity(),
            );
            this.elements.quantity.addEventListener("change", () =>
                this.validateQuantity(),
            );
        }

        this.elements.addProductBtn.addEventListener("click", () =>
            this.addProduct(),
        );

        this.elements.clearProductsBtn.addEventListener("click", () =>
            this.clearProducts(),
        );

        this.elements.applyTotalDiscount.addEventListener("click", () =>
            this.applyOrderDiscount(),
        );

        this.elements.form.addEventListener("submit", (e) =>
            this.handleSubmit(e),
        );
    }

    calculateDueDate() {
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
                this.elements.priceField.value = data.price;
                this.elements.sellingPriceField.value = data.selling_price;
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

        const totalStockQuantity = this.selectedProductData.stock_quantity;
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
            .filter((product) => product.id === productId)
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

        const quantity = parseInt(this.elements.quantity.value) || 0;
        const productId = this.selectedProductData.id;

        const totalStockQuantity = this.selectedProductData.stock_quantity;
        const orderedQuantity = this.getOrderedQuantityForProduct(productId);
        const availableForSale = Math.max(
            0,
            totalStockQuantity - orderedQuantity,
        ); // Changed from totalRemainingQuantity

        if (quantity > availableForSale) {
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
                // console.error("Error fetching past price:", error);
                this.elements.pastPriceField.value = "0";
            });
    }

    updateTotalPrice() {
        let subtotal = 0;
        let totalBeforeDiscounts = 0;
        let itemDiscount = 0;

        this.products.forEach((product) => {
            const priceInCents = Math.round(product.customer_price * 100);
            const productSubtotal = (priceInCents * product.quantity) / 100;
            totalBeforeDiscounts += productSubtotal;

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
        const taxAmount = taxableAmount * (this.taxRate / 100);
        const finalTotal = taxableAmount + taxAmount;

        document.getElementById("subtotal").innerText =
            formatCurrency(subtotal);
        document.getElementById("orderDiscountTotal").innerText =
            formatCurrency(orderDiscountAmount);
        document.getElementById("taxTotal").innerText =
            formatCurrency(taxAmount);
        document.getElementById("finalTotal").innerText =
            formatCurrency(finalTotal);

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
            // console.warn("Required form elements not found for adding product");
            return;
        }

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

        const selectedOption =
            this.elements.productSelect.options[
                this.elements.productSelect.selectedIndex
            ];
        const stock = parseInt(selectedOption.getAttribute("data-stock")) || 0;

        const uniqueId = `${Date.now()}-${Math.random()
            .toString(36)
            .substring(2, 7)}`;

        const total = this.calculateTotal(
            price,
            quantity,
            discount,
            discountType,
        );

        this.products.push({
            product_id: productId,
            uniqueId,
            name: productName,
            quantity,
            customer_price: price,
            discount,
            discountType,
            total,
            stock: this.selectedProductData.stock_quantity, // Use overall stock for display
            // Removed po_items: this.selectedProductData.po_items,
        });

        this.renderTable();
        this.clearProductForm();
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
        if (this.products.length === 0) {
            e.preventDefault();
            InventMagApp.showToast(
                "Warning",
                "Please add at least one product before submitting.",
                "warning",
            );
            return false;
        }

        this.sessionJustSubmitted = true;
    }

    renderTable() {
        this.elements.productTableBody.innerHTML = "";

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
                            min="0" step="0.01" />
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
            this.elements.productTableBody.appendChild(row);
        });

        this.updateTotalPrice();
        this.attachTableEventListeners();
    }

    attachTableEventListeners() {
        const newTableBody = this.elements.productTableBody.cloneNode(true);
        this.elements.productTableBody.parentNode.replaceChild(
            newTableBody,
            this.elements.productTableBody,
        );
        this.elements.productTableBody = newTableBody;

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
