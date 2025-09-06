export class PurchaseOrderModule {
    constructor(config = {}) {
        this.config = {
            currency: "IDR",
            locale: "id-ID",
            ...config,
        };
    }

    formatCurrency(amount) {
        const currencySettings = this.config;
        return new Intl.NumberFormat(currencySettings.locale, {
            style: "currency",
            currency: currencySettings.currency_code,
            maximumFractionDigits: currencySettings.decimal_places,
        }).format(amount);
    }

    calculateTotal(price, quantity, discount, discountType) {
        const priceInCents = Math.round(price * 100);
        const discountInCents =
            discountType === "percentage"
                ? Math.round((priceInCents * discount) / 100)
                : Math.round(discount * 100);

        const totalPerUnitInCents = priceInCents - discountInCents;
        const totalInCents = totalPerUnitInCents * quantity;

        return Math.round(totalInCents / 100);
    }

    calculateDiscount(subtotal, discountValue, discountType) {
        if (discountType === "percentage") {
            return Math.round((subtotal * discountValue) / 100);
        }
        return discountValue;
    }

    safeGetElement(id) {
        try {
            return document.getElementById(id);
        } catch (error) {
            console.warn(`Element with ID '${id}' not found:`, error);
            return null;
        }
    }
}
