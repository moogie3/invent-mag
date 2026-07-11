import { PurchaseOrderModule } from "../common/PurchaseOrderModule.js";

export class PurchaseOrderEdit extends PurchaseOrderModule {
    constructor(config = {}) {
        super(config);

        this.elements = this.initializeEditElements();
        this.initEventListeners();
        this.calculateAllAmounts();
        this.isSubmitting = false;
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
                ".discount-type-input",
            ),
            form: document.getElementById("edit-po-form"),
            productsJsonInput: document.getElementById("products-json"),
            statusSelect: document.getElementById("status"),
            balanceAmount: document.getElementById("balance-amount"),
            totalPaidAmount: document.getElementById("total-paid-amount"),
        };
    }

    initEventListeners() {
        document.addEventListener("input", (event) => {
            if (
                event.target.matches(
                    ".quantity-input, .price-input, .discount-input",
                )
            ) {
                const itemId = event.target.dataset.itemId;
                if (itemId) {
                    this.calculateOrderTotal();
                }
            } else if (event.target.matches("#discountTotalValue")) {
                this.calculateOrderTotal();
            }
        });

        document.addEventListener("change", (event) => {
            if (event.target.matches(".discount-type-input")) {
                const itemId = event.target.dataset.itemId;
                if (itemId) {
                    this.calculateOrderTotal();
                }
            } else if (event.target.matches("#discountTotalType")) {
                this.calculateOrderTotal();
            }
        });

        if (this.elements.form) {
            this.elements.form.addEventListener(
                "submit",
                this.serializeProducts.bind(this),
            );
        }

        const taxSelector = document.getElementById("taxSelector");
        if (taxSelector) {
            document.body.addEventListener("change", (e) => {
                if (e.target && e.target.id === "taxSelector") {
                    const opt = e.target.options[e.target.selectedIndex];
                    const typeInput = document.getElementById("taxTypeInput");
                    const rateInput = document.getElementById("taxRateInput");
                    if (typeInput) typeInput.value = opt.dataset.type || "none";
                    if (rateInput) rateInput.value = parseFloat(opt.dataset.rate) || 0;
                    this.calculateOrderTotal();
                }
            });

            // Sync hidden inputs on initial page load from the selected option
            const initialOpt = taxSelector.options[taxSelector.selectedIndex];
            if (initialOpt) {
                const typeInput = document.getElementById("taxTypeInput");
                const rateInput = document.getElementById("taxRateInput");
                if (typeInput && initialOpt.dataset.type) typeInput.value = initialOpt.dataset.type;
                if (rateInput && initialOpt.dataset.rate) rateInput.value = parseFloat(initialOpt.dataset.rate) || 0;
            }
        }
    }

    calculateAllAmounts() {
        this.calculateOrderTotal();
    }

    calculateOrderTotal() {
        let subtotal = 0;

        document.querySelectorAll("#po-items-table-body tr").forEach((row) => {
            const itemId = row.querySelector(".quantity-input")?.dataset.itemId;
            if (!itemId) return;

            const quantity =
                parseFloat(
                    row.querySelector(
                        `.quantity-input[data-item-id="${itemId}"]`,
                    ).value,
                ) || 0;
            const price =
                parseFloat(
                    row.querySelector(`.price-input[data-item-id="${itemId}"]`)
                        .value,
                ) || 0;
            const discountInput = row.querySelector(
                `.discount-input[data-item-id="${itemId}"]`,
            );
            const discountTypeSelect = row.querySelector(
                `.discount-type-input[data-item-id="${itemId}"]`,
            );

            const discountValue = parseFloat(discountInput?.value) || 0;
            const discountType = discountTypeSelect?.value || "fixed";

            const itemTotal = this.calculateTotal(
                price,
                quantity,
                discountValue,
                discountType,
            );

            const amountInput = row.querySelector(
                `.amount-input[data-item-id="${itemId}"]`,
            );
            if (amountInput) {
                amountInput.value = itemTotal.toFixed(2);
            }

            subtotal += itemTotal;
        });

        const discountTotalValue =
            parseFloat(this.elements.discountTotalValue?.value) || 0;
        const discountTotalType =
            this.elements.discountTotalType?.value || "fixed";

        const orderDiscountAmount = this.calculateDiscount(
            subtotal,
            discountTotalValue,
            discountTotalType,
        );
        const taxableAmount = subtotal - orderDiscountAmount;
        const taxAmount = this.calculateTaxAmount(taxableAmount);
        const finalTotal = this.getTaxType() === "inclusive" ? taxableAmount : taxableAmount + taxAmount;

        if (this.elements.subtotalElement) {
            this.elements.subtotalElement.textContent =
                this.formatCurrency(subtotal);
        }
        if (this.elements.orderDiscountTotalElement) {
            this.elements.orderDiscountTotalElement.textContent =
                this.formatCurrency(orderDiscountAmount);
        }
        const totalTaxEl = document.getElementById("totalTax");
        if (totalTaxEl) {
            totalTaxEl.textContent = this.formatCurrency(taxAmount);
        }
        if (this.elements.finalTotalElement) {
            this.elements.finalTotalElement.textContent =
                this.formatCurrency(finalTotal);
        }
        if (this.elements.totalDiscountInput) {
            this.elements.totalDiscountInput.value = orderDiscountAmount;
        }
        const taxInput = document.getElementById("taxInput");
        if (taxInput) taxInput.value = taxAmount;

        if (this.elements.productsJsonInput) {
            const products = [];
            document
                .querySelectorAll("#po-items-table-body tr")
                .forEach((row) => {
                    const itemId =
                        row.querySelector(".quantity-input")?.dataset.itemId;
                    if (!itemId) {
                        return;
                    }

                    const quantity =
                        parseFloat(
                            row.querySelector(
                                `.quantity-input[data-item-id="${itemId}"]`,
                            ).value,
                        ) || 0;
                    const price =
                        parseFloat(
                            row.querySelector(
                                `.price-input[data-item-id="${itemId}"]`,
                            ).value,
                        ) || 0;
                    const discount =
                        parseFloat(
                            row.querySelector(
                                `.discount-input[data-item-id="${itemId}"]`,
                            ).value,
                        ) || 0;
                    const discountType = row.querySelector(
                        `.discount-type-input[data-item-id="${itemId}"]`,
                    ).value;

                    const expiryDateInput = row.querySelector(
                        `.expiry-date-input[data-item-id="${itemId}"]`,
                    );

                    products.push({
                        product_id: itemId,
                        quantity: quantity,
                        price: price,
                        discount: discount,
                        discountType: discountType,
                        expiry_date: expiryDateInput
                            ? expiryDateInput.value
                            : null,
                    });
                });
            this.elements.productsJsonInput.value = JSON.stringify(products);
        }

        const totalPaid = parseFloat(
            this.elements.totalPaidAmount.dataset.totalPaid,
        );
        const returnedAmount = parseFloat(
            this.elements.balanceAmount.dataset.returned || 0,
        );
        const newBalance = Math.max(0, finalTotal - totalPaid - returnedAmount);

        if (this.elements.balanceAmount) {
            this.elements.balanceAmount.textContent =
                this.formatCurrency(newBalance);
            this.elements.balanceAmount.dataset.balance = newBalance;
        }
    }

    serializeProducts(event) {
        event.preventDefault();

        if (this.isSubmitting) {
            return;
        }

        const status = this.elements.statusSelect.value;
        const balance = parseFloat(this.elements.balanceAmount.dataset.balance);

        if (status === "Paid" && balance > 0) {
            InventMagApp.showToast(
                "Warning",
                "Cannot mark as Paid. Please add a payment to cover the outstanding balance.",
                "warning",
            );
            return;
        }

        const products = [];
        document.querySelectorAll("#po-items-table-body tr").forEach((row) => {
            const itemId = row.querySelector(".quantity-input")?.dataset.itemId;
            if (!itemId) {
                return;
            }

            const quantity = parseFloat(
                row.querySelector(`.quantity-input[data-item-id="${itemId}"]`)
                    .value,
            );
            const price = parseFloat(
                row.querySelector(`.price-input[data-item-id="${itemId}"]`)
                    .value,
            );
            const discount = parseFloat(
                row.querySelector(`.discount-input[data-item-id="${itemId}"]`)
                    .value,
            );
            const discountType = row.querySelector(
                `.discount-type-input[data-item-id="${itemId}"]`,
            ).value;
            const expiryDateInput = row.querySelector(
                `.expiry-date-input[data-item-id="${itemId}"]`,
            );
            const expiry_date = expiryDateInput ? expiryDateInput.value : null;

            products.push({
                product_id: itemId,
                quantity: quantity,
                price: price,
                discount: discount,
                discountType: discountType,
                expiry_date: expiry_date,
            });
        });

        if (this.elements.productsJsonInput) {
            this.elements.productsJsonInput.value = JSON.stringify(products);
        }

        this.isSubmitting = true;
        this.elements.form.submit();
    }
}
