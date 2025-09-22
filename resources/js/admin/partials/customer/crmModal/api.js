import { crmState } from "./state.js";
import { showLoadingState, showErrorState } from "./ui.js";
import {
    safeUpdateElement,
    safeUpdateElementHTML,
    safeToggleElement,
} from "../utils/dom.js";
import { formatCurrency } => "../../../../utils/currencyFormatter.js";
import { formatDateToCustomString } from "../utils/date.js";
import { getStatusBadgeHtml } from "../utils/status.js";

export function loadCrmData(id, page, append = false) {
    if (!id) {
        console.error("Customer ID is required");
        showErrorState("Customer ID is required");
        return;
    }

    fetch(`/admin/customers/${id}/crm-details?page=${page}`)
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (!data || !data.customer) {
                throw new Error("Customer data not found in response");
            }

            if (!append) {
                safeUpdateElementHTML("interactionTimeline", "");
                safeUpdateElementHTML("transactionHistory", "");
                safeToggleElement("noInteractionsMessage", "none");
                safeToggleElement("noTransactionsMessage", "none");
            }

            populateCrmData(data);

            crmState.lastPage = data.sales.last_page || 1;
            const currentPageNum = data.sales ? data.sales.current_page : 1;
            const lastPageNum = data.sales ? data.sales.last_page : 1;

            if (currentPageNum < lastPageNum) {
                safeToggleElement("loadMoreTransactions", "block");
            } else {
                safeToggleElement("loadMoreTransactions", "none");
            }
        })
        .catch((error) => {
            console.error("Error loading CRM data:", error);
            InventMagApp.showToast(
                "Error",
                `Failed to load CRM data: ${error.message}`,
                "error"
            );
            showErrorState("Failed to load");
        });
}

function populateCrmData(data) {
    safeUpdateElement("crmCustomerName", data.customer.name || "N/A");
    safeUpdateElement("crmCustomerEmail", data.customer.email || "N/A");
    safeUpdateElement("crmCustomerPhone", data.customer.phone_number || "N/A");
    safeUpdateElement("crmCustomerAddress", data.customer.address || "N/A");
    safeUpdateElement(
        "crmCustomerPaymentTerms",
        data.customer.payment_terms || "N/A"
    );

    const crmCustomerImageContainer = document.getElementById(
        "crmCustomerImageContainer"
    );
    const defaultPlaceholderUrl =
        window.defaultPlaceholderUrl || "/img/default_placeholder.png";

    if (crmCustomerImageContainer) {
        if (
            data.customer.image &&
            data.customer.image !== defaultPlaceholderUrl
        ) {
            crmCustomerImageContainer.innerHTML = `
            <img src="${data.customer.image}" alt="${
                data.customer.name || "Customer Image"
            }"
                 class="img-thumbnail"
                 style="max-width: 120px; max-height: 120px; object-fit: cover;">
        `;
        } else {
            crmCustomerImageContainer.innerHTML = `
            <div class="img-thumbnail d-flex align-items-center justify-content-center"
                 style="width: 120px; height: 120px; margin: 0 auto;">
                <i class="ti ti-photo fs-1 text-muted"></i>
            </div>
        `;
        }
    }

    safeUpdateElement(
        "crmLifetimeValue",
        formatCurrency(data.lifetimeValue || 0)
    );
    safeUpdateElement("crmTotalSalesCount", data.totalSalesCount || 0);
    safeUpdateElement(
        "crmAverageOrderValue",
        formatCurrency(data.averageOrderValue || 0)
    );
    safeUpdateElement(
        "crmLastInteractionDate",
        data.lastInteractionDate
            ? new Date(data.lastInteractionDate).toLocaleDateString("id-ID")
            : "N/A"
    );
    safeUpdateElement(
        "crmMostPurchasedProduct",
        data.mostPurchasedProduct || "N/A"
    );
    safeUpdateElement(
        "crmTotalProductsPurchased",
        data.totalProductsPurchased || 0
    );
    safeUpdateElement(
        "crmMemberSince",
        data.customer.created_at
            ? new Date(data.customer.created_at).toLocaleDateString("id-ID")
            : "N/A"
    );
    safeUpdateElement(
        "crmLastPurchase",
        data.lastPurchaseDate
            ? new Date(data.lastPurchaseDate).toLocaleDateString("id-ID")
            : "N/A"
    );

    populateInteractions(data.customer.interactions);
    populateTransactions(data.sales.data);
}

function populateInteractions(interactions) {
    const interactionTimeline = document.getElementById("interactionTimeline");
    if (interactionTimeline) {
        interactionTimeline.innerHTML = "";

        if (interactions && interactions.length > 0) {
            safeToggleElement("noInteractionsMessage", "none");
            interactions.forEach((interaction) => {
                const interactionElement = document.createElement("div");
                interactionElement.classList.add(
                    "list-group-item",
                    "list-group-item-action"
                );
                interactionElement.innerHTML = `
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">${
                        interaction.type.charAt(0).toUpperCase() +
                        interaction.type.slice(1)
                    } on ${new Date(
                    interaction.interaction_date
                ).toLocaleDateString("id-ID")}</h5>
                    <small class="text-muted">by ${
                        interaction.user ? interaction.user.name : "Unknown"
                    }</small>
                </div>
                <p class="mb-1">${interaction.notes}</p>
            `;
                interactionTimeline.appendChild(interactionElement);
            });
        } else {
            safeToggleElement("noInteractionsMessage", "block");
        }
    }
}

