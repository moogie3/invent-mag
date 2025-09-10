import { SalesOrderModule } from '../common/SalesOrderModule.js';
import { formatCurrency } from '../../../../utils/currencyFormatter.js';

export class SalesOrderEdit extends SalesOrderModule {
    constructor(config = {}) {
        super(config);

        this.elements = {
            orderDate: document.getElementById("order_date"),
            dueDate: document.getElementById("due_date"),
            customerSelect: document.getElementById("customer_id"),
            discountTotalValue: document.getElementById("discountTotalValue"),
            discountTotalType: document.getElementById("discountTotalType"),
        };

        this.initFlatpickr(this.elements.orderDate, this.elements.dueDate);

        this.initEventListeners();
        this.calculateTotals();

        this.elements.form = document.getElementById("edit-sales-form");
        this.elements.productsJsonInput =
            document.getElementById("products-json");

        if (this.elements.form) {
            this.elements.form.addEventListener(
                "submit",
                this.serializeProducts.bind(this)
            );
        }
    }

    serializeProducts() {
        const products = [];
        const productRows = document.querySelectorAll("tbody tr");

        productRows.forEach((row) => {
            const productId = row.dataset.productId;
            if (!productId) {
                return;
            }

            const quantity = row.querySelector(".quantity-input").value;
            const price = row.querySelector(".price-input").value;
            const discount = row.querySelector(".discount-input").value;
            const discountType = row.querySelector(
                ".discount-type-input"
            ).value;

            products.push({
                product_id: productId,
                quantity: quantity,
                customer_price: price,
                discount: discount,
                discount_type: discountType,
            });
        });

        if (this.elements.productsJsonInput) {
            this.elements.productsJsonInput.value = JSON.stringify(products);
        }
    }

    initEventListeners() {
        if (this.elements.customerSelect) {
            this.elements.customerSelect.addEventListener("change", () =>
                this.calculateDueDate()
            );
        }

        if (this.elements.orderDate && this.elements.orderDate._flatpickr) {
            this.elements.orderDate._flatpickr.config.onChange.push(() =>
                this.calculateDueDate()
            );
        } else if (this.elements.orderDate) {
            this.elements.orderDate.addEventListener("change", () =>
                this.calculateDueDate()
            );
        }

        document.addEventListener("input", (event) => {
            if (
                event.target.matches(
                    ".quantity-input, .price-input, .discount-input, #discountTotalValue"
                )
            ) {
                this.calculateTotals();
            }
        });

        document.addEventListener("change", (event) => {
            if (
                event.target.matches(".discount-type-input, #discountTotalType")
            ) {
                this.calculateTotals();
            }
        });
    }

    calculateDueDate() {
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
        let subtotalBeforeDiscounts = 0;

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

            subtotalBeforeDiscounts += price * quantity;

            const discountInput = row.querySelector(
                `.discount-input[data-item-id='${itemId}']`
            );
            const discountTypeSelect = row.querySelector(
                `.discount-type-input[data-item-id='${itemId}']`
            );

            const discountValue = parseFloat(discountInput?.value) || 0;
            const discountType = discountTypeSelect?.value || "percentage";

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

            const amountInput = row.querySelector(
                `.amount-input[data-item-id='${itemId}']`
            );
            if (amountInput) {
                amountInput.value = Math.round(netAmount);
            }

            subtotal += price * quantity;
        });

        const discountTotalValue =
            parseFloat(this.elements.discountTotalValue?.value) || 0;
        const discountTotalType =
            this.elements.discountTotalType?.value || "percentage";
        let orderDiscountAmount = 0;

        if (discountTotalType === "percentage") {
            orderDiscountAmount =
                subtotalBeforeDiscounts * (discountTotalValue / 100);
        } else {
            orderDiscountAmount = discountTotalValue;
        }

        const totalAfterAllDiscounts = subtotal - orderDiscountAmount;
        const taxAmount = totalAfterAllDiscounts * (this.taxRate / 100);
        const grandTotal = totalAfterAllDiscounts + taxAmount;

        document.getElementById("subtotal").innerText =
            formatCurrency(subtotal);
        document.getElementById("orderDiscountTotal").innerText =
            formatCurrency(orderDiscountAmount);

        if (document.getElementById("totalTax")) {
            document.getElementById("totalTax").innerText =
                formatCurrency(taxAmount);
        }

        document.getElementById("finalTotal").innerText =
            formatCurrency(grandTotal);

        document.getElementById("grandTotalInput").value =
            Math.floor(grandTotal);
        document.getElementById("totalDiscountInput").value =
            Math.floor(orderDiscountAmount);
        document.getElementById("taxInput").value = Math.floor(taxAmount);

        const totalTaxInput = document.getElementById("total_tax_input");
        if (totalTaxInput) {
            totalTaxInput.value = Math.floor(taxAmount);
        }
    }
}
