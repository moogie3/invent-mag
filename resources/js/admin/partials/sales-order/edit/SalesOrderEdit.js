import { SalesOrderModule } from "../common/SalesOrderModule.js";
import { formatCurrency } from "../../../../utils/currencyFormatter.js";

export class SalesOrderEdit extends SalesOrderModule {
    constructor(config = {}) {
        super(config);

        this.elements = this.initializeEditElements();
        this.initEventListeners();
        this.calculateTotals();
        this.isSubmitting = false;
    }

    initializeEditElements() {
        return {
            orderDate: document.getElementById("order_date"),
            dueDate: document.getElementById("due_date"),
            customerSelect: document.getElementById("customer_id"),
            discountTotalValue: document.getElementById("discountTotalValue"),
            discountTotalType: document.getElementById("discountTotalType"),
            statusSelect: document.getElementById("status"),
            balanceAmount: document.getElementById("balance-amount"),
            totalPaidAmount: document.getElementById("total-paid-amount"),
            form: document.getElementById("edit-sales-form"),
            productsJsonInput: document.getElementById("products-json"),
        };
    }

    initEventListeners() {
        if (this.elements.customerSelect) {
            this.elements.customerSelect.addEventListener("change", () =>
                this.calculateDueDate(),
            );
        }

        if (this.elements.orderDate && this.elements.orderDate._flatpickr) {
            this.elements.orderDate._flatpickr.config.onChange.push(() =>
                this.calculateDueDate(),
            );
        } else if (this.elements.orderDate) {
            this.elements.orderDate.addEventListener("change", () =>
                this.calculateDueDate(),
            );
        }

        document.addEventListener("input", (event) => {
            if (
                event.target.matches(
                    ".quantity-input, .price-input, .discount-input, #discountTotalValue",
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

        if (this.elements.form) {
            this.elements.form.addEventListener(
                "submit",
                this.serializeProducts.bind(this),
            );
        }
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

        document
            .querySelectorAll("#sales-items-table-body tr")
            .forEach((row) => {
                const itemId =
                    row.querySelector(".quantity-input")?.dataset.itemId;
                if (!itemId) return;

                const quantity =
                    parseFloat(
                        row.querySelector(
                            `.quantity-input[data-item-id='${itemId}']`,
                        ).value,
                    ) || 0;
                const price =
                    parseFloat(
                        row.querySelector(
                            `.price-input[data-item-id='${itemId}']`,
                        ).value,
                    ) || 0;

                const priceInCents = Math.round(price * 100);
                subtotalBeforeDiscounts += (priceInCents * quantity) / 100;

                const discountInput = row.querySelector(
                    `.discount-input[data-item-id='${itemId}']`,
                );
                const discountTypeSelect = row.querySelector(
                    `.discount-type-input[data-item-id='${itemId}']`,
                );

                const discountValue = parseFloat(discountInput?.value) || 0;
                const discountType = discountTypeSelect?.value || "percentage";

                const itemTotal = this.calculateTotal(
                    price,
                    quantity,
                    discountValue,
                    discountType,
                );

                const amountInput = row.querySelector(
                    `.amount-input[data-item-id='${itemId}']`,
                );
                if (amountInput) {
                    amountInput.value = itemTotal.toFixed(2);
                }

                subtotal += itemTotal;
            });

        const discountTotalValue =
            parseFloat(this.elements.discountTotalValue?.value) || 0;
        const discountTotalType =
            this.elements.discountTotalType?.value || "percentage";
        let orderDiscountAmount = 0;

        if (discountTotalType === "percentage") {
            orderDiscountAmount = subtotal * (discountTotalValue / 100);
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
            grandTotal.toFixed(2);
        document.getElementById("totalDiscountInput").value =
            orderDiscountAmount.toFixed(2);
        document.getElementById("taxInput").value = taxAmount.toFixed(2);

        const totalTaxInput = document.getElementById("total_tax_input");
        if (totalTaxInput) {
            totalTaxInput.value = taxAmount.toFixed(2);
        }

        const totalPaid = parseFloat(
            this.elements.totalPaidAmount.dataset.totalPaid,
        );
        const newBalance = grandTotal - totalPaid;

        if (this.elements.balanceAmount) {
            this.elements.balanceAmount.textContent =
                formatCurrency(newBalance);
            this.elements.balanceAmount.dataset.balance = newBalance;
        }
    }

    serializeProducts(event) {
        event.preventDefault(); // Prevent default form submission

        if (this.isSubmitting) {
            return; // Prevent multiple submissions
        }

        const status = this.elements.statusSelect.value;
        const balance = parseFloat(this.elements.balanceAmount.dataset.balance);

        if (status === "Paid" && balance > 0) {
            InventMagApp.showToast(
                "Warning",
                "Cannot mark as Paid. Please add a payment to cover the outstanding balance.",
                "warning",
            );
            return; // Stop further execution
        }

        const products = [];
        const productRows = document.querySelectorAll(
            "#sales-items-table-body tr",
        );

        productRows.forEach((row) => {
            const productId =
                row.querySelector(".quantity-input")?.dataset.itemId;
            if (!productId) {
                return;
            }

            const quantity = row.querySelector(
                `.quantity-input[data-item-id="${productId}"]`,
            ).value;
            const price = row.querySelector(
                `.price-input[data-item-id="${productId}"]`,
            ).value;
            const discount = row.querySelector(
                `.discount-input[data-item-id="${productId}"]`,
            ).value;
            const discountType = row.querySelector(
                `.discount-type-input[data-item-id="${productId}"]`,
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

        this.isSubmitting = true;
        this.elements.form.submit(); // Manually submit the form after serialization
    }

    calculateTotal(price, quantity, discount, discountType) {
        let itemTotal = price * quantity;
        let discountAmount = 0;
        if (discount > 0) {
            if (discountType === "percentage") {
                discountAmount = itemTotal * (discount / 100);
            } else {
                discountAmount = discount * quantity;
            }
        }
        return itemTotal - discountAmount;
    }
}
