import {
    loadProductsFromCache,
    getProducts,
} from "./partials/pos/cart/state.js";
import { initCartActions, addToProductList } from "./partials/pos/cart/actions.js";
import { calculateTotals } from "./partials/pos/cart/totals.js";
import { renderList } from "./partials/pos/cart/dom.js";
import { initPaymentForm } from "./partials/pos/payment/form.js";
import { initPaymentApi } from "./partials/pos/payment/api.js";
import { populatePaymentModal } from "./partials/pos/payment/modal.js";
import { initQuickCreateCustomer } from "./partials/pos/quickCreate/customer.js";
import { initQuickCreateProduct } from "./partials/pos/quickCreate/product.js";
import { onPageLoad } from "./utils/onPageLoad.js";

onPageLoad(function () {
    if (window.location.pathname !== "/admin/pos") return;

    if (!window.posBeforeCacheHandlerAttached) {
        window.posBeforeCacheHandlerAttached = true;
        document.addEventListener("turbo:before-cache", function () {
            const processPaymentBtn = document.getElementById("processPaymentBtn");
            const barcodeInput = document.getElementById("barcodeScannerInput");
            const searchInput = document.getElementById("searchProduct");
            const hasExpiryCheckbox = document.getElementById("has_expiry");
            
            if (processPaymentBtn) delete processPaymentBtn.dataset.handlerAttached;
            if (barcodeInput) delete barcodeInput.dataset.handlerAttached;
            if (searchInput) delete searchInput.dataset.handlerAttached;
            if (hasExpiryCheckbox) delete hasExpiryCheckbox.dataset.handlerAttached;
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    const successParam = urlParams.get("success");

    flatpickr("#transaction_date", {
        enableTime: true,
        dateFormat: "d F Y H:i",
        altInput: true,
        altFormat: "d F Y H:i",
        time_24hr: true,
        defaultDate: new Date(),
        allowInput: true,
        minuteIncrement: 1,
    });

    if (successParam === "true") {
        localStorage.removeItem("cachedProducts");
    } else {
        loadProductsFromCache();
    }

    renderList();
    initCartActions();
    calculateTotals();
    initPaymentForm();
    initPaymentApi();
    initQuickCreateCustomer();
    initQuickCreateProduct();

    const processPaymentBtn = document.getElementById("processPaymentBtn");
    if (processPaymentBtn && !processPaymentBtn.dataset.handlerAttached) {
        processPaymentBtn.dataset.handlerAttached = 'true';
        processPaymentBtn.addEventListener("click", async function () {
            if (getProducts().length === 0) {
                InventMagApp.showToast(
                    "Warning",
                    "Please add at least one product to the cart.",
                    "warning",
                );
                return;
            }
            await populatePaymentModal();
        });
    }

    const barcodeInput = document.getElementById("barcodeScannerInput");

    if (barcodeInput && !barcodeInput.dataset.handlerAttached) {
        barcodeInput.dataset.handlerAttached = 'true';
        barcodeInput.addEventListener("keypress", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                const barcode = this.value.trim();
                if (barcode) {
                    fetch(`/admin/product/search-by-barcode?barcode=${barcode}`)
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error("Product not found");
                            }
                            return response.json();
                        })
                        .then((product) => {
                            addToProductList(
                                product.id,
                                product.name,
                                product.selling_price,
                                product.unit.symbol,
                                product.main_warehouse_stock,
                            );
                            this.value = "";
                        })
                        .catch((error) => {
                            InventMagApp.showToast(
                                "Error",
                                "Product not found for the given barcode.",
                                "error",
                            );
                        });
                }
            }
        });
    }

    const searchInput = document.getElementById("searchProduct");
    const productGrid = document.getElementById("productGrid");

    if (searchInput && productGrid && !searchInput.dataset.handlerAttached) {
        searchInput.dataset.handlerAttached = 'true';
        searchInput.addEventListener("input", function () {
            const searchText = this.value.toLowerCase().trim();
            const productCards = productGrid.querySelectorAll(".col-md-4");

            productCards.forEach((card) => {
                const productNameElement = card.querySelector(".card-title");
                if (productNameElement) {
                    const productName = productNameElement.textContent
                        .toLowerCase()
                        .trim();
                    card.style.display = productName.includes(searchText)
                        ? ""
                        : "none";
                }
            });
        });
    }

    const hasExpiryCheckbox = document.getElementById("has_expiry");
    const expiryDateField = document.querySelector(".expiry-date-field");

    if (hasExpiryCheckbox && !hasExpiryCheckbox.dataset.handlerAttached) {
        hasExpiryCheckbox.dataset.handlerAttached = 'true';
        hasExpiryCheckbox.addEventListener("change", function () {
            if (expiryDateField) {
                expiryDateField.style.display = this.checked ? "block" : "none";
            }
        });
    }

    if (!window.posCtrlSHandlerAttached) {
        window.posCtrlSHandlerAttached = true;
        document.addEventListener("ctrl-s-pressed", function () {
            if (getProducts().length === 0) {
                InventMagApp.showToast(
                    "Warning",
                    "Please add at least one product to the cart.",
                    "warning",
                );
                return;
            }
            populatePaymentModal();
            const paymentModal = new bootstrap.Modal(
                document.getElementById("paymentModal", {}),
            );
            paymentModal.show();
        });
    }
});
