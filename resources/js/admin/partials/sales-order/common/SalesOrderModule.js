import { formatCurrency } from "../../../../utils/currencyFormatter.js";

export class SalesOrderModule {
    constructor(config = {}) {
        this.config = {
            ...config,
        };

        this.taxRate =
            parseFloat(document.getElementById("taxRateInput")?.value) || 0;
    }

    calculateDiscountAmount(price, quantity, discount, discountType) {
        const priceInCents = Math.round(price * 100);
        const discountInCents =
            discountType === "percentage"
                ? Math.round((priceInCents * discount) / 100)
                : Math.round(discount * 100);

        return (discountInCents * quantity) / 100;
    }

    calculateTotal(price, quantity, discount, discountType) {
        const priceInCents = Math.round(price * 100);
        const discountInCents =
            discountType === "percentage"
                ? Math.round((priceInCents * discount) / 100)
                : Math.round(discount * 100);

        const totalPerUnitInCents = priceInCents - discountInCents;
        const totalInCents = totalPerUnitInCents * quantity;

        return totalInCents / 100;
    }

    getTaxType() {
        const selector = document.getElementById("taxSelector");
        if (selector && selector.selectedIndex >= 0) {
            const opt = selector.options[selector.selectedIndex];
            return opt.dataset.type || "exclusive";
        }
        return document.getElementById("taxTypeInput")?.value || "exclusive";
    }

    getEffectiveTaxRate() {
        const selector = document.getElementById("taxSelector");
        if (selector && selector.selectedIndex >= 0) {
            const opt = selector.options[selector.selectedIndex];
            return parseFloat(opt.dataset.rate) || 0;
        }
        return this.taxRate;
    }

    calculateTaxAmount(taxableAmount) {
        const rate = this.getEffectiveTaxRate();
        const type = this.getTaxType();
        if (type === "none" || rate <= 0 || taxableAmount <= 0) {
            return 0;
        }
        if (type === "inclusive") {
            return Math.round((taxableAmount - (taxableAmount / (1 + rate / 100))) * 100) / 100;
        }
        return Math.round((taxableAmount * (rate / 100)) * 100) / 100;
    }

    calculatePphAmount(taxableAmount) {
        const pphRate = this.getPphRate();
        if (pphRate <= 0 || taxableAmount <= 0) return 0;
        return Math.round(taxableAmount * (pphRate / 100) * 100) / 100;
    }

    getPphRate() {
        const selector = document.getElementById('pphSelector');
        if (selector && selector.value) {
            const selected = selector.options[selector.selectedIndex];
            return parseFloat(selected.dataset.rate) || 0;
        }
        return 0;
    }

    isPphEnabled() {
        const selector = document.getElementById('pphSelector');
        return selector && selector.value !== '';
    }

    calculateOrderDiscount(subtotal, discountValue, discountType) {
        if (discountType === "percentage") {
            return (subtotal * discountValue) / 100;
        }
        return discountValue;
    }

    initFlatpickr(orderDateElement, dueDateElement) {
        const flatpickrConfig = {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d-m-Y",
            allowInput: true,
        };

        if (orderDateElement) {
            try {
                orderDateElement._flatpickr = flatpickr(orderDateElement, {
                    ...flatpickrConfig,
                    defaultDate: new Date(),
                });
            } catch (error) {
                // Flatpickr init failed — field will fall back to native validation
            }
        }

        if (dueDateElement) {
            try {
                dueDateElement._flatpickr = flatpickr(dueDateElement, flatpickrConfig);
            } catch (error) {
                // Flatpickr init failed — field will fall back to native validation
            }
        }
    }

    safeGetElement(id) {
        const element = document.getElementById(id);
        if (!element) {
            // console.warn(`Element with ID '${id}' not found`);
        }
        return element;
    }
}
