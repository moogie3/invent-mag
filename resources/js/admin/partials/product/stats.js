function updateProductStats(stats) {
    if (stats.totalproduct !== undefined) {
        const totalElement = document.getElementById("totalProductCount");
        if (totalElement) totalElement.textContent = stats.totalproduct;
    }

    if (stats.totalcategory !== undefined) {
        const totalCategoryElement = document.getElementById("totalCategoryCount");
        if (totalCategoryElement) totalCategoryElement.textContent = stats.totalcategory;
    }

    if (stats.lowStockCount !== undefined) {
        const lowStockElement = document.getElementById("lowStockItemsCount");
        if (lowStockElement) {
            lowStockElement.textContent = stats.lowStockCount;
            const lowStockLabel = lowStockElement.closest('.card-body').querySelector('.form-label');
            const lowStockIcon = lowStockElement.closest('.d-flex').querySelector('i');
            if (lowStockLabel && lowStockIcon) {
                if (stats.lowStockCount > 0) {
                    lowStockLabel.classList.remove('text-success');
                    lowStockLabel.classList.add('text-danger');
                    lowStockIcon.classList.remove('text-success');
                    lowStockIcon.classList.add('text-danger');
                    lowStockElement.classList.remove('text-success');
                    lowStockElement.classList.add('text-danger');
                } else {
                    lowStockLabel.classList.remove('text-danger');
                    lowStockLabel.classList.add('text-success');
                    lowStockIcon.classList.remove('text-danger');
                    lowStockIcon.classList.add('text-success');
                    lowStockElement.classList.remove('text-danger');
                    lowStockElement.classList.add('text-success');
                }
            }
        }
    }

    if (stats.expiringSoonCount !== undefined) {
        const expiringSoonElement = document.getElementById("expiringSoonItemsCount");
        if (expiringSoonElement) {
            expiringSoonElement.textContent = stats.expiringSoonCount;
            const expiringSoonLabel = expiringSoonElement.closest('.card-body').querySelector('.form-label');
            const expiringSoonIcon = expiringSoonElement.closest('.d-flex').querySelector('i');
            if (expiringSoonLabel && expiringSoonIcon) {
                if (stats.expiringSoonCount > 0) {
                    expiringSoonLabel.classList.remove('text-success');
                    expiringSoonLabel.classList.add('text-warning');
                    expiringSoonIcon.classList.remove('text-success');
                    expiringSoonIcon.classList.add('text-warning');
                    expiringSoonElement.classList.remove('text-success');
                    expiringSoonElement.classList.add('text-warning');
                } else {
                    expiringSoonLabel.classList.remove('text-warning');
                    expiringSoonLabel.classList.add('text-success');
                    expiringSoonIcon.classList.remove('text-warning');
                    expiringSoonIcon.classList.add('text-success');
                    expiringSoonElement.classList.remove('text-warning');
                    expiringSoonElement.classList.add('text-success');
                }
            }
        }
    }
}

export function fetchProductMetrics() {
    fetch('/admin/product/metrics')
        .then(response => response.json())
        .then(data => {
            updateProductStats(data);
        })
        .catch(error => { /* // console.error('Error fetching product metrics:', error) */ });
}
