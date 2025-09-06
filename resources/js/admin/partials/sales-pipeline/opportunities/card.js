import { formatCurrencyJs, getStatusColor } from '../common/utils.js';

export function createOpportunityCard(opportunity) {
    const card = document.createElement("div");
    card.className = "card card-sm mb-2 opportunity-card";
    card.dataset.opportunityId = opportunity.id;

    const customerName = opportunity.customer
        ? opportunity.customer.name
        : "Unknown Customer";
    const amount = opportunity.amount
        ? formatCurrencyJs(opportunity.amount)
        : "No amount";
    const expectedCloseDate = opportunity.expected_close_date
        ? new Date(opportunity.expected_close_date).toLocaleDateString(
              "en-GB",
              { day: "numeric", month: "long", year: "numeric" }
          )
        : "No date";

    card.innerHTML = `
        <div class="card-body">
            <h4 class="card-title">${opportunity.name}</h4>
            <p class="card-text">
                <strong>Customer:</strong> ${customerName}<br>
                <strong>Amount:</strong> ${amount}<br>
                <strong>Expected Close:</strong> ${expectedCloseDate}<br>
                <strong>Status:</strong> <span class="badge ${getStatusColor(
                    opportunity.status
                )} text-white">${
        opportunity.status.charAt(0).toUpperCase() +
        opportunity.status.slice(1)
    }</span>
            </p>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm edit-opportunity-btn"
                        data-opportunity-id="${opportunity.id}" ${
        opportunity.status === "converted" ? "disabled" : ""
    }>
                    <i class="ti ti-edit"></i> Edit
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm delete-opportunity-btn"
                        data-opportunity-id="${opportunity.id}">
                    <i class="ti ti-trash"></i> Delete
                </button>
                ${
                    opportunity.status === "won" && !opportunity.sales_id
                        ? `<button type="button" class="btn btn-success btn-sm convert-opportunity-btn" data-opportunity-id="${opportunity.id}"><i class="ti ti-check"></i> Convert to Sales Order</button>`
                        : ""
                }
            </div>
        </div>
    `;

    return card;
}
