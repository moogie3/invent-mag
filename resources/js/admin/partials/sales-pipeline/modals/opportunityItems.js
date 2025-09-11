import { allProducts } from '../common/state.js';
import { formatCurrency } from '../../../../utils/currencyFormatter.js';

export function createProductItemRow(item = {}, index, containerId) {
    const itemDiv = document.createElement("div");
    itemDiv.className = "row g-2 align-items-end mb-2 product-item-row";
    itemDiv.dataset.index = index;

    const productId = item.product_id || "";
    const quantity = item.quantity || "";
    const price = parseFloat(item.price || 0).toFixed(
        currencySettings.decimalPlaces
    );

    let productOptions = '<option value="">Select Product</option>';
    allProducts.forEach((product) => {
        const selected = product.id == productId ? "selected" : "";
        productOptions += `<option value="${product.id}" data-price="${
            product.selling_price || 0
        }" data-stock="${product.stock_quantity || 0}" ${selected}>${
            product.name
        } (Stock: ${product.stock_quantity || 0})</option>`;
    });

    itemDiv.innerHTML = `
        <div class="col-md-5">
            <label for="${containerId}-product-${index}" class="form-label">Product ${
        index + 1
    }</label>
            <select class="form-select product-select" id="${containerId}-product-${index}" name="items[${index}][product_id]" required>
                ${productOptions}
            </select>
        </div>
        <div class="col-md-3">
            <label for="${containerId}-quantity-${index}" class="form-label">Quantity</label>
            <input type="number" class="form-control quantity-input" id="${containerId}-quantity-${index}" name="items[${index}][quantity]" value="${quantity}" min="1" max="${
        item.product ? item.product.stock_quantity : ""
    }" required>
        </div>
        <div class="col-md-3">
            <label for="${containerId}-price-${index}" class="form-label">Price</label>
            <input type="number" class="form-control price-input" id="${containerId}-price-${index}" name="items[${index}][price]" value="${price}" step="0.01" min="0" required>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-danger btn-icon remove-item-btn">
                <i class="ti ti-trash"></i>
            </button>
        </div>
    `;

    const quantityInput = itemDiv.querySelector(".quantity-input");
    const priceInput = itemDiv.querySelector(".price-input");
    const productSelect = itemDiv.querySelector(".product-select");

    productSelect.addEventListener("change", function () {
        const selectedOption = this.options[this.selectedIndex];
        const productPrice = parseFloat(selectedOption.dataset.price || 0);
        const productStock = parseFloat(selectedOption.dataset.stock || 0);
        priceInput.value = productPrice.toFixed(
            currencySettings.decimalPlaces
        );
        quantityInput.max = productStock;
        if (parseFloat(quantityInput.value) > productStock) {
            quantityInput.value = productStock;
            window.showToast(
                "Warning",
                `Quantity cannot exceed available stock (${maxStock}).`,
                "warning"
            );
        }
        calculateTotalAmount(containerId);
    });

    quantityInput.addEventListener("input", () => {
        const maxStock = parseFloat(quantityInput.max);
        if (parseFloat(quantityInput.value) > maxStock) {
            quantityInput.value = maxStock;
            window.showToast(
                "Warning",
                `Quantity cannot exceed available stock (${maxStock}).`,
                "warning"
            );
        }
        calculateTotalAmount(containerId);
    });
    priceInput.addEventListener("input", () =>
        calculateTotalAmount(containerId)
    );
    itemDiv
        .querySelector(".remove-item-btn")
        .addEventListener("click", function () {
            itemDiv.remove();
            calculateTotalAmount(containerId);
            updateProductItemLabels(containerId);
        });

    return itemDiv;
}

export function updateProductItemLabels(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const itemRows = container.querySelectorAll(".product-item-row");
    itemRows.forEach((row, index) => {
        const newIndex = index;
        row.dataset.index = newIndex;

        const productLabel = row.querySelector(
            `label[for^='${containerId}-product-']`
        );
        if (productLabel) {
            productLabel.textContent = `Product ${newIndex + 1}`;
            productLabel.setAttribute(
                "for",
                `${containerId}-product-${newIndex}`
            );
        }

        const productSelect = row.querySelector(".product-select");
        if (productSelect) {
            productSelect.id = `${containerId}-product-${newIndex}`;
            productSelect.name = `items[${newIndex}][product_id]`;
        }

        const quantityLabel = row.querySelector(
            `label[for^='${containerId}-quantity-']`
        );
        const quantityInput = row.querySelector(".quantity-input");
        if (quantityInput) {
            quantityInput.id = `${containerId}-quantity-${newIndex}`;
            quantityInput.name = `items[${newIndex}][quantity]`;
            if (quantityLabel)
                quantityLabel.setAttribute(
                    "for",
                    `${containerId}-quantity-${newIndex}`
                );
        }

        const priceLabel = row.querySelector(
            `label[for^='${containerId}-price-']`
        );
        const priceInput = row.querySelector(".price-input");
        if (priceInput) {
            priceInput.id = `${containerId}-price-${newIndex}`;
            priceInput.name = `items[${newIndex}][price]`;
            if (priceLabel)
                priceLabel.setAttribute(
                    "for",
                    `${containerId}-price-${newIndex}`
                );
        }
    });
}

export function calculateTotalAmount(containerId) {
    let total = 0;
    const container = document.getElementById(containerId);
    const itemRows = container.querySelectorAll(".product-item-row");
    itemRows.forEach((row) => {
        const quantity =
            parseFloat(row.querySelector(".quantity-input").value) || 0;
        const price =
            parseFloat(row.querySelector(".price-input").value) || 0;
        total += quantity * price;
    });

    const newOpportunityTotalAmountInput = document.getElementById(
        "newOpportunityTotalAmount"
    );
    const editOpportunityTotalAmountInput = document.getElementById(
        "editOpportunityTotalAmount"
    );

    if (containerId === "newOpportunityItemsContainer") {
        newOpportunityTotalAmountInput.value = formatCurrency(total);
    } else if (containerId === "editOpportunityItemsContainer") {
        editOpportunityTotalAmountInput.value = formatCurrency(total);
    }
}
