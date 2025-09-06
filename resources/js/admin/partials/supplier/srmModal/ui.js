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
    safeUpdateElementHTML(
        "srmTransactionHistory",
        '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
    );
    safeUpdateElementHTML(
        "srmHistoricalPurchaseContent",
        '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>'
    );

    safeToggleElement("srmLoadMoreTransactions", "none");
    safeToggleElement("srmNoInteractionsMessage", "none");
    safeToggleElement("srmNoTransactionsMessage", "none");
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
        "srmTransactionHistory",
        `<p class="text-danger text-center py-3">Failed to load transactions.</p>`
    );
    safeUpdateElementHTML(
        "srmHistoricalPurchaseContent",
        `<p class="text-danger text-center py-3">Failed to load purchase history.</p>`
    );

    safeToggleElement("srmLoadMoreTransactions", "none");
}
