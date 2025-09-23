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
    // Removed direct HTML update for transactionHistory and productHistoryContent
    // Their content will be managed by populateTransactions and loadProductHistory functions

    // Hide buttons and messages
    safeToggleElement("loadMoreTransactions", "none");
    safeToggleElement("noInteractionsMessage", "none");
    safeToggleElement("noTransactionsMessage", "none");
    safeToggleElement("noProductHistoryMessage", "none");
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
        "productHistoryContent",
        `<p class="text-danger text-center py-3">Failed to load product history.</p>`
    );

    safeToggleElement("loadMoreTransactions", "none");
    safeToggleElement("noProductHistoryMessage", "none");
}

export function showNoInteractionsMessage() {
    safeToggleElement("interactionTimeline", "none");
    safeToggleElement("noInteractionsMessage", "block");
}

export function hideNoInteractionsMessage() {
    safeToggleElement("interactionTimeline", "block");
    safeToggleElement("noInteractionsMessage", "none");
}

export function showNoTransactionsMessage() {
    safeToggleElement("transactionHistory", "none");
    safeToggleElement("noTransactionsMessage", "block");
}

export function hideNoTransactionsMessage() {
    safeToggleElement("transactionHistory", "block");
    safeToggleElement("noTransactionsMessage", "none");
}

export function showNoProductHistoryMessage() {
    safeToggleElement("productHistoryContent", "none");
    safeToggleElement("noProductHistoryMessage", "block");
}

export function hideNoProductHistoryMessage() {
    safeToggleElement("productHistoryContent", "block");
    safeToggleElement("noProductHistoryMessage", "none");
}

