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

    calculateOrderDiscount(subtotal, discountValue, discountType) {
        if (discountType === "percentage") {
            return (subtotal * discountValue) / 100;
        }
        return discountValue;
    }

    initFlatpickr(orderDateElement, dueDateElement) {
        if (orderDateElement) {
            flatpickr(orderDateElement, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d-m-Y",
                defaultDate: new Date(),
                allowInput: true,
            });
        }

        if (dueDateElement) {
            flatpickr(dueDateElement, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d-m-Y",
                allowInput: true,
            });
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
