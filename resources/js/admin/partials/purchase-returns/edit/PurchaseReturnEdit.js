import { formatCurrency } from '../../../../utils/currencyFormatter.js';

export class PurchaseReturnEdit {
    constructor() {
        this.form = document.getElementById('purchase-return-edit-form');
        if (!this.form) {
            return; // Stop if the form is not on the page
        }

        this.purchaseSelect = document.getElementById('purchase-select');
        this.productReturnList = document.getElementById('product-return-list');
        this.totalAmountInput = document.getElementById('total-amount');
        this.itemsInput = document.getElementById('items-json');
        this.existingItemsInput = document.getElementById('purchase-return-items');

        this.returnedItems = {}; // This will hold the current state of returned items, keyed by PO item ID
        this.allPurchaseItems = []; // This will hold all items from the original purchase

        this.init();
    }

    async init() {
        if (!this.purchaseSelect || !this.productReturnList || !this.totalAmountInput || !this.itemsInput || !this.existingItemsInput) {
            console.error('Required elements not found for PurchaseReturnEdit');
            return;
        }

        this.productReturnList.addEventListener('input', this.handleQuantityChange.bind(this));
        this.form.addEventListener('submit', this.handleSubmit.bind(this));

        // Initialize the component
        await this.loadInitialData();
    }

    async loadInitialData() {
        const purchaseId = this.purchaseSelect.value;
        const existingItemsJson = this.existingItemsInput.value;
        const existingItems = existingItemsJson ? JSON.parse(existingItemsJson) : [];

        if (purchaseId) {
            try {
                const response = await fetch(`/admin/por/purchase/${purchaseId}`);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                this.allPurchaseItems = await response.json();

                // Match existing return items to PO items (best effort due to schema limitation)
                const tempExisting = [...existingItems];
                this.allPurchaseItems.forEach(poItem => {
                    const matchIndex = tempExisting.findIndex(exItem => exItem.product_id === poItem.product_id);
                    if (matchIndex > -1) {
                        const matchedItem = tempExisting[matchIndex];
                        this.returnedItems[poItem.id] = { // Key by po_item_id
                            product_id: poItem.product_id,
                            returned_quantity: matchedItem.quantity,
                            price: matchedItem.price,
                        };
                        // Remove the matched item so it's not used again for another PO line
                        tempExisting.splice(matchIndex, 1);
                    }
                });

                this.populateItemsTable();
            } catch (error) {
                console.error('Error fetching purchase items:', error);
                this.productReturnList.innerHTML = '<p class="text-danger">Error loading items. Please try again.</p>';
            }
        }
    }

    populateItemsTable() {
        if (!this.allPurchaseItems || this.allPurchaseItems.length === 0) {
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

        this.allPurchaseItems.forEach(item => {
            const unitPrice = parseFloat(item.price || 0);
            const returnedItem = this.returnedItems[item.id]; // Use PO item ID as key
            const returnedQuantity = returnedItem ? returnedItem.returned_quantity : 0;
            const itemTotal = unitPrice * returnedQuantity;

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
                            value="${returnedQuantity}"
                            style="width: 100px;">
                    </td>
                    <td>${formatCurrency(unitPrice)}</td>
                    <td class="item-total text-end">${formatCurrency(itemTotal)}</td>
                </tr>
            `;
        });

        tableHTML += `
                    </tbody>
                </table>
            </div>
        `;

        this.productReturnList.innerHTML = tableHTML;
        this.updateTotalAmount(); // Initial total calculation
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
        if (row) {
            row.querySelector('.item-total').textContent = formatCurrency(itemTotal);
        }

        if (quantity > 0) {
            this.returnedItems[itemId] = {
                product_id: productId,
                returned_quantity: quantity,
                price: price,
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