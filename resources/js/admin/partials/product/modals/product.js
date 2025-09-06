import { setText, setBadge, getExpiryBadge } from '../utils/ui.js';
import { formatCurrency } from '../utils/currency.js';

export function initProductModal() {
    const printBtn = document.getElementById("productModalPrint");
    if (printBtn) printBtn.addEventListener("click", handleProductModalPrint);

    window.loadProductDetails = function (id) {
        const content = document.getElementById("viewProductModalContent");
        const editBtn = document.getElementById("productModalEdit");

        if (!content || !editBtn) {
            console.error("Modal elements not found");
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
            })
            .catch((error) => {
                console.error("Error loading product details:", error);
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

    if (data.has_expiry && data.expiry_date) {
        const date = new Date(data.expiry_date).toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "long",
            year: "numeric",
        });
        document.getElementById("productExpiry").innerHTML =
            date + getExpiryBadge(data.expiry_date);
    } else {
        setText("productExpiry", "N/A");
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
