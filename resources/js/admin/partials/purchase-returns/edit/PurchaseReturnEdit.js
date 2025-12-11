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

        this.returnedItems = {}; // This will hold the current state of returned items
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

        // Store existing returned items in the state
        existingItems.forEach(item => {
            this.returnedItems[item.product_id] = { // Use product_id as a key
                product_id: item.product_id,
                returned_quantity: item.quantity,
                price: item.price,
            };
        });

        if (purchaseId) {
            try {
                const response = await fetch(`/admin/por/purchase/${purchaseId}`);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                this.allPurchaseItems = await response.json();
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
            const returnedItem = this.returnedItems[item.product.id];
            const returnedQuantity = returnedItem ? returnedItem.returned_quantity : 0;
            const itemTotal = unitPrice * returnedQuantity;

            tableHTML += `
                <tr>
                    <td>${item.product.name}</td>
                    <td>${item.quantity}</td>
                    <td>
                        <input type="number"
                            class="form-control quantity-input"
                            data-product-id="${item.product.id}"
                            data-price="${unitPrice}"
                            min="0"
                            max="${item.quantity}"
                            value="${returnedQuantity}"
                            style="width: 100px;">
                    </td>
                    <td>${unitPrice.toFixed(2)}</td>
                    <td class="item-total text-end">${itemTotal.toFixed(2)}</td>
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
            row.querySelector('.item-total').textContent = itemTotal.toFixed(2);
        }

        if (quantity > 0) {
            this.returnedItems[productId] = {
                product_id: productId,
                returned_quantity: quantity,
                price: price,
            };
        } else {
            delete this.returnedItems[productId];
        }

        this.updateTotalAmount();
    }

    updateTotalAmount() {
        let totalAmount = 0;
        for (const productId in this.returnedItems) {
            const item = this.returnedItems[productId];
            totalAmount += item.price * item.returned_quantity;
        }
        this.totalAmountInput.value = totalAmount.toFixed(2);
    }

    handleSubmit(event) {
        const itemsArray = Object.values(this.returnedItems);
        if (itemsArray.length === 0) {
            event.preventDefault();
            alert('No items selected for return.');
            return;
        }
        this.itemsInput.value = JSON.stringify(itemsArray);
    }
}
