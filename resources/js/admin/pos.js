import { loadProductsFromCache, getProducts } from './partials/pos/cart/state.js';
import { initCartActions } from './partials/pos/cart/actions.js';
import { calculateTotals } from './partials/pos/cart/totals.js';
import { renderList } from './partials/pos/cart/dom.js';
import { initPaymentForm } from './partials/pos/payment/form.js';
import { initPaymentApi } from './partials/pos/payment/api.js';
import { populatePaymentModal } from './partials/pos/payment/modal.js';
import { initQuickCreateCustomer } from './partials/pos/quickCreate/customer.js';
import { initQuickCreateProduct } from './partials/pos/quickCreate/product.js';

document.addEventListener("DOMContentLoaded", function () {
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
    processPaymentBtn.addEventListener("click", function () {
        if (getProducts().length === 0) {
            alert("Please add at least one product to the cart.");
            return;
        }
        populatePaymentModal();
    });

    const searchInput = document.getElementById("searchProduct");
    const productCards = document.querySelectorAll("#productGrid .col-md-4");

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const searchText = this.value.toLowerCase().trim();

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

    if (hasExpiryCheckbox) {
        hasExpiryCheckbox.addEventListener("change", function () {
            expiryDateField.style.display = this.checked ? "block" : "none";
        });
    }
});