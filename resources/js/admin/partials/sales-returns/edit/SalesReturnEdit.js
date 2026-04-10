import { formatCurrency } from '../../../../utils/currencyFormatter.js';

export class SalesReturnEdit {
    constructor() {
        this.form = document.getElementById('sales-return-edit-form');
        if (!this.form) {
            return; // Stop if the form is not on the page
        }

        this.salesSelect = document.getElementById('sales-select');
        this.productReturnList = document.getElementById('product-return-list');
        this.totalAmountInput = document.getElementById('total-amount');
        this.itemsInput = document.getElementById('items-json');
        this.existingItemsInput = document.getElementById('sales-return-items');

        this.returnedItems = {}; // This will hold the current state of returned items, keyed by Sales item ID
        this.allSalesItems = []; // This will hold all items from the original sales

        this.init();
    }

    async init() {
        if (!this.salesSelect || !this.productReturnList || !this.totalAmountInput || !this.itemsInput || !this.existingItemsInput) {
            console.error('Required elements not found for SalesReturnEdit');
            return;
        }

        this.productReturnList.addEventListener('input', this.handleQuantityChange.bind(this));
        this.form.addEventListener('submit', this.handleSubmit.bind(this));

        // Initialize the component
        await this.loadInitialData();
    }

    async loadInitialData() {
        const salesId = this.salesSelect.value;
        const existingItemsJson = this.existingItemsInput.value;
        const existingItems = existingItemsJson ? JSON.parse(existingItemsJson) : [];

        // Pass the current return ID so the API excludes it from "already returned"
        // counts, giving the correct available qty for editing.
        const returnId = this.form.dataset.returnId || '';

        if (salesId) {
            try {
                const url = returnId
                    ? `/admin/sales-returns/sale/${salesId}?exclude_return_id=${returnId}`
                    : `/admin/sales-returns/sale/${salesId}`;
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                this.allSalesItems = await response.json();

                // Build a map of sales_item_id -> existing return item qty
                // This ensures we correctly match return items to their source sales line items
                const existingBySalesItemId = {};
                existingItems.forEach(exItem => {
                    const siId = exItem.sales_item_id || exItem.product_id + '_' + exItem.price;
                    if (!existingBySalesItemId[siId]) {
                        existingBySalesItemId[siId] = {
                            sales_item_id: exItem.sales_item_id,
                            product_id: exItem.product_id,
                            returned_quantity: 0,
                            price: exItem.price,
                        };
                    }
                    existingBySalesItemId[siId].returned_quantity += exItem.quantity;
                });

                // Now map each sales item to its existing return qty
                this.allSalesItems.forEach(salesItem => {
                    const siId = salesItem.id;
                    
                    // Try to find by exact sales_item_id first
                    let matchedItem = null;
                    for (const key in existingBySalesItemId) {
                        if (existingBySalesItemId[key].sales_item_id == siId) {
                            matchedItem = existingBySalesItemId[key];
                            break;
                        }
                    }

                    if (matchedItem) {
                        const maxReturn = salesItem.available_quantity !== undefined 
                            ? salesItem.available_quantity 
                            : salesItem.quantity;
                        
                        // Use the matched item's qty, capped at available
                        const allocatedQty = Math.min(matchedItem.returned_quantity, maxReturn + matchedItem.returned_quantity);
                        
                        this.returnedItems[salesItem.id] = {
                            sales_item_id: salesItem.id,
                            product_id: salesItem.product_id,
                            returned_quantity: allocatedQty,
                            price: matchedItem.price,
                        };
                    }
                });

                this.populateItemsTable();
            } catch (error) {
                console.error('Error fetching sales items:', error);
                this.productReturnList.innerHTML = '<p class="text-danger">Error loading items. Please try again.</p>';
            }
        }
    }

    populateItemsTable() {
        if (!this.allSalesItems || this.allSalesItems.length === 0) {
            this.productReturnList.innerHTML = '<div class="text-muted p-4 text-center">No items found in this sale.</div>';
            return;
        }

        let tableHTML = `
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Product</th>
                            <th>Sold Qty</th>
                            <th>Return Qty</th>
                            <th>Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        this.allSalesItems.forEach(item => {
            const basePrice = parseFloat(item.price || 0);
            const netPrice = item.quantity > 0 ? parseFloat(item.total) / item.quantity : basePrice;
            const returnedItem = this.returnedItems[item.id]; // Use Sales item ID as key
            const returnedQuantity = returnedItem ? returnedItem.returned_quantity : 0;
            const itemTotal = netPrice * returnedQuantity;

            const availableQty = item.available_quantity !== undefined ? item.available_quantity : item.quantity;
            const maxAllowed = availableQty + returnedQuantity;

            tableHTML += `
                <tr>
                    <td>${item.product.name}</td>
                    <td>${item.quantity}</td>
                    <td>
                        <input type="number"
                            class="form-control quantity-input"
                            data-item-id="${item.id}"
                            data-sales-item-id="${item.id}"
                            data-product-id="${item.product.id}"
                            data-price="${netPrice}"
                            min="0"
                            max="${maxAllowed}"
                            value="${returnedQuantity}"
                            style="width: 100px;">
                        <small class="text-muted d-block mt-1">Available: ${maxAllowed}</small>
                    </td>
                    <td>
                        <div>${formatCurrency(netPrice)}</div>
                        ${basePrice !== netPrice ? `<small class="text-muted text-decoration-line-through">${formatCurrency(basePrice)}</small>` : ''}
                    </td>
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
        const salesItemId = input.dataset.salesItemId;
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
                sales_item_id: salesItemId,
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