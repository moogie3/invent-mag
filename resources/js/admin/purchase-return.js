document.addEventListener("DOMContentLoaded", function() {
    const purchaseSelect = document.getElementById('purchase-select');
    const productReturnList = document.getElementById('product-return-list');
    const totalAmountInput = document.getElementById('total-amount');
    const itemsJsonInput = document.getElementById('items-json');

    function formatCurrency(amount) {
        return new Intl.NumberFormat(window.currencySettings.locale, {
            style: 'currency',
            currency: window.currencySettings.currency_code,
            minimumFractionDigits: window.currencySettings.decimal_places,
        }).format(amount);
    }

    if (purchaseSelect) {
        purchaseSelect.addEventListener('change', function() {
            const purchaseId = this.value;
            if (purchaseId) {
                fetch(`/admin/purchase-returns/purchase/${purchaseId}`)
                    .then(response => response.json())
                    .then(data => {
                        productReturnList.innerHTML = '';
                        const table = document.createElement('table');
                        table.classList.add('table', 'table-vcenter');
                        table.innerHTML = `
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Purchased Qty</th>
                                    <th>Return Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        `;
                        const tbody = table.querySelector('tbody');
                        data.forEach(item => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${item.product.name}</td>
                                <td>${item.quantity}</td>
                                <td>
                                    <input type="number" name="items[${item.id}][quantity]" class="form-control" value="0" min="0" max="${item.quantity}" data-price="${item.price}">
                                </td>
                                <td>${formatCurrency(item.price)}</td>
                                <td class="item-total">${formatCurrency(0)}</td>
                            `;
                            tbody.appendChild(row);
                        });
                        productReturnList.appendChild(table);
                        updateTotalAmount();
                    });
            } else {
                productReturnList.innerHTML = '<p>Select a purchase order to see its items.</p>';
            }
        });
    }

    if (productReturnList) {
        productReturnList.addEventListener('input', function(event) {
            if (event.target.tagName === 'INPUT') {
                updateTotalAmount();
            }
        });
    }

    function updateTotalAmount() {
        let total = 0;
        const items = [];
        productReturnList.querySelectorAll('tbody tr').forEach(row => {
            const quantityInput = row.querySelector('input[type="number"]');
            const price = parseFloat(quantityInput.dataset.price);
            const quantity = parseInt(quantityInput.value);
            const itemTotal = price * quantity;
            row.querySelector('.item-total').textContent = formatCurrency(itemTotal);
            total += itemTotal;

            if (quantity > 0) {
                const match = quantityInput.name.match(/items\[(\d+)\]\[quantity\]/);
                if (match && match[1]) {
                    items.push({
                        product_id: match[1],
                        quantity: quantity,
                        price: price,
                    });
                }
            }
        });
        totalAmountInput.value = formatCurrency(total);
        itemsJsonInput.value = JSON.stringify(items);
    }
});
