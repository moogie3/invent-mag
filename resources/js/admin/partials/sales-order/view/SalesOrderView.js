import { SalesOrderModule } from '../common/SalesOrderModule.js';

export class SalesOrderView extends SalesOrderModule {
    constructor(config = {}) {
        super(config);

        this.elements = {
            deleteForm: this.safeGetElement("deleteForm"),
            viewSalesModalContent: this.safeGetElement("viewSalesModalContent"),
            salesModalEdit: this.safeGetElement("salesModalEdit"),
            salesModalFullView: this.safeGetElement("salesModalFullView"),
            salesModalPrint: this.safeGetElement("salesModalPrint"),
        };

        this.formatAllCurrencyValues();
        this.initModalListeners();
        this.initGlobalFunctions();

        document
            .querySelectorAll(".view-sales-details-btn")
            .forEach((button) => {
                button.addEventListener("click", function () {
                    const salesId = this.dataset.id;
                    window.showSalesDetailsModal(salesId);
                });
            });
    }

    formatAllCurrencyValues() {
        const currencyElements = document.querySelectorAll(".currency-value");
        currencyElements.forEach((element) => {
            const value = parseFloat(element.dataset.value) || 0;
            element.textContent = this.formatCurrency(value);
        });
    }

    initModalListeners() {
        if (this.elements.salesModalPrint) {
            this.elements.salesModalPrint.addEventListener("click", () =>
                this.printModalContent()
            );
        }

        const viewSalesModalElement = document.getElementById("viewSalesModal");
        if (viewSalesModalElement) {
            viewSalesModalElement.addEventListener(
                "shown.bs.modal",
                (event) => {
                    const salesId = viewSalesModalElement.dataset.salesId;
                    if (salesId) {
                        this.loadSalesDetails(salesId);
                    }
                }
            );
        }
    }

    showSalesDetailsModal(salesId) {
        const viewSalesModalElement = document.getElementById("viewSalesModal");
        if (viewSalesModalElement) {
            viewSalesModalElement.dataset.salesId = salesId;
            const salesViewModal = new bootstrap.Modal(viewSalesModalElement);
            salesViewModal.show();
        }
    }

    initGlobalFunctions() {
        window.setDeleteFormAction = (url) => this.setDeleteFormAction(url);
        window.showSalesDetailsModal = (salesId) =>
            this.showSalesDetailsModal(salesId);
    }

    setDeleteFormAction(url) {
        if (this.elements.deleteForm) {
            this.elements.deleteForm.action = url;
        } else {
            console.error("Delete form element not found");
        }
    }

    loadSalesDetails(id) {
        if (
            this.elements.viewSalesModalContent.innerHTML.includes(
                "spinner-border"
            ) ||
            this.elements.viewSalesModalContent.innerHTML.trim() === ""
        ) {
            this.elements.viewSalesModalContent.innerHTML =
                '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            fetch(`/admin/sales/modal-view/${id}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(
                            `HTTP error! Status: ${response.status}`
                        );
                    }
                    return response.text();
                })
                .then((html) => {
                    if (this.elements.viewSalesModalContent) {
                        this.elements.viewSalesModalContent.innerHTML = html;
                        this.formatAllCurrencyValues();

                        if (this.elements.salesModalEdit) {
                            this.elements.salesModalEdit.href = `/admin/sales/edit/${id}`;
                        }
                        if (this.elements.salesModalFullView) {
                            this.elements.salesModalFullView.href = `/admin/sales/view/${id}`;
                        }
                    }
                })
                .catch((error) => {
                    console.error("Error loading sales details:", error);
                    if (this.elements.viewSalesModalContent) {
                        this.elements.viewSalesModalContent.innerHTML =
                            '<div class="alert alert-danger">Failed to load sales details.</div>';
                    }
                    showToast(
                        "Error",
                        "Failed to load sales details.",
                        "error"
                    );
                });
        }
    }

    printModalContent() {
        if (!this.elements.viewSalesModalContent) return;

        const printContent = this.elements.viewSalesModalContent.innerHTML;
        const originalContent = document.body.innerHTML;

        document.body.innerHTML = `
            <div class="container print-container">
                <div class="card">
                    <div class="card-body">${printContent}</div>
                </div>
            </div>
        `;

        window.print();
        document.body.innerHTML = originalContent;

        setTimeout(() => window.location.reload(), 100);
    }
}
