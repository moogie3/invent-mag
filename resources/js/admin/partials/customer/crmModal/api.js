import { crmState } from "./state.js";
import { showLoadingState, showErrorState, showNoTransactionsMessage, hideNoTransactionsMessage } from "./ui.js";
import {
    safeUpdateElement,
    safeUpdateElementHTML,
    safeToggleElement,
} from "../utils/dom.js";
import { formatCurrency } from "../../../../utils/currencyFormatter.js";
import { formatDateToCustomString } from "../utils/date.js";
import { getStatusBadgeHtml } from "../utils/status.js";
import { escapeHtml } from "../../../../utils/sanitize.js";

export function loadCrmData(id, page, append = false) {
    if (!id) {
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

            populateCrmData(data, append);

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
            InventMagApp.showToast("Error", `Failed to load CRM data: ${error.message}`, "error");
            showErrorState("Failed to load");
        });
}

function populateCrmData(data, append = false) {
    if (data.customer) {
        safeUpdateElement("crmCustomerName", data.customer.name || "N/A");
        safeUpdateElement("crmCustomerEmail", data.customer.email || "N/A");
        safeUpdateElement("crmCustomerPhone", data.customer.phone_number || "N/A");
        safeUpdateElement("crmCustomerAddress", data.customer.address || "N/A");
        safeUpdateElement("crmCustomerPaymentTerms", data.customer.payment_terms || "N/A");
        
        const crmCustomerImageContainer = document.getElementById("crmCustomerImageContainer");
        const defaultPlaceholderUrl = document.querySelector('meta[name="default-placeholder-url"]')?.content || window.defaultPlaceholderUrl || "/img/default_placeholder.png";

        if (crmCustomerImageContainer) {
            if (data.customer.image && data.customer.image !== defaultPlaceholderUrl && !data.customer.image.includes('default_placeholder')) {
                const imgUrl = data.customer.image.startsWith('http') ? data.customer.image : `/storage/${data.customer.image}`;
                crmCustomerImageContainer.innerHTML = `
                    <img id="crmCustomerImage" src="${imgUrl}" alt="Customer Image"
                        class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                `;
            } else {
                crmCustomerImageContainer.innerHTML = `
                    <div class="d-flex justify-content-center align-items-center rounded-circle bg-secondary text-white shadow-sm"
                        style="width: 100px; height: 100px; font-size: 2.5rem; margin: 0 auto;">
                        <i class="ti ti-user"></i>
                    </div>
                `;
            }
        }
        
        if (!append) {
            safeUpdateElement("crmLifetimeValue", formatCurrency(data.lifetimeValue || 0));
            safeUpdateElement("crmTotalSalesCount", data.totalSalesCount || 0);
            safeUpdateElement("crmAverageOrderValue", formatCurrency(data.averageOrderValue || 0));
            safeUpdateElement("crmLastInteractionDate", data.lastInteractionDate ? new Date(data.lastInteractionDate).toLocaleDateString("id-ID") : "N/A");
            safeUpdateElement("crmMostPurchasedProduct", data.mostPurchasedProduct || "N/A");
            safeUpdateElement("crmTotalProductsPurchased", data.totalProductsPurchased || 0);
            safeUpdateElement("crmMemberSince", data.customer.created_at ? new Date(data.customer.created_at).toLocaleDateString("id-ID") : "N/A");
            safeUpdateElement("crmLastPurchase", data.lastPurchaseDate ? new Date(data.lastPurchaseDate).toLocaleDateString("id-ID") : "N/A");
        }
        
        populateInteractions(data.customer.interactions, append);
    }
    
    if (data.customerCredits) {
        populateCustomerCredits(data.customerCredits, data.totalAvailableCredits);
    }
    
    if (data.sales) {
        populateTransactions(data.sales.data, append);
    }
}

