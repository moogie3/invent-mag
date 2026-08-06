import { PurchaseOrderModule } from "../common/PurchaseOrderModule.js";
import { validateForm } from "../../../utils/formValidator.js";

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
                    const taxLabel = document.getElementById("taxLabel");
                    const typeInput = document.getElementById("taxTypeInput");
                    const rateInput = document.getElementById("taxRateInput");
                    if (taxLabel) {
                        const base = taxLabel.dataset.base || taxLabel.textContent;
                        const rate = parseFloat(opt.dataset.rate) || 0;
                        taxLabel.textContent = opt.value && opt.dataset.name ? base + ' (' + opt.dataset.name + ' ' + rate + '%)' : base;
                    }
                    if (typeInput) typeInput.value = opt.dataset.type || "none";
                    if (rateInput) rateInput.value = parseFloat(opt.dataset.rate) || 0;
                    this.calculateOrderTotal();
                }
            });

            // Sync hidden inputs on initial page load from the selected option
            const initialOpt = taxSelector.options[taxSelector.selectedIndex];
            if (initialOpt) {
                const taxLabel = document.getElementById("taxLabel");
                const typeInput = document.getElementById("taxTypeInput");
                const rateInput = document.getElementById("taxRateInput");
                if (taxLabel) {
                    const base = taxLabel.dataset.base || taxLabel.textContent;
                    const rate = parseFloat(initialOpt.dataset.rate) || 0;
                    taxLabel.textContent = initialOpt.value && initialOpt.dataset.name ? base + ' (' + initialOpt.dataset.name + ' ' + rate + '%)' : base;
                }
                if (typeInput && initialOpt.dataset.type) typeInput.value = initialOpt.dataset.type;
                if (rateInput && initialOpt.dataset.rate) rateInput.value = parseFloat(initialOpt.dataset.rate) || 0;
            }
        }

        const pphSelector = document.getElementById("pphSelector");
        if (pphSelector) {
            document.body.addEventListener("change", (e) => {
                if (e.target && e.target.id === "pphSelector") {
                    const pphInput = document.getElementById("pphEnabledInput");
                    const pphRateInput = document.getElementById("pphRateInput");
                    const pphTaxIdInput = document.getElementById("pphTaxIdInput");
                    if (e.target.value) {
                        const opt = e.target.options[e.target.selectedIndex];
                        if (pphInput) pphInput.value = "1";
                        if (pphRateInput) pphRateInput.value = opt.dataset.rate;
                        if (pphTaxIdInput) pphTaxIdInput.value = e.target.value;
                    } else {
                        if (pphInput) pphInput.value = "0";
                        if (pphRateInput) pphRateInput.value = "0";
                        if (pphTaxIdInput) pphTaxIdInput.value = "";
                    }
                    this.calculateOrderTotal();
                }
            });

            const initialPphOpt = pphSelector.options[pphSelector.selectedIndex];
            if (initialPphOpt && initialPphOpt.value) {
                const pphRateInput = document.getElementById("pphRateInput");
                const pphTaxIdInput = document.getElementById("pphTaxIdInput");
                if (pphRateInput) pphRateInput.value = initialPphOpt.dataset.rate || 0;
                if (pphTaxIdInput) pphTaxIdInput.value = initialPphOpt.value;
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
        const baseTotal = this.getTaxType() === "inclusive" ? taxableAmount : taxableAmount + taxAmount;

        const pphAmount = this.calculatePphAmount(taxableAmount);
        const pphAmountInput = document.getElementById("pphAmountInput");
        if (pphAmountInput) pphAmountInput.value = pphAmount;
        const finalTotal = baseTotal - pphAmount;

        // PPN Masukan (Input VAT) — auto-derived from the selected tax is_ppn flag
        const taxSelector = document.getElementById("taxSelector");
        const selectedTaxOpt =
            taxSelector && taxSelector.selectedIndex >= 0
                ? taxSelector.options[taxSelector.selectedIndex]
                : null;
        const ppnEnabled =
            !!selectedTaxOpt && selectedTaxOpt.dataset.isPpn === "1";
        const ppnRate = ppnEnabled
            ? parseFloat(selectedTaxOpt.dataset.rate) || 0
            : 0;
        const ppnAmount = ppnEnabled ? taxAmount : 0;

        const ppnRow = document.getElementById("ppnMasukanRow");
        const ppnRateLabel = document.getElementById("ppnMasukanRateLabel");
        const ppnTotalDisplay = document.getElementById("ppnMasukanTotal");
        const ppnEnabledInput = document.getElementById("ppnEnabledInput");
        const ppnRateInput = document.getElementById("ppnRateInput");
        const ppnAmountInput = document.getElementById("ppnAmountInput");

        if (ppnRow) ppnRow.style.display = ppnEnabled ? "" : "none";
        if (ppnRateLabel) ppnRateLabel.textContent = ppnRate;
        if (ppnTotalDisplay)
            ppnTotalDisplay.textContent = this.formatCurrency(ppnAmount);
        if (ppnEnabledInput) ppnEnabledInput.value = ppnEnabled ? "1" : "0";
        if (ppnRateInput) ppnRateInput.value = ppnRate;
        if (ppnAmountInput) ppnAmountInput.value = ppnAmount;

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

        // Update discount label with type info
        const discountLabel = document.getElementById('discountLabel');
        if (discountLabel) {
            const base = discountLabel.dataset.base || 'Order Discount';
            const val = parseFloat(this.elements.discountTotalValue?.value) || 0;
            const type = this.elements.discountTotalType?.value || 'fixed';
            discountLabel.textContent = val > 0
                ? (type === 'percentage' ? base + ' (' + val + '%)' : base + ' (Rp ' + val.toLocaleString() + ')')
                : base;
        }

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
        if (event.defaultPrevented) return;
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
