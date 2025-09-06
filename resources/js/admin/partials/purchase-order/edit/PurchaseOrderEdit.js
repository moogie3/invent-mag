import { PurchaseOrderModule } from '../common/PurchaseOrderModule.js';

export class PurchaseOrderEdit extends PurchaseOrderModule {
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
            form: document.getElementById("edit-po-form"),
            productsJsonInput: document.getElementById("products-json"),
        };
    }

    initEventListeners() {
        document.addEventListener("input", (event) => {
            if (
                event.target.matches(
                    ".quantity-input, .price-input, .discount-input"
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
                this.serializeProducts.bind(this)
            );
        }
    }

    calculateAllAmounts() {
        this.calculateOrderTotal();
    }

    calculateOrderTotal() {
        let subtotal = 0;

        document.querySelectorAll("tbody tr").forEach((row) => {
            const itemId = row.querySelector(".quantity-input")?.dataset.itemId;
            if (!itemId) return;

            const quantity =
                parseFloat(
                    row.querySelector(
                        `.quantity-input[data-item-id="${itemId}"]`
                    ).value
                ) || 0;
            const price =
                parseFloat(
                    row.querySelector(`.price-input[data-item-id="${itemId}"]`)
                        .value
                ) || 0;
            const discountInput = row.querySelector(
                `.discount-input[data-item-id="${itemId}"]`
            );
            const discountTypeSelect = row.querySelector(
                `.discount-type-input[data-item-id="${itemId}"]`
            );

            const discountValue = parseFloat(discountInput?.value) || 0;
            const discountType = discountTypeSelect?.value || "fixed";

            const itemTotal = this.calculateTotal(
                price,
                quantity,
                discountValue,
                discountType
            );

            const amountInput = row.querySelector(
                `.amount-input[data-item-id="${itemId}"]`
            );
            if (amountInput) {
                amountInput.value = Math.round(itemTotal);
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
            discountTotalType
        );
        const finalTotal = subtotal - orderDiscountAmount;

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

        if (this.elements.productsJsonInput) {
            const products = [];
            document.querySelectorAll("tbody tr").forEach((row) => {
                const itemId =
                    row.querySelector(".quantity-input")?.dataset.itemId;
                if (!itemId) {
                    return;
                }

                const quantity =
                    parseFloat(
                        row.querySelector(
                            `.quantity-input[data-item-id="${itemId}"]`
                        ).value
                    ) || 0;
                const price =
                    parseFloat(
                        row.querySelector(
                            `.price-input[data-item-id="${itemId}"]`
                        ).value
                    ) || 0;
                const discount =
                    parseFloat(
                        row.querySelector(
                            `.discount-input[data-item-id="${itemId}"]`
                        ).value
                    ) || 0;
                const discountType = row.querySelector(
                    `.discount-type-input[data-item-id="${itemId}"]`
                ).value;

                products.push({
                    product_id: itemId,
                    quantity: quantity,
                    price: price,
                    discount: discount,
                    discount_type: discountType,
                });
            });
            this.elements.productsJsonInput.value = JSON.stringify(products);
        }
    }

    serializeProducts() {
        const products = [];
        document.querySelectorAll("tbody tr").forEach((row) => {
            const itemId = row.querySelector(".quantity-input")?.dataset.itemId;
            if (!itemId) {
                return;
            }

            const quantity = parseFloat(
                row.querySelector(`.quantity-input[data-item-id="${itemId}"]`)
                    .value
            );
            const price = parseFloat(
                row.querySelector(`.price-input[data-item-id="${itemId}"]`)
                    .value
            );
            const discount = parseFloat(
                row.querySelector(`.discount-input[data-item-id="${itemId}"]`)
                    .value
            );
            const discountType = row.querySelector(
                `.discount-type-input[data-item-id="${itemId}"]`
            ).value;

            products.push({
                product_id: itemId,
                quantity: quantity,
                price: price,
                discount: discount,
                discount_type: discountType,
            });
        });

        if (this.elements.productsJsonInput) {
            this.elements.productsJsonInput.value = JSON.stringify(products);
        }
    }
}
