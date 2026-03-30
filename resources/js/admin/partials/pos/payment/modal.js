import { getProducts, setProducts, saveProductsToCache } from '../cart/state.js';
import { subtotal, orderDiscount, taxAmount, grandTotal } from '../cart/totals.js';
import { formatCurrency } from '../../../../utils/currencyFormatter.js';
import { renderList } from '../cart/dom.js';

let _paymentModal = null;
let _insufficientStockModal = null;

function getPaymentModal() {
    if (!_paymentModal) {
        const el = document.getElementById("paymentModal");
        _paymentModal = el ? new bootstrap.Modal(el, {}) : null;
    }
    return _paymentModal;
}

function getInsufficientStockModal() {
    if (!_insufficientStockModal) {
        const el = document.getElementById("insufficientStockModal");
        _insufficientStockModal = el ? new bootstrap.Modal(el, {}) : null;
    }
    return _insufficientStockModal;
}

export async function validateCartStock() {
    const products = getProducts();
    const insufficientItems = [];

    for (const product of products) {
        try {
            const response = await fetch(`/admin/product/${product.id}/main-stock`);
            if (response.ok) {
                const data = await response.json();
                const availableStock = data.main_warehouse_stock || 0;
                if (product.quantity > availableStock) {
                    insufficientItems.push({
                        ...product,
                        availableStock: availableStock,
                        requestedQuantity: product.quantity,
                    });
                }
            }
        } catch (error) {
            console.error('Error validating stock:', error);
        }
    }

    return insufficientItems;
}

export function showInsufficientStockWarning(insufficientItems) {
    return new Promise((resolve) => {
        const modal = getInsufficientStockModal();
        if (!modal) {
            resolve('cancel');
            return;
        }

        const modalBody = document.getElementById("insufficientStockList");
        if (!modalBody) {
            resolve('cancel');
            return;
        }

        modalBody.innerHTML = '';
        insufficientItems.forEach((item) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.name}</td>
                <td class="text-center">${item.requestedQuantity}</td>
                <td class="text-center text-danger">${item.availableStock}</td>
            `;
            modalBody.appendChild(row);
        });

        const modalEl = document.getElementById("insufficientStockModal");
        const adjustBtn = document.getElementById("adjustToAvailableBtn");
        const removeBtn = document.getElementById("removeInsufficientBtn");
        const cancelBtn = document.getElementById("cancelCheckoutBtn");

        if (!modalEl || !adjustBtn || !removeBtn || !cancelBtn) {
            resolve('cancel');
            return;
        }

        const cleanup = () => {
            modalEl.removeEventListener('hidden.bs.modal', cleanup);
            adjustBtn.removeEventListener('click', onAdjust);
            removeBtn.removeEventListener('click', onRemove);
            cancelBtn.removeEventListener('click', onCancel);
        };

        const onAdjust = () => {
            cleanup();
            modal.hide();
            resolve('adjust');
        };

        const onRemove = () => {
            cleanup();
            modal.hide();
            resolve('remove');
        };

        const onCancel = () => {
            cleanup();
            modal.hide();
            resolve('cancel');
        };

        modalEl.addEventListener('hidden.bs.modal', cleanup);
        adjustBtn.addEventListener('click', onAdjust);
        removeBtn.addEventListener('click', onRemove);
        cancelBtn.addEventListener('click', onCancel);

        modal.show();
    });
}

export function handleInsufficientStockAction(action, insufficientItems) {
    const products = getProducts();

    if (action === 'adjust') {
        insufficientItems.forEach((insufficient) => {
            const product = products.find((p) => p.id === insufficient.id);
            if (product) {
                product.quantity = insufficient.availableStock;
                product.total = product.quantity * product.price;
            }
        });
        setProducts(products);
        saveProductsToCache();
        renderList();
    } else if (action === 'remove') {
        const insufficientIds = insufficientItems.map((i) => i.id);
        const updatedProducts = products.filter((p) => !insufficientIds.includes(parseInt(p.id)));
        setProducts(updatedProducts);
        saveProductsToCache();
        renderList();
    }
}

export async function populatePaymentModal() {
    const insufficientItems = await validateCartStock();

    if (insufficientItems.length > 0) {
        const action = await showInsufficientStockWarning(insufficientItems);
        if (action === 'cancel') {
            return false;
        }
        handleInsufficientStockAction(action, insufficientItems);
    }

    const paymentModal = getPaymentModal();
    if (!paymentModal) return false;
    
    const modalProductList = document.getElementById("modalProductList");
    const modalSubtotal = document.getElementById("modalSubtotal");
    const modalDiscount = document.getElementById("modalDiscount");
    const modalTax = document.getElementById("modalTax");
    const modalGrandTotal = document.getElementById("modalGrandTotal");
    const amountReceived = document.getElementById("amountReceived");
    const changeAmount = document.getElementById("changeAmount");
    const orderDiscountElement = document.getElementById("orderDiscount");
    const discountTypeElement = document.getElementById("discountType");
    const taxRateElement = document.getElementById("taxRate");
    
    if (!modalProductList || !modalSubtotal) return false;
    
    const products = getProducts();
    modalProductList.innerHTML = "";

    products.forEach((product) => {
        const row = document.createElement("tr");
        row.innerHTML = `
        <td>${product.name}</td>
        <td class="text-center">${product.quantity} ${product.unit}</td>
        <td class="text-end">${formatCurrency(Math.round(product.price))}</td>
        <td class="text-end">${formatCurrency(Math.round(product.total))}</td>
    `;
        modalProductList.appendChild(row);
    });

    const discountValue = parseFloat(orderDiscountElement?.value || 0);
    const discountType = discountTypeElement?.value ?? 'fixed';
    const taxRate = parseFloat(taxRateElement?.value || 0);

    let discountText = "";
    if (discountType === "percentage") {
        discountText = `(${discountValue}%)`;
    } else {
        discountText = "(Fixed)";
    }

    let taxText = `(${taxRate}%)`;

    modalSubtotal.textContent = formatCurrency(Math.round(subtotal));
    modalDiscount.textContent = `${formatCurrency(
        Math.round(orderDiscount)
    )} ${discountText}`;
    modalTax.textContent = `${formatCurrency(Math.round(taxAmount))} ${taxText}`;
    modalGrandTotal.textContent = formatCurrency(Math.round(grandTotal));

    if (amountReceived) amountReceived.value = "";
    if (changeAmount) changeAmount.textContent = formatCurrency(0);

    if (paymentModal) paymentModal.show();
    return true;
}
