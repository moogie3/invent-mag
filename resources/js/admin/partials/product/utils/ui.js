export function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

export function setBadge(el, text, badgeClass) {
    if (el) {
        el.className = `badge fs-6 ${badgeClass}`;
        el.textContent = text;
    }
}

export function getExpiryBadge(expiryDateStr) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const expiryDate = new Date(expiryDateStr);
    expiryDate.setHours(0, 0, 0, 0);
    const diffDays = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return ' <span class="badge bg-danger-lt">Expired</span>';
    if (diffDays <= 7)
        return ` <span class="badge bg-warning-lt">Expiring Soon - ${diffDays}d</span>`;
    return "";
}

export function getStockClassAndText(stockQty, threshold = 10) {
    if (stockQty <= threshold) {
        return ['bg-red text-white', 'Low Stock'];
    }
    return ['bg-green text-white', 'In Stock'];
}

export function resetButton(button, originalText) {
    if (button) {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}
