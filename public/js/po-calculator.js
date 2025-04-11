function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(amount);
}

function calculateTotal(price, quantity, discount, discountType) {
    if (discountType === 'percentage') {
        return (price * quantity) - ((price * quantity) * discount / 100);
    }
    return (price * quantity) - discount;
}

function calculateSummary(products) {
    let subtotal = 0;
    let totalDiscount = 0;

    products.forEach(product => {
        const productSubtotal = Number(product.price) * Number(product.quantity);
        const productDiscount = product.discountType === 'percentage'
            ? (productSubtotal * product.discount / 100)
            : product.discount;

        subtotal += productSubtotal;
        totalDiscount += productDiscount;
    });

    const finalTotal = subtotal - totalDiscount;

    return {
        subtotal,
        totalDiscount,
        finalTotal
    };
}
