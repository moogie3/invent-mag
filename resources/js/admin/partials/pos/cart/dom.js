import { getProducts } from './state.js';
import { formatCurrency } from '../../../../utils/currencyFormatter.js';
import { calculateTotals } from './totals.js';

const productList = document.getElementById("productList");
const productsField = document.getElementById("productsField");

export function renderList() {
    const products = getProducts();
    const existingItems = Array.from(productList.children);
    const productIdsInCart = products.map(p => p.id);

    if (products.length === 0) {
        productList.innerHTML = "";
        const emptyMessage = document.createElement("div");
        emptyMessage.classList.add("text-center", "py-4", "text-muted");
        emptyMessage.innerHTML =
            '<i class="ti ti-shopping-cart text-muted" style="font-size: 3rem;"></i>' +
            '<p class="mt-2">Your cart is empty</p>';
        productList.appendChild(emptyMessage);
        calculateTotals();
        productsField.value = JSON.stringify(products);
        return;
    }

    existingItems.forEach(item => {
        const itemId = parseInt(item.dataset.cartItemId);
        if (!productIdsInCart.includes(itemId)) {
            item.remove();
        }
    });

    products.forEach((product, index) => {
        let item = document.querySelector(`[data-cart-item-id="${product.id}"]`);

        if (item) {
            const quantityInput = item.querySelector(".quantity-input");
            const priceInput = item.querySelector(".price-input");
            const totalElement = item.querySelector("strong");

            if (quantityInput) quantityInput.value = product.quantity;
            if (priceInput) priceInput.value = product.price;
            if (totalElement) totalElement.textContent = formatCurrency(product.total);

            item.querySelectorAll("[data-index]").forEach(el => {
                el.dataset.index = index;
            });

        } else {
            item = document.createElement("div");
            item.classList.add(
                "list-group-item",
                "d-flex",
                "justify-content-between",
                "align-items-center",
                "border-0",
                "border-bottom"
            );
            item.setAttribute("data-cart-item-id", product.id);

            const productInfo = document.createElement("div");
            productInfo.classList.add("flex-grow-1");
            productInfo.innerHTML = `
            <strong class="d-block">${product.name}</strong>
            <div class="d-flex align-items-center mt-1">
                <input type="number" class="form-control form-control-sm quantity-input" value="${product.quantity}" min="1" data-index="${index}" style="width: 60px;">
                <span class="text-muted mx-2">x</span>
                <div class="input-group input-group-sm" style="width: 120px;">
                    <span class="input-group-text">${window.currencySettings.currency_symbol}</span>
                    <input type="number" class="form-control price-input" value="${product.price}" min="0" data-index="${index}">
                </div>
                <span class="text-muted ms-2">/ ${product.unit}</span>
            </div>
        `;

            const actionsSection = document.createElement("div");
            actionsSection.classList.add(
                "d-flex",
                "justify-content-end",
                "align-items-center",
                "gap-2",
                "ms-2"
            );
            actionsSection.innerHTML = `
            <strong>${formatCurrency(product.total)}</strong>
            <div class="btn-group" style="gap: 0.25rem;">
                <button class="btn btn-outline-success increase-product" data-index="${index}" title="Increase" style="padding: 0.4rem 0.6rem; font-size: 0.85rem;">
                    <i class="ti ti-plus"></i>
                </button>
                <button class="btn btn-outline-warning decrease-product" data-index="${index}" title="Decrease" style="padding: 0.4rem 0.6rem; font-size: 0.85rem;">
                    <i class="ti ti-minus"></i>
                </button>
                <button class="btn btn-outline-danger remove-product" data-index="${index}" title="Remove" style="padding: 0.4rem 0.6rem; font-size: 0.85rem;">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        `;

            item.appendChild(productInfo);
            item.appendChild(actionsSection);

            productList.appendChild(item);
        }
    });

    calculateTotals();
    productsField.value = JSON.stringify(products);
}
