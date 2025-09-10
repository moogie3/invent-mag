import { PurchaseOrderModule } from '../common/PurchaseOrderModule.js';
import { formatCurrency } from '../../../../utils/currencyFormatter.js';

export class PurchaseOrderCreate extends PurchaseOrderModule {
    constructor(config = {}) {
        super(config);

        this.elements = this.initializeElements();
        this.products = [];
        this.orderDiscount = { value: 0, type: "fixed" };
        this.sessionJustSubmitted = false;
        this.currentStock = 0;

        this.checkSessionState();
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
            "expiry_date",

        const elements = {};
        elementIds.forEach((id) => {
            elements[id] = this.safeGetElement(id);
        });

        return elements;
    }

    initFlatpickr() {
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
        if (this.sessionJustSubmitted) {
            this.products = [];
            this.orderDiscount = { value: 0, type: "fixed" };
            this.sessionJustSubmitted = false;
        }
    }

    initEventListeners() {
        if (this.elements.supplier_id) {
            this.elements.supplier_id.addEventListener("change", () =>
                this.calculateDueDate()
            );
        }

        if (this.elements.product_id) {
            this.elements.product_id.addEventListener("change", () => {
                this.updateProductPrice();
                this.updateStockDisplay();
            });
        }

        if (this.elements.addProduct) {
            this.elements.addProduct.addEventListener("click", () =>
                this.addProduct()
            );
        }

        if (this.elements.clearProducts) {
            this.elements.clearProducts.addEventListener("click", () =>
                this.clearProducts()
            );
        }

        if (this.elements.applyTotalDiscount) {
            this.elements.applyTotalDiscount.addEventListener("click", () =>
                this.applyOrderDiscount()
            );
        }

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

            this.elements.stock_available.textContent = stock;

            this.updateStockStyling(stock);

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

        this.elements.stock_available.classList.remove(
            "text-primary",
            "text-warning",
            "text-danger"
        );

        if (stock === 0) {
            this.elements.stock_available.classList.add("text-danger");
        } else if (stock <= 5) {
            this.elements.stock_available.classList.add("text-warning");
        } else {
            this.elements.stock_available.classList.add("text-primary");
        }
    }

    updateTotalPrice() {
        let subtotal = this.products.reduce(
            (sum, product) => sum + product.total,
            0
        );

        const orderDiscountAmount = this.calculateDiscount(
            subtotal,
            this.orderDiscount.value,
            this.orderDiscount.type
        );

        const finalTotal = subtotal - orderDiscountAmount;

        const subtotalEl = document.getElementById("subtotal");
        const orderDiscountTotalEl =
            document.getElementById("orderDiscountTotal");
        const finalTotalEl = document.getElementById("finalTotal");
        const totalDiscountInputEl =
            document.getElementById("totalDiscountInput");

        if (subtotalEl) subtotalEl.innerText = formatCurrency(subtotal);
        if (orderDiscountTotalEl)
            orderDiscountTotalEl.innerText =
                formatCurrency(orderDiscountAmount);
        if (finalTotalEl)
            finalTotalEl.innerText = formatCurrency(finalTotal);
        if (totalDiscountInputEl)
            totalDiscountInputEl.value = orderDiscountAmount;

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

        if (quantity <= 0) {
            alert("Please enter a valid quantity greater than 0.");
            return;
        }

        const selectedOption =
            this.elements.product_id.options[
                this.elements.product_id.selectedIndex
            ];
        const stock = parseInt(selectedOption.getAttribute("data-stock")) || 0;
        const hasExpiry = selectedOption.getAttribute("data-has-expiry") === "1";

        const uniqueId = `${Date.now()}-${Math.random()
            .toString(36)
            .substring(2, 7)}`;
        const total = this.calculateTotal(
            price,
            quantity,
            discount,
            discountType
        );

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
            hasExpiry,
            expiry_date: null, // Initialize expiry_date to null
        });

        this.renderTable();
        this.clearProductForm();
        this.updateStockDisplay();
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

        if (this.elements.stock_available) {
            this.elements.stock_available.textContent = "-";
        }
    }

    clearProducts() {
        this.products = [];
        this.orderDiscount = { value: 0, type: "fixed" };

        if (this.elements.discountTotalValue)
            this.elements.discountTotalValue.value = 0;
        if (this.elements.discountTotalType)
            this.elements.discountTotalType.value = "fixed";

        this.renderTable();
        this.updateStockDisplay();
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

        const newTableBody = this.elements.productTableBody.cloneNode(true);
        this.elements.productTableBody.parentNode.replaceChild(
            newTableBody,
            this.elements.productTableBody
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
            target.classList.remove("is-invalid");
            product.quantity = newQuantity;
        } else if (target.classList.contains("price-input")) {
            product.price = parseFloat(target.value) || 0;
        } else if (target.classList.contains("discount-input")) {
            product.discount = parseFloat(target.value) || 0;
        } else if (target.classList.contains("expiry-date-input")) {
            product.expiry_date = target.value;
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
            product.price,
            product.quantity,
            product.discount,
            product.discountType
        );

        const totalElement = targetElement
            .closest("tr")
            .querySelector(".product-total");
        if (totalElement) {
            totalElement.innerText = formatCurrency(product.total);
        }

        this.updateTotalPrice();
    }

    renderTable() {
        if (!this.elements.productTableBody) return;

        this.elements.productTableBody.innerHTML = "";

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
                    product.total
                )}</td>
                <td class="text-center">
                    ${product.hasExpiry ? `
                    <input type="date" class="form-control expiry-date-input text-center"
                        value="${product.expiry_date ? product.expiry_date : ''}" data-unique-id="${
                            product.uniqueId
                        }" style="width:120px;" />
                    ` : 'N/A'}
                </td>
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
