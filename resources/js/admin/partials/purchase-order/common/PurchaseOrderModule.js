import { formatCurrency } from "../../../../utils/currencyFormatter.js";

export class PurchaseOrderModule {
    constructor(config = {}) {
        this.config = {
            ...config,
        };
        this.formatCurrency = formatCurrency;
        this.taxRate =
            parseFloat(document.getElementById("taxRateInput")?.value) || 0;
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

    calculateDiscount(subtotal, discountValue, discountType) {
        if (discountType === "percentage") {
            return (subtotal * discountValue) / 100;
        }
        return discountValue;
    }

    getTaxType() {
        return document.getElementById("taxTypeInput")?.value || "none";
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

    safeGetElement(id) {
        try {
            return document.getElementById(id);
        } catch (error) {
            return null;
        }
    }
}
