import { setText, setBadge, getExpiryBadge } from '../utils/ui.js';
import { formatCurrency } from '../../../../utils/currencyFormatter.js';

export function initProductModal() {
    const printBtn = document.getElementById("productModalPrint");
    if (printBtn) printBtn.addEventListener("click", handleProductModalPrint);

    window.loadProductDetails = function (id) {
        const content = document.getElementById("viewProductModalContent");
        const editBtn = document.getElementById("productModalEdit");

        if (!content || !editBtn) {
            return;
        }

        editBtn.href = `/admin/product/edit/${id}`;
        content.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-3 text-muted">Loading...</p>
            </div>
        `;

        fetch(`/admin/product/modal-view/${id}`)
            .then((response) => {
                if (!response.ok) throw new Error("Network error");
                return response.json();
            })
            .then((data) => {
                renderProductDetails(data);
                // Also fetch and render the adjustment log
                fetch(`/admin/product/${id}/adjustment-log`)
                    .then(response => response.json())
                    .then(logData => {
                        renderAdjustmentLog(logData);
                    })
                    .catch(error => {
                        const logContent = document.getElementById('productAdjustmentLogContent');
                        if (logContent) {
                            logContent.innerHTML = `<div class="alert alert-danger">Error loading adjustment log: ${error.message}</div>`;
                        }
                    });
            })
            .catch((error) => {
                content.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            });
    };
}

function renderProductDetails(data) {
    const content = document.getElementById("viewProductModalContent");
    const template = document.getElementById(
        "productModalViewTemplate"
    ).innerHTML;
    content.innerHTML = template;

    // Activate the first tab (Basic Info) by default
    const basicInfoTab = document.getElementById('basic-info-tab');
    const basicInfoPane = document.getElementById('basic-info-pane');
    if (basicInfoTab && basicInfoPane) {
        basicInfoTab.classList.add('active');
        basicInfoPane.classList.add('show', 'active');
    }

    setText("productName", data.name);
    setText("productCode", `Code: ${data.code}`);
    setText("productCategory", data.category?.name || "N/A");
    setText("productUnit", data.unit?.symbol || "N/A");
    setText("productQuantity", data.stock_quantity);
    setText("productSupplier", data.supplier?.name || "N/A");
    setText("productWarehouse", data.warehouse?.name || "N/A");

    const threshold = data.low_stock_threshold || 10;
    const stockElement = document.getElementById("stockStatus");
    const isLowStock = data.stock_quantity <= threshold;
    setBadge(
        stockElement,
        isLowStock ? "Low Stock" : "In Stock",
        isLowStock ? "bg-danger-lt" : "bg-success-lt"
    );

    const productImageContainer = document.getElementById(
        "productImageContainer"
    );
    const defaultPlaceholderUrl = "/img/default_placeholder.png";

    if (productImageContainer) {
        if (
            data.image &&
            typeof data.image === "string" &&
            data.image.trim() !== "" &&
            !data.image.endsWith(defaultPlaceholderUrl) &&
            data.image.toLowerCase() !== "null" &&
            data.image.toLowerCase() !== "undefined"
        ) {
            productImageContainer.innerHTML = `<img id="productImage" src="${data.image}" alt="Product Image" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: contain;">`;
        } else {
            productImageContainer.innerHTML = `
                <div class="d-flex align-items-center justify-content-center"
                     style="width: 220px; height: 220px; margin: 0 auto; border: 1px solid #ccc; border-radius: 5px;">
                    <i class="ti ti-photo fs-1 text-muted" style="font-size: 100px !important;"></i>
                </div>
            `;
        }
    }

    const thresholdElement = document.getElementById("productThreshold");
    const thresholdNote = document.getElementById("thresholdDefaultNote");
    setText("productThreshold", data.low_stock_threshold || "10");
    if (thresholdNote) {
        thresholdNote.style.display = data.low_stock_threshold
            ? "none"
            : "inline";
    }

    setText("productPrice", data.formatted_price || formatCurrency(data.price));
    setText(
        "productSellingPrice",
        data.formatted_selling_price || formatCurrency(data.selling_price)
    );

    const margin = (
        ((data.selling_price - data.price) / data.price) *
        100
    ).toFixed(2);
    setText("productMargin", margin + "%");

    const descContainer = document.getElementById(
        "productDescriptionContainer"
    );
    if (data.description) {
        setText("productDescription", data.description);
    } else if (descContainer) {
        descContainer.style.display = "none";
    }

    // Handle Expiry Status Tab
    const expiryStatusTab = document.getElementById('expiry-status-tab');
    const expiryStatusPane = document.getElementById('expiry-status-pane');
    const productExpiryStatusContent = document.getElementById('productExpiryStatusContent');

    if (data.has_expiry && data.po_items && data.po_items.length > 0) {
        if (expiryStatusTab) expiryStatusTab.style.display = 'block';
        let expiryTableHtml = `
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>PO ID</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        data.po_items.forEach(item => {
            const expiryDate = item.expiry_date ? new Date(item.expiry_date).toLocaleDateString("en-GB", { day: "2-digit", month: "long", year: "numeric" }) : 'N/A';
            const today = new Date();
            const itemExpiryDate = item.expiry_date ? new Date(item.expiry_date) : null;
            let status = 'N/A';
            let statusClass = '';

            if (itemExpiryDate) {
                if (itemExpiryDate < today) {
                    status = 'Expired';
                    statusClass = 'badge bg-danger-lt';
                } else {
                    const diffTime = Math.abs(itemExpiryDate - today);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays <= 7) {
                        status = `Expiring in ${diffDays} days`;
                        statusClass = 'badge bg-danger-lt'; // Critical warning
                    } else if (diffDays <= 30) {
                        status = `Expiring in ${diffDays} days`;
                        statusClass = 'badge bg-warning-lt'; // Warning
                    } else if (diffDays <= 90) {
                        status = `Expiring in ${diffDays} days`;
                        statusClass = 'badge bg-info-lt'; // Mild warning (using info for less critical)
                    } else {
                        status = 'Long Shelf Life';
                        statusClass = 'badge bg-success-lt';
                    }
                }
            }

            expiryTableHtml += `
                <tr>
                    <td><a href="/admin/po/edit/${item.po_id}">${item.po_id}</a></td>
                    <td>${item.quantity}</td>
                    <td>${expiryDate}</td>
                    <td><span class="${statusClass}">${status}</span></td>
                </tr>
            `;
        });
        expiryTableHtml += `
                    </tbody>
                </table>
            </div>
        `;
        if (productExpiryStatusContent) {
            productExpiryStatusContent.innerHTML = expiryTableHtml;
        }
    } else {
        if (expiryStatusTab) expiryStatusTab.style.display = 'none';
        if (productExpiryStatusContent) {
            productExpiryStatusContent.innerHTML = `
                <div class="text-center text-muted py-4">
                    ${data.has_expiry ? 'No expiry data available for this product.' : 'This product does not have an expiry date.'}
                </div>
            `;
        }
    }
}

function handleProductModalPrint() {
    const content = document.getElementById(
        "viewProductModalContent"
    ).innerHTML;
    const original = document.body.innerHTML;

    document.body.innerHTML = `
        <div class="container print-container">
            <div class="card"><div class="card-body">${content}</div></div>
        </div>
    `;

    window.print();
    document.body.innerHTML = original;
    setTimeout(() => window.location.reload(), 100);
}

export function loadExpiringSoonProductsModal(expiringSoonProducts) {
    const tableBody = document.getElementById('expiringSoonProductsTableBody');

    if (!tableBody) {
        return;
    }

    tableBody.innerHTML = ''; // Clear previous content

    if (expiringSoonProducts && expiringSoonProducts.length > 0) {
        expiringSoonProducts.forEach(item => {
            console.log('Processing item:', item);
            const expiryDate = item.expiry_date ? new Date(item.expiry_date).toLocaleDateString("en-GB", { day: "2-digit", month: "long", year: "numeric" }) : 'N/A';
            const today = new Date();
            const itemExpiryDate = item.expiry_date ? new Date(item.expiry_date) : null;
            let status = 'N/A';
            let statusClass = '';

            if (itemExpiryDate) {
                if (itemExpiryDate < today) {
                    status = 'Expired';
                    statusClass = 'badge bg-danger-lt';
                } else {
                    const diffTime = Math.abs(itemExpiryDate - today);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays <= 7) {
                        status = `Expiring in ${diffDays} days`;
                        statusClass = 'badge bg-danger-lt'; // Critical warning
                    } else if (diffDays <= 30) {
                        status = `Expiring in ${diffDays} days`;
                        statusClass = 'badge bg-warning-lt'; // Warning
                    } else if (diffDays <= 90) {
                        status = `Expiring in ${diffDays} days`;
                        statusClass = 'badge bg-info-lt'; // Mild warning (using info for less critical)
                    } else {
                        status = 'Long Shelf Life';
                        statusClass = 'badge bg-success-lt';
                    }
                }
            }

            const row = `
                <tr>
                    <td>${item.product ? item.product.name : 'N/A'}</td>
                    <td class="text-center"><a href="/admin/po/edit/${item.po_id}">${item.po_id}</a></td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-center">${expiryDate}</td>
                    <td class="text-center"><span class="${statusClass}">${status}</span></td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    } else {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-4">No products expiring soon.</td>
            </tr>
        `;
    }
}

function renderAdjustmentLog(logData) {
    const logContent = document.getElementById('productAdjustmentLogContent');
    if (!logContent) return;

    if (!logData || logData.length === 0) {
        logContent.innerHTML = `<div class="text-center text-muted py-4">No adjustment history for this product.</div>`;
        return;
    }

    let logTableHtml = `
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Qty Before</th>
                        <th>Qty After</th>
                        <th>Change</th>
                        <th>Reason</th>
                        <th>Adjusted By</th>
                    </tr>
                </thead>
                <tbody>
    `;

    logData.forEach(log => {
        const adjustmentDate = new Date(log.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        const change = log.quantity_after - log.quantity_before;
        const changeClass = change > 0 ? 'text-success' : (change < 0 ? 'text-danger' : 'text-muted');
        const changeSign = change > 0 ? '+' : '';

        logTableHtml += `
            <tr>
                <td>${adjustmentDate}</td>
                <td><span class="badge bg-secondary-lt">${log.adjustment_type}</span></td>
                <td>${log.quantity_before}</td>
                <td>${log.quantity_after}</td>
                <td class="${changeClass}">${changeSign}${change}</td>
                <td>${log.reason || 'N/A'}</td>
                <td>${log.adjusted_by ? log.adjusted_by.name : 'System'}</td>
            </tr>
        `;
    });

    logTableHtml += `
                </tbody>
            </table>
        </div>
    `;

    logContent.innerHTML = logTableHtml;
}
