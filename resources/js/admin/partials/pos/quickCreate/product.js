import { formatCurrency } from '../utils/currency.js';
import { getStockBadgeClass } from '../utils/ui.js';

function setupQuickCreateProductButton() {
    const searchProductContainer =
        document.getElementById("searchProduct")?.parentElement;
    if (!searchProductContainer) return;

    const addButton = document.createElement("button");
    addButton.type = "button";
    addButton.className = "btn btn-primary";
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
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        const url = form.getAttribute("action");
        if (!url) {
            console.error("Form action URL is missing");
            showToast("Error", "Form configuration error", "error");
            return;
        }

        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;
        if (!csrfToken) {
            console.error("CSRF token not found");
            showToast("Error", "Security token missing", "error");
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
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
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

                    showToast(
                        "Success",
                        "Product created successfully",
                        "success"
                    );
                } else {
                    showToast(
                        "Error",
                        data.message || "Failed to create product",
                        "error"
                    );
                    console.error("Error response:", data);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                showToast(
                    "Error",
                    "An error occurred while creating the product",
                    "error"
                );
            });
    });
}

function addProductToGrid(product) {
    const productGrid = document.getElementById("productGrid");
    if (!productGrid) return;

    const productCol = document.createElement("div");
    productCol.className = "col-md-4 mb-2";

    const productCard = document.createElement("div");
    productCard.className = "card product-card h-100 border hover-shadow";

    const imageSrc = product.image_url || "/img/default_placeholder.png";

    const imageContainer = document.createElement("div");
    imageContainer.className =
        "card-img-top position-relative product-image-container";

    const img = document.createElement("img");
    img.src = imageSrc;
    img.alt = product.name;
    img.className = "img-fluid product-image";
    img.setAttribute("data-product-id", product.id);
    img.setAttribute("data-product-name", product.name);
    img.setAttribute("data-product-price", product.selling_price);
    img.setAttribute("data-product-unit", product.unit_name || "pcs");
    img.setAttribute("data-product-stock", product.stock_quantity);

    imageContainer.appendChild(img);

    const cardBody = document.createElement("div");
    cardBody.className = "card-body p-2 text-center";

    const title = document.createElement("h5");
    title.className = "card-title fs-4 mb-1";
    title.textContent = product.name;
    title.style.maxHeight = "2.8em";
    title.style.overflow = "hidden";

    const price = document.createElement("p");
    price.className = "card-text fs-4 mb-1";
    price.textContent = formatCurrency(product.selling_price);

    const stockDisplay = document.createElement("p");
    stockDisplay.className = "card-text fs-5 text-muted";
    stockDisplay.innerHTML = `In Stock: <span class="product-stock-display badge text-light ${getStockBadgeClass(product.stock_quantity)}">${product.stock_quantity}</span>`;

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