function populateInteractions(interactions, append = false) {
    const interactionTimeline = document.getElementById("interactionTimeline");
    if (interactionTimeline) {
        if (!append) {
            interactionTimeline.innerHTML = "";
        }

        if (interactions && interactions.length > 0) {
            safeToggleElement("noInteractionsMessage", "none");
            interactions.forEach((interaction) => {
                const interactionElement = document.createElement("div");
                interactionElement.classList.add("list-group-item", "list-group-item-action");
                interactionElement.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">${interaction.type.charAt(0).toUpperCase() + interaction.type.slice(1)} on ${new Date(interaction.interaction_date).toLocaleDateString("id-ID")}</h5>
                        <small class="text-muted">by ${interaction.user ? escapeHtml(interaction.user.name) : "Unknown"}</small>
                    </div>
                    <p class="mb-1">${escapeHtml(interaction.notes)}</p>
                `;
                interactionTimeline.appendChild(interactionElement);
            });
        } else {
            safeToggleElement("noInteractionsMessage", "block");
        }
    }
}

function populateCustomerCredits(credits, totalAvailable) {
    const totalAvailableCreditsEl = document.getElementById("totalAvailableCredits");
    const creditsTableBody = document.getElementById("customerCreditsTableBody");
    
    if (totalAvailableCreditsEl) {
        totalAvailableCreditsEl.textContent = formatCurrency(totalAvailable || 0);
    }
    
    if (creditsTableBody) {
        if (credits && credits.length > 0) {
            creditsTableBody.innerHTML = "";
            credits.forEach((credit, index) => {
                const statusBadge = credit.status === 'active' 
                    ? '<span class="badge bg-success text-white">Active</span>'
                    : credit.status === 'used'
                        ? '<span class="badge bg-secondary text-white">Used</span>'
                        : '<span class="badge bg-warning text-white">Expired</span>';
                
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td class="fw-bold">${formatCurrency(credit.amount)}</td>
                    <td>${formatCurrency(credit.used_amount)}</td>
                    <td class="fw-bold text-success">${formatCurrency(credit.available_amount)}</td>
                    <td>${statusBadge}</td>
                    <td>${credit.expires_at ? credit.expires_at : '-'}</td>
                    <td>${credit.reason || '-'}</td>
                `;
                creditsTableBody.appendChild(row);
            });
        } else {
            creditsTableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="empty">
                            <div class="empty-img">
                                <i class="ti ti-credit-card-off fs-1 text-muted"></i>
                            </div>
                            <p class="empty-title">No credits available</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    }
}

function populateTransactions(sales, append = false) {
    const transactionHistory = document.getElementById("transactionHistory");
    if (transactionHistory) {
        if (!append) {
            transactionHistory.innerHTML = "";
        }

        if (sales && sales.length > 0) {
            hideNoTransactionsMessage();

            sales.forEach((sale) => {
                const saleElement = document.createElement("div");
                saleElement.classList.add("accordion-item");
                saleElement.innerHTML = `
                    <h2 class="accordion-header" id="heading-${sale.id}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${sale.id}" aria-expanded="false" aria-controls="collapse-${sale.id}">
                            <div class="d-flex justify-content-between w-100 pe-3">
                                <div>
                                    Invoice #${escapeHtml(sale.invoice)} - ${formatDateToCustomString(sale.created_at)}
                                    ${getStatusBadgeHtml(sale.status, sale.due_date)}
                                </div>
                                <div class="fw-bold">${formatCurrency(sale.grand_total_adjusted)}</div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse-${sale.id}" class="accordion-collapse collapse" aria-labelledby="heading-${sale.id}" data-bs-parent="#transactionHistory">
                        <div class="accordion-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Order Date:</strong> ${formatDateToCustomString(sale.order_date)}</p>
                                    <p class="mb-1"><strong>Due Date:</strong> ${formatDateToCustomString(sale.due_date)}</p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p class="mb-1"><strong>Payment Type:</strong> ${escapeHtml(sale.payment_type) || "N/A"}</p>
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
                                            sale.sales_items && sale.sales_items.length > 0
                                                ? sale.sales_items.map((item) => {
                                                    const returnedQty = item.returned_quantity || 0;
                                                    const adjustedQty = item.adjusted_quantity ?? item.quantity;
                                                    const qtyHtml = returnedQty > 0 
                                                        ? `<span class="fw-bold">${adjustedQty}</span><br><small class="text-danger"><i class="ti ti-package-export me-1"></i>(-${returnedQty} Returned)</small>`
                                                        : adjustedQty;
                                                        
                                                    return `
                                                    <tr>
                                                        <td>${item.product ? escapeHtml(item.product.name) : "N/A"}</td>
                                                        <td class="text-center">${qtyHtml}</td>
                                                        <td class="text-end">${formatCurrency(item.customer_price || 0)}</td>
                                                        <td class="text-end">${formatCurrency(item.adjusted_total ?? item.total ?? 0)}</td>
                                                    </tr>
                                                `}).join('')
                                                : '<tr><td colspan="4" class="text-center">No items found</td></tr>'
                                        }
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12 text-end">
                                    <p class="mb-1"><strong>Subtotal:</strong> ${formatCurrency(sale.summary_subtotal || 0)}</p>
                                    <p class="mb-1"><strong>Discount:</strong> ${formatCurrency(sale.summary_order_discount || 0)}</p>
                                    <p class="mb-1"><strong>Tax:</strong> ${formatCurrency(sale.summary_tax_amount || 0)}</p>
                                    <p class="mb-1"><strong>Grand Total:</strong> <span class="text-primary fw-bold">${formatCurrency(sale.grand_total_adjusted)}</span></p>
                                    ${sale.returned_amount > 0 ? `<p class="mb-1 text-muted border-bottom border-light pb-2"><small><i class="ti ti-package-export me-1"></i>Returned (Totals Adjusted): -${formatCurrency(sale.returned_amount)}</small></p>` : ''}
                                    <p class="mb-1 mt-2"><strong>Total Paid:</strong> ${formatCurrency(sale.total_paid)}</p>
                                    <p class="mb-1 text-danger"><strong>Balance Due:</strong> ${formatCurrency(sale.balance)}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                transactionHistory.appendChild(saleElement);
            });
        } else {
            showNoTransactionsMessage();
        }
    }
}

export function handleInteractionForm() {
    const interactionForm = document.getElementById("interactionForm");
    if (!interactionForm) return;
    if (interactionForm.dataset.handlerAttached) return;
    interactionForm.dataset.handlerAttached = 'true';

    interactionForm.addEventListener("submit", function (event) {
        if (event.defaultPrevented) return;
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        if (!crmState.customerId) {
            InventMagApp.showToast("Error", "Customer ID not found.", "error");
            return;
        }

        if (form.dataset.submitting === 'true') return;
        form.dataset.submitting = 'true';

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }

        fetch(`/admin/customers/${crmState.customerId}/interactions`, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || '',
            },
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.id) {
                InventMagApp.showToast("Success", "Interaction added.", "success");
                const timeline = document.getElementById("interactionTimeline");
                if (timeline) {
                    const newInteraction = document.createElement("div");
                    newInteraction.classList.add("list-group-item", "list-group-item-action");
                    newInteraction.innerHTML = `
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">${data.type.charAt(0).toUpperCase() + data.type.slice(1)} on ${new Date(data.interaction_date).toLocaleDateString("id-ID")}</h5>
                            <small class="text-muted">by ${escapeHtml(data.user?.name || "Unknown")}</small>
                        </div>
                        <p class="mb-1">${escapeHtml(data.notes)}</p>
                    `;
                    timeline.prepend(newInteraction);
                    safeToggleElement("noInteractionsMessage", "none");
                }
                form.reset();
                const dateInput = form.querySelector('input[name="interaction_date"]');
                if (dateInput) dateInput.value = new Date().toISOString().slice(0, 10);
            } else {
                InventMagApp.showToast("Error", data.message || "Failed to add interaction.", "error");
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            InventMagApp.showToast("Error", "An error occurred.", "error");
        })
        .finally(() => {
            form.dataset.submitting = 'false';
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Interaction';
            }
        });
    });
}
