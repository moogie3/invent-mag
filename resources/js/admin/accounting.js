import { escapeHtml } from '../utils/sanitize.js';
import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function() {
    const accountingPaths = [
        '/admin/settings/accounting', '/admin/accounting/accounting-setting',
        '/admin/accounting/journal', '/admin/accounting/general-ledger',
        '/admin/reports/income-statement', '/admin/reports/balance-sheet',
        '/admin/reports/aged-receivables', '/admin/reports/aged-payables'
    ];
    if (!accountingPaths.some(p => window.location.pathname.includes(p))) return;

    const coaTemplateModalElement = document.getElementById("coaTemplateConfirmationModal");

    if (coaTemplateModalElement) {
        const applyCoaTemplateBtn = document.getElementById("applyCoaTemplateBtn");
        const coaTemplateConfirmationModalMessage = document.getElementById("coaTemplateConfirmationModalMessage");
        const coaTemplateConfirmationModalConfirmBtn = document.getElementById("coaTemplateConfirmationModalConfirmBtn");

        // Function to update modal message
        const updateModalMessage = () => {
            const templateSelect = document.getElementById("coaTemplateSelect");
            if (!templateSelect || !coaTemplateConfirmationModalMessage) return;

            const selectedOption = templateSelect.options[templateSelect.selectedIndex];
            const selectedTemplateDisplayName = selectedOption.textContent;
            
            // Set modal message
            const templateName = selectedTemplateDisplayName;
            const warningMessage = document.querySelector('meta[name="apply-template-warning"]')?.content || 
                `Are you sure you want to apply the <strong>{template}</strong> Chart of Accounts template? This will overwrite existing accounts, <strong>delete all historical journal entries and transactions</strong>, and cannot be undone.`;
            
            // Use escapeHtml for the template name to prevent XSS
            const safeTemplateName = escapeHtml(templateName);
            coaTemplateConfirmationModalMessage.innerHTML = warningMessage.replace('{template}', `<strong>${safeTemplateName}</strong>`);
        };

        // Update message on button click (primary method)
        if (applyCoaTemplateBtn) {
            applyCoaTemplateBtn.addEventListener("click", updateModalMessage);
        }

        // Also update on modal show (backup method)
        coaTemplateModalElement.addEventListener('show.bs.modal', updateModalMessage);

        // Handle confirmation button click
        if (coaTemplateConfirmationModalConfirmBtn) {
            coaTemplateConfirmationModalConfirmBtn.addEventListener("click", function() {
                // console.log('Confirm button clicked');
                
                // Find the close button to hide modal via DOM click (safest way without bootstrap object)
                const closeBtn = coaTemplateModalElement.querySelector('[data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();

                if (applyCoaTemplateBtn) {
                    applyCoaTemplateBtn.disabled = true;
                    applyCoaTemplateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Applying...';
                }

                const templateSelect = document.getElementById("coaTemplateSelect");
                if (!templateSelect) return;
                const selectedTemplateValue = templateSelect.value;

                fetch("/admin/settings/apply-coa-template", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? '',
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        template: selectedTemplateValue,
                    }),
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        InventMagApp.showToast("Success", data.message, "success");
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        InventMagApp.showToast("Error", data.message || "An error occurred.", "error");
                        if (applyCoaTemplateBtn) {
                            applyCoaTemplateBtn.disabled = false;
                            applyCoaTemplateBtn.innerHTML = 'Apply Template'; // Reset text
                        }
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    InventMagApp.showToast("Error", "An unexpected error occurred.", "error");
                    if (applyCoaTemplateBtn) {
                        applyCoaTemplateBtn.disabled = false;
                        applyCoaTemplateBtn.innerHTML = 'Apply Template';
                    }
                });
            });
        }
    }

    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const startDateInput = document.querySelector('[name="start_date"]');
            const endDateInput = document.querySelector('[name="end_date"]');
            if (!startDateInput || !endDateInput) return;
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            const url = new URL(window.location.href);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            window.location.href = url.href;
        });
    }
});

function exportAccounts(exportOption) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/accounting/accounts/export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

