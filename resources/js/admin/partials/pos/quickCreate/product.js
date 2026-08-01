import { formatCurrency } from '@/js/utils/currencyFormatter.js';
import { getStockBadgeClass } from '../utils/ui.js';

function setupQuickCreateProductButton() {
    const searchProductContainer =
        document.getElementById("searchProduct")?.parentElement;
    if (!searchProductContainer) return;

    // Prevent duplicate buttons on Turbo re-renders
    if (searchProductContainer.querySelector('.quick-create-product-btn')) return;

    const addButton = document.createElement("button");
    addButton.type = "button";
    addButton.className = "btn btn-primary quick-create-product-btn";
    addButton.innerHTML = '<i class="ti ti-plus"></i>';
    addButton.title = "Create New Product";
    addButton.setAttribute("data-bs-toggle", "modal");
    addButton.setAttribute("data-bs-target", "#quickCreateProductModal");

    searchProductContainer.appendChild(addButton);
}

function setupQuickCreateProductForm() {
    const productForm = document.getElementById("quickCreateProductForm");
    if (!productForm) return;

    productForm.addEventListener("submit", function (e) {
        if (e.defaultPrevented) return;
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        const url = form.getAttribute("action");
        if (!url) {
            // // console.error("Form action URL is missing");
            InventMagApp.showToast("Error", "Form configuration error", "error");
            return;
        }

        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;
        if (!csrfToken) {
            // // console.error("CSRF token not found");
            InventMagApp.showToast("Error", "Security token missing", "error");
            return;
        }

        fetch(url, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken,
            },
        })
            .then((response) => {
                if (response.status === 422) {
                    return response.json().then(err => {
                        const messages = Object.values(err.errors || {}).flat();
                        throw new Error(messages.join(', ') || 'Validation failed');
                    });
                }
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || `HTTP error! Status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    addProductToGrid(data.product);

                    const modal = document.getElementById(
                        "quickCreateProductModal"
                    );
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    }

                    form.reset();

                    InventMagApp.showToast(
                        "Success",
                        "Product created successfully",
                        "success"
                    );
                } else {
                    InventMagApp.showToast(
                        "Error",
                        data.message || "Failed to create product",
                        "error"
                    );
                }
            })
            .catch((error) => {
                InventMagApp.showToast(
                    "Error",
                    error.message || "An error occurred while creating the product",
                    "error"
                );
            });
    });
}

function addProductToGrid(product) {
    const productGrid = document.getElementById("productGrid");
    if (!productGrid) return;

    // Remove empty state if present
    const emptyState = productGrid.querySelector(".empty");
    if (emptyState) emptyState.remove();

    const productCol = document.createElement("div");
    productCol.className = "col-md-4 mb-2";

    const productCard = document.createElement("div");
    productCard.className = "card product-card border-0 shadow-sm rounded-3 hover-shadow";
    productCard.style.cursor = "pointer";
    productCard.setAttribute("data-product-id", product.id);
    productCard.setAttribute("data-product-name", product.name);
    productCard.setAttribute("data-product-price", product.selling_price);
    productCard.setAttribute("data-product-unit", product.unit_symbol || "pcs");
    productCard.setAttribute("data-product-stock", product.main_warehouse_stock ?? 0);

    const imageContainer = document.createElement("div");
    imageContainer.className = "card-img-top position-relative product-image-container d-flex align-items-center justify-content-center";
    imageContainer.style.height = "150px";

    const defaultPlaceholder = "/img/default_placeholder.png";
    const imageSrc = product.image_url && product.image_url !== defaultPlaceholder ? product.image_url : null;

    if (imageSrc) {
        const img = document.createElement("img");
        img.src = imageSrc;
        img.alt = product.name;
        img.className = "img-fluid product-image";
        img.style.width = "100%";
        img.style.height = "100%";
        img.style.objectFit = "cover";
        img.style.borderRadius = "4px 4px 0 0";
        imageContainer.appendChild(img);
    } else {
        const icon = document.createElement("i");
        icon.className = "ti ti-photo fs-1";
        icon.style.width = "100%";
        icon.style.height = "100%";
        icon.style.display = "flex";
        icon.style.alignItems = "center";
        icon.style.justifyContent = "center";
        icon.style.border = "1px solid #ccc";
        icon.style.borderRadius = "5px";
        icon.style.margin = "0 auto";
        imageContainer.appendChild(icon);
    }

    const cardBody = document.createElement("div");
    cardBody.className = "card-body p-2 text-center";

    const title = document.createElement("h5");
    title.className = "card-title fs-4 mb-1";
    title.textContent = product.name;
    title.style.maxHeight = "4em";
    title.style.overflow = "hidden";

    const price = document.createElement("p");
    price.className = "card-text fs-4 mb-1";
    price.textContent = formatCurrency(product.selling_price);

    const stock = product.main_warehouse_stock ?? 0;
    const stockClass = stock > 10 ? "bg-success" : stock > 0 ? "bg-warning" : "bg-danger";

    const stockDisplay = document.createElement("p");
    stockDisplay.className = "card-text fs-5 text-muted";
    stockDisplay.innerHTML = `In Stock: <span class="product-stock-display badge text-light ${stockClass}">${stock}</span>`;

    cardBody.appendChild(title);
    cardBody.appendChild(price);
    cardBody.appendChild(stockDisplay);

    productCard.appendChild(imageContainer);
    productCard.appendChild(cardBody);
    productCol.appendChild(productCard);

    if (productGrid.firstChild) {
        productGrid.insertBefore(productCol, productGrid.firstChild);
    } else {
        productGrid.appendChild(productCol);
    }

    setTimeout(() => {
        productCol.style.transition = "background-color 1s ease";
        productCol.style.backgroundColor = "#e8f4ff";

        setTimeout(() => {
            productCol.style.backgroundColor = "";
        }, 1500);
    }, 100);
}

export function initQuickCreateProduct() {
    setupQuickCreateProductButton();
    setupQuickCreateProductForm();
}