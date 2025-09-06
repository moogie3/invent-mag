import { srmState, currencySettings, setCurrencySettings } from './state.js';
import { showLoadingState, showErrorState } from './ui.js';
import { safeUpdateElement, safeUpdateElementHTML, safeToggleElement } from '../utils/dom.js';
import { formatCurrency } from '../utils/currency.js';
import { formatDateToCustomString } from '../utils/date.js';
import { getStatusBadgeHtml } from '../utils/status.js';

export function loadSrmData(id, page, append = false) {
    if (!id) {
        console.error("Supplier ID is required");
        showErrorState("Supplier ID is required");
        return;
    }

    fetch(`/admin/suppliers/${id}/srm-details?page=${page}`)
        .then((response) => {
            if (!response.ok) {
                throw new Error(
                    `HTTP error! status: ${response.status}`
                );
            }
            return response.json();
        })
        .then((data) => {
            if (!data || !data.supplier) {
                throw new Error("Supplier data not found in response");
            }

            if (data.currencySettings) {
                setCurrencySettings(data.currencySettings);
            }

            if (!append) {
                safeUpdateElementHTML("srmInteractionTimeline", "");
                safeUpdateElementHTML("srmTransactionHistory", "");
                safeToggleElement("srmNoInteractionsMessage", "none");
                safeToggleElement("srmNoTransactionsMessage", "none");
            }

            populateSrmData(data);

            srmState.lastPage = data.purchases.last_page || 1;
            const currentPageNum = data.purchases
                ? data.purchases.current_page
                : 1;
            const lastPageNum = data.purchases
                ? data.purchases.last_page
                : 1;

            if (currentPageNum < lastPageNum) {
                safeToggleElement(
                    "srmLoadMoreTransactions",
                    "block"
                );
            } else {
                safeToggleElement(
                    "srmLoadMoreTransactions",
                    "none"
                );
            }
        })
        .catch((error) => {
            console.error("Error loading SRM data:", error);
            showToast(
                "Error",
                `Failed to load SRM data: ${error.message}`,
                "error"
            );
            showErrorState("Failed to load");
        });
}

function populateSrmData(data) {
    safeUpdateElement(
        "srmSupplierNameInHeader",
        `[${data.supplier.name || "N/A"}]`
    );
    safeUpdateElement(
        "srmSupplierName",
        data.supplier.name || "N/A"
    );
    safeUpdateElement(
        "srmSupplierEmail",
        data.supplier.email || "N/A"
    );
    safeUpdateElement(
        "srmSupplierPhone",
        data.supplier.phone_number || "N/A"
    );
    safeUpdateElement(
        "srmSupplierAddress",
        data.supplier.address || "N/A"
    );
    safeUpdateElement(
        "srmSupplierPaymentTerms",
        data.supplier.payment_terms || "N/A"
    );

    const srmSupplierImageContainer = document.getElementById(
        "srmSupplierImageContainer"
    );
    const defaultPlaceholderUrl =
        window.defaultPlaceholderUrl ||
        "/img/default_placeholder.png";

    if (srmSupplierImageContainer) {
        if (
            data.supplier.image &&
            data.supplier.image !== defaultPlaceholderUrl
        ) {
            srmSupplierImageContainer.innerHTML = `
                <img src="${data.supplier.image}" alt="${
                    data.supplier.name || "Supplier Image"
                }"
                     class="img-thumbnail"
                     style="max-width: 120px; max-height: 120px; object-fit: cover;">
            `;
        } else {
            srmSupplierImageContainer.innerHTML = `
                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                     style="width: 120px; height: 120px; margin: 0 auto;">
                    <i class="ti ti-photo fs-1 text-muted"></i>
                </div>
            `;
        }
    }

    safeUpdateElement(
        "srmLifetimeValue",
        formatCurrency(data.lifetimeValue || 0)
    );
    safeUpdateElement(
        "srmTotalPurchasesCount",
        data.totalPurchasesCount || 0
    );
    safeUpdateElement(
        "srmAverageOrderValue",
        formatCurrency(data.averageOrderValue || 0)
    );
    safeUpdateElement(
        "srmLastInteractionDate",
        data.lastInteractionDate
            ? new Date(
                  data.lastInteractionDate
              ).toLocaleDateString("id-ID")
            : "N/A"
    );
    safeUpdateElement(
        "srmMostPurchasedProduct",
        data.mostPurchasedProduct || "N/A"
    );
    safeUpdateElement(
        "srmTotalProductsPurchased",
        data.totalProductsPurchased || 0
    );
    safeUpdateElement(
        "srmMemberSince",
        data.supplier.created_at
            ? new Date(
                  data.supplier.created_at
              ).toLocaleDateString("id-ID")
            : "N/A"
    );
    safeUpdateElement(
        "srmLastPurchase",
        data.lastPurchaseDate
            ? new Date(
                  data.lastPurchaseDate
              ).toLocaleDateString("id-ID")
            : "N/A"
    );

    populateInteractions(data.supplier.interactions);
    populateTransactions(data.purchases.data);
}

