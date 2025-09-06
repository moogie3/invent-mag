import { srmState, setSrmState } from './state.js';
import { showLoadingState, showErrorState } from './ui.js';
import { loadSrmData, loadHistoricalPurchases, loadProductHistory, handleInteractionForm } from './api.js';

export function initSrmSupplierModal() {
    const srmSupplierModal = document.getElementById("srmSupplierModal");

    if (srmSupplierModal) {
        srmSupplierModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            setSrmState({ supplierId: button.getAttribute("data-id") });
            if (!srmState.supplierId) {
                console.error("Supplier ID not found");
                showErrorState("Supplier ID not found");
                return;
            }

            setSrmState({ currentPage: 1 });
            showLoadingState();
            loadSrmData(srmState.supplierId, srmState.currentPage);
        });

        const loadMoreBtn = document.getElementById("srmLoadMoreTransactions");
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener("click", function () {
                if (srmState.currentPage < srmState.lastPage) {
                    setSrmState({ currentPage: srmState.currentPage + 1 });
                    loadSrmData(srmState.supplierId, srmState.currentPage, true);
                }
            });
        }

        handleInteractionForm();

        const historicalPurchaseTab = document.getElementById(
            "srm-historical-purchases-tab"
        );
        if (historicalPurchaseTab) {
            historicalPurchaseTab.addEventListener(
                "shown.bs.tab",
                function (event) {
                    if (srmState.supplierId) {
                        loadHistoricalPurchases(srmState.supplierId);
                    }
                }
            );
        }

        const productHistoryTab = document.getElementById(
            "srm-product-history-tab"
        );
        if (productHistoryTab) {
            productHistoryTab.addEventListener(
                "shown.bs.tab",
                function (event) {
                    if (srmState.supplierId) {
                        loadProductHistory(srmState.supplierId);
                    }
                }
            );
        }
    }
}
