export function showAddToCartFeedback() {
    const feedback = document.createElement("div");
    feedback.classList.add(
        "position-fixed",
        "top-50",
        "start-50",
        "translate-middle",
        "bg-success",
        "text-white",
        "rounded-circle",
        "p-3",
        "d-flex",
        "justify-content-center",
        "align-items-center"
    );
    feedback.style.zIndex = "1050";
    feedback.style.width = "60px";
    feedback.style.height = "60px";
    feedback.style.opacity = "0";
    feedback.style.transition = "opacity 0.3s ease";
    feedback.innerHTML =
        '<i class="ti ti-check" style="font-size: 24px;"></i>';

    document.body.appendChild(feedback);

    // Animate
    setTimeout(() => {
        feedback.style.opacity = "0.9";
        setTimeout(() => {
            feedback.style.opacity = "0";
            setTimeout(() => {
                document.body.removeChild(feedback);
            }, 300);
        }, 500);
    }, 10);
}

export function getStockBadgeClass(stock) {
    if (stock > 10) {
        return 'bg-success';
    } else if (stock > 0) {
        return 'bg-warning';
    } else {
        return 'bg-danger';
    }
}

export function updateProductCardStockDisplay(productId, newStock) {
    const productCard = document.querySelector(`.product-card[data-product-id="${productId}"]`);
    if (productCard) {
        const stockDisplayElement = productCard.querySelector(".product-stock-display");
        if (stockDisplayElement) {
            stockDisplayElement.textContent = newStock;
            stockDisplayElement.className = `product-stock-display badge text-light ${getStockBadgeClass(newStock)}`;
        }
    }
}
