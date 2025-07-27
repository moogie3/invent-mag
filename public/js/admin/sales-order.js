document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-sales-form');
    if (form) {
        const productsJsonInput = document.getElementById('products-json');

        form.addEventListener('submit', function(event) {
            const products = [];
            const productRows = document.querySelectorAll('.product-row');

            productRows.forEach(row => {
                const productId = row.dataset.productId;
                const quantity = row.querySelector('.quantity-input').value;
                const price = row.querySelector('.price-input').value;
                const discount = row.querySelector('.discount-input').value;
                const discountType = row.querySelector('.discount-type-input').value;

                products.push({
                    product_id: productId,
                    quantity: quantity,
                    customer_price: price,
                    discount: discount,
                    discount_type: discountType
                });
            });

            productsJsonInput.value = JSON.stringify(products);
        });
    }
});