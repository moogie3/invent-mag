import { safeUpdateElement, safeUpdateElementHTML, safeToggleElement } from '../utils/dom.js';

export function showLoadingState() {
    safeUpdateElement("srmSupplierName", "Loading...");
    safeUpdateElement("srmSupplierEmail", "Loading...");
    safeUpdateElement("srmSupplierPhone", "Loading...");
    safeUpdateElement("srmSupplierAddress", "Loading...");
    safeUpdateElement("srmSupplierPaymentTerms", "Loading...");

    safeUpdateElement("srmLifetimeValue", "Loading...");
    safeUpdateElement("srmTotalPurchasesCount", "Loading...");
    safeUpdateElement("srmAverageOrderValue", "Loading...");
    safeUpdateElement("srmLastInteractionDate", "Loading...");
    safeUpdateElement("srmMostPurchasedProduct", "Loading...");
    safeUpdateElement("srmTotalProductsPurchased", "Loading...");
    safeUpdateElement("srmMemberSince", "Loading...");
    safeUpdateElement("srmLastPurchase", "Loading...");

    safeUpdateElementHTML(
        "srmInteractionTimeline",
        '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
    );
    // Removed direct HTML update for srmTransactionHistory and srmProductHistoryContent
    // Their content will be managed by populateHistoricalPurchases and loadProductHistory functions

    safeToggleElement("srmLoadMoreTransactions", "none");
    safeToggleElement("srmNoInteractionsMessage", "none");
    safeToggleElement("srmNoProductHistoryMessage", "none");
}

export function showErrorState(message = "Error") {
    safeUpdateElement("srmSupplierName", message);
    safeUpdateElement("srmSupplierEmail", message);
    safeUpdateElement("srmSupplierPhone", message);
    safeUpdateElement("srmSupplierAddress", message);
    safeUpdateElement("srmSupplierPaymentTerms", message);
    safeUpdateElement("srmLifetimeValue", message);
    safeUpdateElement("srmTotalPurchasesCount", message);
    safeUpdateElement("srmAverageOrderValue", message);
    safeUpdateElement("srmLastInteractionDate", message);
    safeUpdateElement("srmMostPurchasedProduct", message);
    safeUpdateElement("srmTotalProductsPurchased", message);
    safeUpdateElement("srmMemberSince", message);
    safeUpdateElement("srmLastPurchase", message);

    safeUpdateElementHTML(
        "srmInteractionTimeline",
        `<p class="text-danger text-center py-3">Failed to load interactions.</p>`
    );
    safeUpdateElementHTML(
        "srmHistoricalPurchaseContent",
        `<p class="text-danger text-center py-3">Failed to load transactions.</p>`
    );
    safeUpdateElementHTML(
        "srmProductHistoryContent",
        `<p class="text-danger text-center py-3">Failed to load product history.</p>`
    );

    safeToggleElement("srmLoadMoreTransactions", "none");
    safeToggleElement("srmNoProductHistoryMessage", "none");
}

export function showSrmNoInteractionsMessage() {
    safeToggleElement("srmInteractionTimeline", "none");
    safeToggleElement("srmNoInteractionsMessage", "block");
}

export function hideSrmNoInteractionsMessage() {
    safeToggleElement("srmInteractionTimeline", "block");
    safeToggleElement("srmNoInteractionsMessage", "none");
}

export function showSrmNoProductHistoryMessage() {
    safeToggleElement("srmProductHistoryContent", "none");
    safeToggleElement("srmNoProductHistoryMessage", "block");
}

export function hideSrmNoProductHistoryMessage() {
    safeToggleElement("srmProductHistoryContent", "block");
    safeToggleElement("srmNoProductHistoryMessage", "none");
}

export function showSrmNoHistoricalPurchasesMessage() {
    safeToggleElement("srmHistoricalPurchaseContent", "none");
    safeToggleElement("srmNoHistoricalPurchasesMessage", "block");
}

export function hideSrmNoHistoricalPurchasesMessage() {
    safeToggleElement("srmHistoricalPurchaseContent", "block");
    safeToggleElement("srmNoHistoricalPurchasesMessage", "none");
}