function exportJournal(exportOption) {
    const startDateEl = document.getElementById('start_date');
    const endDateEl = document.getElementById('end_date');
    if (!startDateEl || !endDateEl) return;
    const startDate = startDateEl.value;
    const endDate = endDateEl.value;

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/accounting/journal/export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    const startDateInput = document.createElement("input");
    startDateInput.type = "hidden";
    startDateInput.name = "start_date";
    startDateInput.value = startDate;
    form.appendChild(startDateInput);

    const endDateInput = document.createElement("input");
    endDateInput.type = "hidden";
    endDateInput.name = "end_date";
    endDateInput.value = endDate;
    form.appendChild(endDateInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

function exportGeneralLedger(exportOption) {
    const accountIdEl = document.getElementById('account_id');
    const startDateEl = document.getElementById('start_date');
    const endDateEl = document.getElementById('end_date');
    if (!accountIdEl || !startDateEl || !endDateEl) return;
    const accountId = accountIdEl.value;
    const startDate = startDateEl.value;
    const endDate = endDateEl.value;

    if (!accountId) {
        InventMagApp.showToast("Warning", "Please select an account to export.", "warning");
        return;
    }

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/accounting/general-ledger/export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    const accountIdInput = document.createElement("input");
    accountIdInput.type = "hidden";
    accountIdInput.name = "account_id";
    accountIdInput.value = accountId;
    form.appendChild(accountIdInput);

    const startDateInput = document.createElement("input");
    startDateInput.type = "hidden";
    startDateInput.name = "start_date";
    startDateInput.value = startDate;
    form.appendChild(startDateInput);

    const endDateInput = document.createElement("input");
    endDateInput.type = "hidden";
    endDateInput.name = "end_date";
    endDateInput.value = endDate;
    form.appendChild(endDateInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}


function exportTrialBalance(exportOption) {
    const endDateEl = document.getElementById('end_date');
    if (!endDateEl) return;
    const endDate = endDateEl.value;

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/accounting/trial-balance/export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    const endDateInput = document.createElement("input");
    endDateInput.type = "hidden";
    endDateInput.name = "end_date";
    endDateInput.value = endDate;
    form.appendChild(endDateInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

function exportIncomeStatement(exportOption) {
    const startDateEl = document.getElementById('start_date');
    const endDateEl = document.getElementById('end_date');
    if (!startDateEl || !endDateEl) return;
    const startDate = startDateEl.value;
    const endDate = endDateEl.value;

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/reports/income-statement/export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    const startDateInput = document.createElement("input");
    startDateInput.type = "hidden";
    startDateInput.name = "start_date";
    startDateInput.value = startDate;
    form.appendChild(startDateInput);

    const endDateInput = document.createElement("input");
    endDateInput.type = "hidden";
    endDateInput.name = "end_date";
    endDateInput.value = endDate;
    form.appendChild(endDateInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

function exportBalanceSheet(exportOption) {
    const endDateEl = document.getElementById('end_date');
    if (!endDateEl) return;
    const endDate = endDateEl.value;

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/reports/balance-sheet/export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    const endDateInput = document.createElement("input");
    endDateInput.type = "hidden";
    endDateInput.name = "end_date";
    endDateInput.value = endDate;
    form.appendChild(endDateInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

function exportAgedReceivables(exportOption) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/reports/aged-receivables/export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

function exportAgedPayables(exportOption) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/reports/aged-payables/export";
    form.style.display = "none";

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content");
        form.appendChild(token);
    }

    const exportOptionInput = document.createElement("input");
    exportOptionInput.type = "hidden";
    exportOptionInput.name = "export_option";
    exportOptionInput.value = exportOption;
    form.appendChild(exportOptionInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
}

window.exportIncomeStatement = exportIncomeStatement;
window.exportTrialBalance = exportTrialBalance;
window.exportGeneralLedger = exportGeneralLedger;
window.exportJournal = exportJournal;
window.exportBalanceSheet = exportBalanceSheet;
window.exportAgedReceivables = exportAgedReceivables;
window.exportAgedPayables = exportAgedPayables;
window.exportAccounts = exportAccounts;
