import { PurchaseOrderModule } from '../common/PurchaseOrderModule.js';

export class PurchaseOrderView extends PurchaseOrderModule {
    constructor(config = {}) {
        super(config);

        this.elements = {
            deleteForm: this.safeGetElement("deleteForm"),
            viewPoModalContent: this.safeGetElement("viewPoModalContent"),
            poModalEdit: this.safeGetElement("poModalEdit"),
            poModalFullView: this.safeGetElement("poModalFullView"),
            poModalPrint: this.safeGetElement("poModalPrint"),
        };

        this.formatAllCurrencyValues();
        this.initModalListeners();
        this.initGlobalFunctions();
    }

    formatAllCurrencyValues() {
        const currencyElements = document.querySelectorAll(".currency-value");
        currencyElements.forEach((element) => {
            const value = parseFloat(element.dataset.value) || 0;
            element.textContent = this.formatCurrency(value);
        });
    }

    initModalListeners() {
        if (this.elements.poModalPrint) {
            this.elements.poModalPrint.addEventListener("click", () =>
                this.printModalContent()
            );
        }
    }

    initGlobalFunctions() {
        window.loadPoDetails = (id) => this.loadPoDetails(id);
        window.setDeleteFormAction = (url) => this.setDeleteFormAction(url);
    }

    setDeleteFormAction(url) {
        if (this.elements.deleteForm) {
            this.elements.deleteForm.action = url;
        } else {
            // // console.error("Delete form element not found");
        }
    }

    loadPoDetails(id) {
        if (!this.elements.viewPoModalContent) {
            // // console.error("Modal content element not found");
            return;
        }

        if (this.elements.poModalEdit) {
            this.elements.poModalEdit.href = `/admin/po/edit/${id}`;
        }
        if (this.elements.poModalFullView) {
            this.elements.poModalFullView.href = `/admin/po/view/${id}`;
        }

        this.elements.viewPoModalContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading purchase order details...</p>
                </div>
            `;

        fetch(`/admin/po/modal-view/${id}`)
            .then((response) => {
                if (!response.ok)
                    throw new Error("Network response was not ok");
                return response.text();
            })
            .then((html) => {
                this.elements.viewPoModalContent.innerHTML = html;
                this.formatAllCurrencyValues();
            })
            .catch((error) => {
                this.elements.viewPoModalContent.innerHTML = `
                        <div class="alert alert-danger m-3">
                            <i class="ti ti-alert-circle me-2"></i> Error loading PO details: ${error.message}
                        </div>
                    `;
            });
    }

    printModalContent() {
        if (!this.elements.viewPoModalContent) return;

        const printContent = this.elements.viewPoModalContent.innerHTML;
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