function populateInteractions(interactions) {
    const interactionTimeline = document.getElementById(
        "srmInteractionTimeline"
    );
    if (interactionTimeline) {
        interactionTimeline.innerHTML = "";

        if (
            interactions &&
            interactions.length > 0
        ) {
            safeToggleElement(
                "srmNoInteractionsMessage",
                "none"
            );
            interactions.forEach(
                (interaction) => {
                    const interactionElement =
                        document.createElement("div");
                    interactionElement.classList.add(
                        "list-group-item",
                        "list-group-item-action"
                    );
                    interactionElement.innerHTML = `
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">${interaction.type.charAt(0).toUpperCase() + interaction.type.slice(1)} on ${new Date(interaction.interaction_date).toLocaleDateString("id-ID")}</h5>
                    <small class="text-muted">by ${interaction.user ? interaction.user.name : "Unknown"}</small>
                </div>
                <p class="mb-1">${interaction.notes}</p>
            `;
                    interactionTimeline.appendChild(
                        interactionElement
                    );
                }
            );
        } else {
            safeToggleElement(
                "srmNoInteractionsMessage",
                "block"
            );
        }
    }
}

function populateTransactions(purchases) {
    const transactionHistory = document.getElementById(
        "srmTransactionHistory"
    );
    if (transactionHistory) {
        transactionHistory.innerHTML = "";

        if (
            purchases &&
            purchases.length > 0
        ) {
            safeToggleElement(
                "srmNoTransactionsMessage",
                "none"
            );

            purchases.forEach((purchase) => {
                const purchaseElement =
                    document.createElement("div");
                purchaseElement.classList.add("accordion-item");
                purchaseElement.innerHTML = `
                <h2 class="accordion-header" id="heading-${purchase.id}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${purchase.id}" aria-expanded="false" aria-controls="collapse-${purchase.id}">
                        <div class="d-flex w-100 justify-content-between">
                            <div>
                                Invoice #${purchase.invoice} - ${formatDateToCustomString(purchase.created_at)}
                                ${getStatusBadgeHtml(purchase.status, purchase.due_date)}
                            </div>
                            <div class="fw-bold">${formatCurrency(purchase.total_amount)}</div>
                        </div>
                    </button>
                </h2>
                <div id="collapse-${purchase.id}" class="accordion-collapse collapse" aria-labelledby="heading-${purchase.id}" data-bs-parent="#srmTransactionHistory">
                    <div class="accordion-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Order Date:</strong> ${formatDateToCustomString(purchase.date)}</p>
                                <p class="mb-1"><strong>Due Date:</strong> ${formatDateToCustomString(purchase.due_date)}</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <p class="mb-1"><strong>Payment Type:</strong> ${purchase.payment_method || "N/A"}</p>
                            </div>
                        </div>
                        <h6 class="fs-4">Items:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${purchase.purchase_items && purchase.purchase_items.length > 0 ? purchase.purchase_items.map((item) => `
                                        <tr>
                                            <td>${item.product ? item.product.name : "N/A"}</td>
                                            <td class="text-center">${item.quantity || 0}</td>
                                            <td class="text-end">${formatCurrency(item.price || 0)}</td>
                                            <td class="text-end">${formatCurrency(item.total || 0)}</td>
                                        </tr>
                                    `).join("") : '<tr><td colspan="4" class="text-center">No items found</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12 text-end">
                                <p class="mb-1"><strong>Subtotal:</strong> ${formatCurrency(
                                    purchase.total_amount -
                                        purchase.tax_amount
                                )}</p>
                                <p class="mb-1"><strong>Discount:</strong> ${formatCurrency(
                                    purchase.discount_amount
                                )}</p>
                                <p class="mb-1"><strong>Tax:</strong> ${formatCurrency(
                                    purchase.tax_amount
                                )}</p>
                                <p class="mb-1"><strong>Grand Total:</strong> <span class="text-primary fw-bold">${formatCurrency(
                                    purchase.total_amount
                                )}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
                transactionHistory.appendChild(purchaseElement);
            });
        } else {
            safeToggleElement(
                "srmNoTransactionsMessage",
                "block"
            );
        }
    }
}

export function loadHistoricalPurchases(id) {
    const historicalPurchaseContent = document.getElementById(
        "srmHistoricalPurchaseContent"
    );

    if (historicalPurchaseContent) {
        historicalPurchaseContent.innerHTML =
            '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    } else {
        console.error(
            "Element with ID 'srmHistoricalPurchaseContent' not found!"
        );
        return;
    }

    fetch(`/admin/suppliers/${id}/historical-purchases`)
        .then((response) => {
            if (!response.ok) {
                throw new Error(
                    `HTTP error! status: ${response.status}`
                );
            }
            return response.json();
        })
        .then((data) => {
            if (historicalPurchaseContent) {
                if (
                    data.historical_purchases &&
                    data.historical_purchases.length > 0
                ) {
                    let contentHtml = `
                <div class="accordion" id="srmHistoricalPurchasesAccordion">
            `;

                    data.historical_purchases.forEach((purchase) => {
                        contentHtml += `
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-${purchase.id}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${purchase.id}" aria-expanded="false" aria-controls="collapse-${purchase.id}">
                                <div class="d-flex justify-content-between w-100 pe-3">
                                    <div>
                                        Invoice #${purchase.invoice} - ${formatDateToCustomString(purchase.order_date)}
                                        ${getStatusBadgeHtml(purchase.status, purchase.due_date)}
                                    </div>
                                    <div class="fw-bold">${formatCurrency(purchase.total_amount)}</div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse-${purchase.id}" class="accordion-collapse collapse" aria-labelledby="heading-${purchase.id}" data-bs-parent="#srmHistoricalPurchasesAccordion">
                            <div class="accordion-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Order Date:</strong> ${formatDateToCustomString(purchase.order_date)}</p>
                                        <p class="mb-1"><strong>Due Date:</strong> ${formatDateToCustomString(purchase.due_date)}</p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <p class="mb-1"><strong>Payment Type:</strong> ${purchase.payment_method || "N/A"}</p>
                                    </div>
                                </div>
                                <h6 class="fs-4">Items:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Unit Price</th>
                                                <th class="text-end">Line Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${purchase.purchase_items && purchase.purchase_items.length > 0 ? purchase.purchase_items.map((item) => `
                                                <tr>
                                                    <td>${item.product ? item.product.name : "N/A"}</td>
                                                    <td class="text-center">${item.quantity || 0}</td>
                                                    <td class="text-end">${formatCurrency(item.price || 0)}</td>
                                                    <td class="text-end">${formatCurrency(item.total || 0)}</td>
                                                </tr>
                                            `).join("") : '<tr><td colspan="4" class="text-center">No items found</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12 text-end">
                                        <p class="mb-1"><strong>Subtotal:</strong> ${formatCurrency(
                                            purchase.total_amount
                                        )}</p>
                                        <p class="mb-1"><strong>Discount:</strong> ${formatCurrency(
                                            purchase.discount_amount
                                        )}</p>
                                        <p class="mb-1"><strong>Grand Total:</strong> <span class="text-primary fw-bold">${formatCurrency(
                                            purchase.total_amount -
                                                purchase.discount_amount
                                        )}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                    });

                    contentHtml += `
                </div>
            `;

                    historicalPurchaseContent.innerHTML = contentHtml;
                } else {
                    historicalPurchaseContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="ti ti-shopping-cart-off fs-1 text-muted"></i>
                    </div>
                    <h5 class="text-muted">No Purchase History</h5>
                    <p class="text-muted mb-0">This supplier hasn't made any purchases yet.</p>
                </div>
            `;
                }
            } else {
                console.error(
                    "Element with ID 'srmHistoricalPurchaseContent' not found!"
                );
            }
        })
        .catch((error) => {
            console.error(
                "Fetch error in loadHistoricalPurchases:",
                error
            );
            if (historicalPurchaseContent) {
                historicalPurchaseContent.innerHTML = `
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="ti ti-alert-circle fs-1 text-danger"></i>
                </div>
                <h5 class="text-danger">Error Loading Data</h5>
                <p class="text-muted mb-0">Failed to load purchase history: ${error.message}</p>
            </div>
        `;
            }
        });
}

export function loadProductHistory(id) {
    const productHistoryContent = document.getElementById(
        "srmProductHistoryContent"
    );

    if (productHistoryContent) {
        productHistoryContent.innerHTML =
            '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    } else {
        console.error(
            "Element with ID 'srmProductHistoryContent' not found!"
        );
        return;
    }

    fetch(`/admin/suppliers/${id}/product-history`)
        .then((response) => {
            if (!response.ok) {
                throw new Error(
                    `HTTP error! status: ${response.status}`
                );
            }
            return response.json();
        })
        .then((data) => {
            if (productHistoryContent) {
                if (
                    data.product_history &&
                    data.product_history.length > 0
                ) {
                    let contentHtml = `
                <div class="accordion" id="srmProductHistoryAccordion">
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
                            )}" data-bs-parent="#srmProductHistoryAccordion">
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

                    productHistoryContent.innerHTML = contentHtml;
                } else {
                    productHistoryContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="ti ti-shopping-cart-off fs-1 text-muted"></i>
                    </div>
                    <h5 class="text-muted">No Product History</h5>
                    <p class="text-muted mb-0">This supplier hasn't purchased any products yet.</p>
                </div>
            `;
                }
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
            }
        });
}

export function handleInteractionForm() {
    const interactionForm = document.getElementById("srmInteractionForm");
    if (interactionForm) {
        interactionForm.addEventListener("submit", function (event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            if (!srmState.supplierId) {
                showToast("Error", "Supplier ID not found.", "error");
                return;
            }

            fetch(`/admin/suppliers/${srmState.supplierId}/interactions`, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.id) {
                        showToast(
                            "Success",
                            "Interaction added.",
                            "success"
                        );
                        const timeline = document.getElementById(
                            "srmInteractionTimeline"
                        );
                        if (timeline) {
                            const newInteraction =
                                document.createElement("div");
                            newInteraction.classList.add(
                                "list-group-item",
                                "list-group-item-action"
                            );
                            newInteraction.innerHTML = `
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">${data.type.charAt(0).toUpperCase() + data.type.slice(1)} on ${new Date(data.interaction_date).toLocaleDateString("id-ID")}</h5>
                                    <small class="text-muted">by ${data.user ? data.user.name : "Unknown"}</small>
                                </div>
                                <p class="mb-1">${data.notes}</p>
                            `;
                                timeline.prepend(newInteraction);
                                safeToggleElement(
                                    "srmNoInteractionsMessage",
                                    "none"
                                );
                            }
                            form.reset();
                            form.querySelector(
                                'input[name="interaction_date"]'
                            ).value = new Date().toISOString().slice(0, 10);
                        } else {
                            showToast(
                                "Error",
                                "Failed to add interaction.",
                                "error"
                            );
                        }
                    })
                    .catch((error) => {
                        console.error("Error:", error);
                        showToast("Error", "An error occurred.", "error");
                    });
            });
        }
    }
