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
