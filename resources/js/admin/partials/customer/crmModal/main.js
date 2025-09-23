import { crmState } from "./state.js";
import { showLoadingState, showErrorState, showNoProductHistoryMessage, hideNoProductHistoryMessage } from "./ui.js";
import { loadCrmData, handleInteractionForm } from "./api.js";
import { formatCurrency } from "../../../../utils/currencyFormatter.js";

function formatDateToCustomString(dateString) {
    const date = new Date(dateString);
    const options = { year: "numeric", month: "long", day: "numeric" };
    return date.toLocaleDateString(undefined, options);
}

export function initCrmCustomerModal() {
    const crmCustomerModal = document.getElementById("crmCustomerModal");

    if (crmCustomerModal) {
        crmCustomerModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            crmState.customerId = button.getAttribute("data-id");
            if (!crmState.customerId) {
                console.error("Customer ID not found");
                showErrorState("Customer ID not found");
                return;
            }

            crmState.currentPage = 1;
            showLoadingState();
            loadCrmData(crmState.customerId, crmState.currentPage);
        });

        const loadMoreBtn = document.getElementById("loadMoreTransactions");
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener("click", function () {
                if (crmState.currentPage < crmState.lastPage) {
                    crmState.currentPage++;
                    loadCrmData(
                        crmState.customerId,
                        crmState.currentPage,
                        true
                    );
                }
            });
        }

        handleInteractionForm();

        const historicalPurchaseTab = document.getElementById(
            "purchase-history-tab"
        );
        if (historicalPurchaseTab) {
            historicalPurchaseTab.addEventListener(
                "shown.bs.tab",
                function (event) {
                    if (crmState.customerId) {
                        loadProductHistory(crmState.customerId);
                    }
                }
            );
        }
    }
}

function loadProductHistory(id) {
    const productHistoryContent = document.getElementById(
        "productHistoryContent"
    );

    if (productHistoryContent) {
        productHistoryContent.innerHTML = ''; // Clear content, loading state is handled by ui.js
        hideNoProductHistoryMessage();
    } else {
        console.error("Element with ID 'productHistoryContent' not found!");
        return;
    }

    fetch(`/admin/customers/${id}/product-history`)
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (productHistoryContent) {
                if (data.product_history && data.product_history.length > 0) {
                    let contentHtml = `
            <div class="accordion" id="crmProductHistoryAccordion">
        `;

                    data.product_history.forEach((product) => {
                        contentHtml += `
                <div class="accordion-item">
                    <h2 class="accordion-header" id="product-heading-${product.product_name.replace(
                        /\s+/g,
                        "-"
                    )}">
                        <button class="accordion-button collapsed fs-3" type="button" data-bs-toggle="collapse" data-bs-target="#product-collapse-${product.product_name.replace(
                            /\s+/g,
                            "-"
                        )}" aria-expanded="false" aria-controls="product-collapse-${product.product_name.replace(
                            /\s+/g,
                            "-"
                        )}">
                            <div class="d-flex justify-content-between w-100 pe-3">
                                <span>${product.product_name}</span>
                                <span class="text-muted fs-4">Last Price: ${formatCurrency(
                                    product.last_price
                                )}</span>
                            </div>
                        </button>
                    </h2>
                    <div id="product-collapse-${product.product_name.replace(
                        /\s+/g,
                        "-"
                    )}" class="accordion-collapse collapse" aria-labelledby="product-heading-${product.product_name.replace(
                            /\s+/g,
                            "-"
                        )}" data-bs-parent="#crmProductHistoryAccordion">
                        <div class="accordion-body">
                            <div class="list-group list-group-flush">
                                ${product.history
                                    .map(
                                        (item) => `
                                    <div class="list-group-item px-0">
                                        <div class="row align-items-center">
                                            <div class="col-md-5 mb-2 mb-md-0">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <i class="ti ti-file-invoice text-primary fs-2"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fs-4">Invoice #${
                                                            item.invoice
                                                        }</h6>
                                                        <small class="text-muted">${formatDateToCustomString(
                                                            item.order_date
                                                        )}</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3 text-center">
                                                <div class="text-muted small">Quantity</div>
                                                <div class="fw-bold">${
                                                    item.quantity
                                                }</div>
                                            </div>
                                            <div class="col-6 col-md-4 text-end">
                                                <div class="text-muted small">Price</div>
                                                <div class="fw-bold text-success">${formatCurrency(
                                                    item.price_at_purchase
                                                )}</div>
                                            </div>
                                        </div>
                                    </div>
                                `
                                    )
                                    .join("")}
                            </div>
                        </div>
                    </div>
                </div>
            `;
                    });

                    contentHtml += `
            </div>
        `;
                    hideNoProductHistoryMessage(); // Hide empty message if data is loaded
                    productHistoryContent.innerHTML = contentHtml;
                } else {
                    showNoProductHistoryMessage();
                }
            } else {
                console.error(
                    "Element with ID 'crmProductHistoryContent' was null after fetch!"
                );
            }
        })
        .catch((error) => {
            console.error("Fetch error in loadProductHistory:", error);
            if (productHistoryContent) {
                productHistoryContent.innerHTML = `
        <div class="text-center py-5">
            <div class="mb-3">
                <i class="ti ti-alert-circle fs-1 text-danger"></i>
            </div>
            <h5 class="text-danger">Error Loading Data</h5>
            <p class="text-muted mb-0">Failed to load product history: ${error.message}</p>
        </div>
    `;
                showNoProductHistoryMessage(); // Also show empty message on error
            }
        });
}