function populateTransactions(sales) {
    const transactionHistory = document.getElementById("transactionHistory");
    if (transactionHistory) {
        transactionHistory.innerHTML = "";

        if (sales && sales.length > 0) {
            safeToggleElement("noTransactionsMessage", "none");

            sales.forEach((sale) => {
                const saleElement = document.createElement("div");
                saleElement.classList.add("accordion-item");
                saleElement.innerHTML = `
                <h2 class="accordion-header" id="heading-${sale.id}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${
                        sale.id
                    }" aria-expanded="false" aria-controls="collapse-${
                    sale.id
                }">
                        <div class="d-flex justify-content-between w-100 pe-3">
                            <div>
                                Invoice #${
                                    sale.invoice
                                } - ${formatDateToCustomString(sale.created_at)}
                                ${getStatusBadgeHtml(
                                    sale.status,
                                    sale.due_date
                                )}
                            </div>
                            <div class="fw-bold">${formatCurrency(
                                sale.total
                            )}</div>
                        </div>
                    </button>
                </h2>
                <div id="collapse-${
                    sale.id
                }" class="accordion-collapse collapse" aria-labelledby="heading-${
                    sale.id
                }" data-bs-parent="#transactionHistory">
                    <div class="accordion-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Order Date:</strong> ${formatDateToCustomString(
                                    sale.order_date
                                )}</p>
                                <p class="mb-1"><strong>Due Date:</strong> ${formatDateToCustomString(
                                    sale.due_date
                                )}</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <p class="mb-1"><strong>Payment Type:</strong> ${
                                    sale.payment_type || "N/A"
                                }</p>
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
                                    ${
                                        sale.sales_items &&
                                        sale.sales_items.length > 0
                                            ? sale.sales_items
                                                  .map(
                                                      (item) => `
                                        <tr>
                                            <td>${
                                                item.product
                                                    ? item.product.name
                                                    : "N/A"
                                            }</td>
                                            <td class="text-center">${
                                                item.quantity || 0
                                            }</td>
                                            <td class="text-end">${formatCurrency(
                                                item.customer_price || 0
                                            )}</td>
                                            <td class="text-end">${formatCurrency(
                                                item.total || 0
                                            )}</td>
                                        </tr>
                                    `
                                                  )
                                                  .join("")
                                            : '<tr><td colspan="4" class="text-center">No items found</td></tr>'
                                    }
                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12 text-end">
                                <p class="mb-1"><strong>Subtotal:</strong> ${formatCurrency(
                                    sale.sales_items &&
                                        sale.sales_items.length > 0
                                        ? sale.sales_items.reduce(
                                              (sum, item) =>
                                                  sum +
                                                  (parseFloat(item.total) || 0),
                                              0
                                          )
                                        : 0
                                )}</p>
                                <p class="mb-1"><strong>Discount:</strong> ${formatCurrency(
                                    sale.order_discount
                                )}</p>
                                <p class="mb-1"><strong>Tax:</strong> ${formatCurrency(
                                    sale.total_tax
                                )}</p>
                                <p class="mb-1"><strong>Grand Total:</strong> <span class="text-primary fw-bold">${formatCurrency(
                                    sale.total
                                )}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
                transactionHistory.appendChild(saleElement);
            });
        } else {
            safeToggleElement("noTransactionsMessage", "block");
        }
    }
}

export function handleInteractionForm() {
    const interactionForm = document.getElementById("interactionForm");
    if (interactionForm) {
        interactionForm.addEventListener("submit", function (event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            if (!crmState.customerId) {
                InventMagApp.showToast("Error", "Customer ID not found.", "error");
                return;
            }

            fetch(`/admin/customers/${crmState.customerId}/interactions`, {
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
                        InventMagApp.showToast("Success", "Interaction added.", "success");
                        const timeline = document.getElementById(
                            "interactionTimeline"
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
                            <h5 class="mb-1">${
                                data.type.charAt(0).toUpperCase() +
                                data.type.slice(1)
                            } on ${new Date(
                                data.interaction_date
                            ).toLocaleDateString("id-ID")}</h5>
                            <small class="text-muted">by ${
                                data.user.name
                            }</small>
                        </div>
                        <p class="mb-1">${data.notes}</p>
                    `;
                            timeline.prepend(newInteraction);
                            safeToggleElement("noInteractionsMessage", "none");
                        }
                        form.reset();
                        form.querySelector(
                            'input[name="interaction_date"]'
                        ).value = new Date().toISOString().slice(0, 10);
                    } else {
                        InventMagApp.showToast(
                            "Error",
                            "Failed to add interaction.",
                            "error"
                        );
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    InventMagApp.showToast("Error", "An error occurred.", "error");
                });
        });
    }
}