import { safeUpdateElement, safeUpdateElementHTML, safeToggleElement } from '../utils/dom.js';

export function showLoadingState() {
    // Update customer info elements
    safeUpdateElement("crmCustomerName", "Loading...");
    safeUpdateElement("crmCustomerEmail", "Loading...");
    safeUpdateElement("crmCustomerPhone", "Loading...");
    safeUpdateElement("crmCustomerAddress", "Loading...");
    safeUpdateElement("crmCustomerPaymentTerms", "Loading...");

    // Update metrics elements
    safeUpdateElement("crmLifetimeValue", "Loading...");
    safeUpdateElement("crmTotalSalesCount", "Loading...");
    safeUpdateElement("crmAverageOrderValue", "Loading...");
    safeUpdateElement("crmLastInteractionDate", "Loading...");
    safeUpdateElement("crmMostPurchasedProduct", "Loading...");
    safeUpdateElement("crmTotalProductsPurchased", "Loading...");
    safeUpdateElement("crmMemberSince", "Loading...");
    safeUpdateElement("crmLastPurchase", "Loading...");

    // Show loading spinners
    safeUpdateElementHTML(
        "interactionTimeline",
        '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
    );
    safeUpdateElementHTML(
        "transactionHistory",
        '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
    );
    safeUpdateElementHTML(
        "purchaseHistoryContent",
        '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
    );

    // Hide buttons and messages
    safeToggleElement("loadMoreTransactions", "none");
    safeToggleElement("noInteractionsMessage", "none");
    safeToggleElement("noTransactionsMessage", "none");
}

export function showErrorState(message = "Error") {
    safeUpdateElement("crmCustomerName", message);
    safeUpdateElement("crmCustomerEmail", message);
    safeUpdateElement("crmCustomerPhone", message);
    safeUpdateElement("crmCustomerAddress", message);
    safeUpdateElement("crmCustomerPaymentTerms", message);
    safeUpdateElement("crmLifetimeValue", message);
    safeUpdateElement("crmTotalSalesCount", message);
    safeUpdateElement("crmAverageOrderValue", message);
    safeUpdateElement("crmLastInteractionDate", message);
    safeUpdateElement("crmMostPurchasedProduct", message);
    safeUpdateElement("crmTotalProductsPurchased", message);
    safeUpdateElement("crmMemberSince", message);
    safeUpdateElement("crmLastPurchase", message);

    safeUpdateElementHTML(
        "interactionTimeline",
        `<p class="text-danger text-center py-3">Failed to load interactions.</p>`
    );
    safeUpdateElementHTML(
        "transactionHistory",
        `<p class="text-danger text-center py-3">Failed to load transactions.</p>`
    );
    safeUpdateElementHTML(
        "purchaseHistoryContent",
        `<p class="text-danger text-center py-3">Failed to load purchase history.</p>`
    );

    safeToggleElement("loadMoreTransactions", "none");
}
