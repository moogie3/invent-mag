import { formatCurrency } from "../../../../utils/currencyFormatter.js";

export class PurchaseReturnCreate {
    constructor() {
        this.purchaseSelect = document.getElementById('purchase-select');
        this.productReturnList = document.getElementById('product-return-list');
        this.totalAmountInput = document.getElementById('total-amount');
        this.itemsInput = document.getElementById('items-json');
        this.returnedItems = {};
        this.init();
    }

    init() {
        if (!this.purchaseSelect || !this.productReturnList || !this.totalAmountInput || !this.itemsInput) {
            console.error('Required elements not found for PurchaseReturnCreate');
            return;
        }

        const returnForm = document.getElementById('purchase-return-form');
        if (!returnForm) {
            console.error('Return form element not found');
            return;
        }

        this.purchaseSelect.addEventListener('change', this.handlePurchaseChange.bind(this));
        this.productReturnList.addEventListener('input', this.handleQuantityChange.bind(this));
        returnForm.addEventListener('submit', this.handleSubmit.bind(this));
    }

    handlePurchaseChange() {
        const purchaseId = this.purchaseSelect.value;
        this.returnedItems = {};
        this.updateTotalAmount();
        this.itemsInput.value = '';

        if (purchaseId) {
            fetch(`/admin/por/purchase/${purchaseId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => this.populateItems(data))
                .catch(error => {
                    console.error('Error fetching purchase items:', error);
                    this.productReturnList.innerHTML = '<p class="text-danger">Error loading items. Please try again.</p>';
                });
        } else {
            this.productReturnList.innerHTML = `
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-shopping-cart fs-1"></i>
                    </div>
                    <p class="empty-title">No items selected</p>
                    <p class="empty-subtitle text-muted">Select a purchase order to see its items</p>
                </div>
            `;
        }
    }

    populateItems(items) {
        if (!items || items.length === 0) {
            this.productReturnList.innerHTML = '<div class="text-muted p-4 text-center">No items found in this purchase.</div>';
            return;
        }

        let tableHTML = `
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Product</th>
                            <th>Purchased Qty</th>
                            <th>Return Qty</th>
                            <th>Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        items.forEach(item => {
            const unitPrice = parseFloat(item.price || 0);
            tableHTML += `
                <tr>
                    <td>${item.product.name}</td>
                    <td>${item.quantity}</td>
                    <td>
                        <input type="number"
                            class="form-control quantity-input"
                            data-item-id="${item.id}"
                            data-product-id="${item.product.id}"
                            data-price="${unitPrice}"
                            min="0"
                            max="${item.quantity}"
                            value="0"
                            style="width: 100px;">
                    </td>
                    <td>${formatCurrency(unitPrice)}</td>
                    <td class="item-total text-end">${formatCurrency(0)}</td>
                </tr>
            `;
        });

        tableHTML += `
                    </tbody>
                </table>
            </div>
        `;

        this.productReturnList.innerHTML = tableHTML;
    }

    handleQuantityChange(event) {
        const input = event.target;
        if (!input.classList.contains('quantity-input')) return;

        const itemId = input.dataset.itemId;
        const productId = input.dataset.productId;
        const price = parseFloat(input.dataset.price);
        let quantity = parseInt(input.value, 10) || 0;
        const maxQuantity = parseInt(input.max, 10);

        if (quantity > maxQuantity) {
            quantity = maxQuantity;
            input.value = maxQuantity;
        } else if (quantity < 0) {
            quantity = 0;
            input.value = 0;
        }

        const itemTotal = price * quantity;
        const row = input.closest('tr');
        if(row) {
            row.querySelector('.item-total').textContent = formatCurrency(itemTotal);
        }

        if (quantity > 0) {
            this.returnedItems[itemId] = {
                product_id: productId,
                returned_quantity: quantity,
                price: price
            };
        } else {
            delete this.returnedItems[itemId];
        }

        this.updateTotalAmount();
    }

    updateTotalAmount() {
        let totalAmount = 0;
        for (const itemId in this.returnedItems) {
            const item = this.returnedItems[itemId];
            totalAmount += item.price * item.returned_quantity;
        }
        this.totalAmountInput.value = formatCurrency(totalAmount);
    }

    handleSubmit(event) {
        const itemsArray = Object.values(this.returnedItems);
        if (itemsArray.length === 0) {
            event.preventDefault();
            alert('No items selected for return.');
            return;
        }

        const plainTotalAmount = Object.values(this.returnedItems).reduce((sum, item) => sum + (item.price * item.returned_quantity), 0);
        this.totalAmountInput.value = plainTotalAmount.toFixed(2);

        this.itemsInput.value = JSON.stringify(itemsArray.map(item => ({
            ...item,
            returned_quantity: parseInt(item.returned_quantity, 10)
        })));
    }
}
