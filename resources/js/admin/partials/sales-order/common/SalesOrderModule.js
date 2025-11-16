import { formatCurrency } from '../../../../utils/currencyFormatter.js';

export class SalesOrderModule {
    constructor(config = {}) {
        this.config = {
            ...config,
        };

        this.taxRate =
            parseFloat(document.getElementById("taxRateInput")?.value) || 0;
    }

    calculateDiscountAmount(price, quantity, discount, discountType) {
        if (discountType === "percentage") {
            return ((price * discount) / 100) * quantity;
        }
        return discount * quantity;
    }

    calculateTotal(price, quantity, discount, discountType) {
        const discountAmount = this.calculateDiscountAmount(
            price,
            quantity,
            discount,
            discountType
        );
        return price * quantity - discountAmount;
    }

    calculateOrderDiscount(subtotal, discount, discountType) {
        if (discountType === "percentage") {
            return (subtotal * discount) / 100;
        }
        return discount;
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
