document.addEventListener("DOMContentLoaded", function() {
    const coaTemplateModalElement = document.getElementById("coaTemplateConfirmationModal");

    if (coaTemplateModalElement) {
        const applyCoaTemplateBtn = document.getElementById("applyCoaTemplateBtn");
        const coaTemplateConfirmationModal = new bootstrap.Modal(coaTemplateModalElement, {});
        const coaTemplateConfirmationModalMessage = document.getElementById(
            "coaTemplateConfirmationModalMessage"
        );
        const coaTemplateConfirmationModalConfirmBtn = document.getElementById(
            "coaTemplateConfirmationModalConfirmBtn"
        );

        if (applyCoaTemplateBtn) {
            applyCoaTemplateBtn.addEventListener("click", function () {
                const templateSelect = document.getElementById("coaTemplateSelect");
                const selectedOption = templateSelect.options[templateSelect.selectedIndex];
                const selectedTemplateDisplayName = selectedOption.textContent;
                const selectedTemplateValue = templateSelect.value;
                const originalButtonText = this.innerHTML;

                // Set modal message
                coaTemplateConfirmationModalMessage.innerHTML = `Are you sure you want to apply the <strong>${selectedTemplateDisplayName}</strong> Chart of Accounts template? This will overwrite existing accounts, <strong>delete all historical journal entries and transactions</strong>, and cannot be undone.`;
                coaTemplateConfirmationModal.show();

                // Handle confirmation
                coaTemplateConfirmationModalConfirmBtn.onclick = () => {
                    coaTemplateConfirmationModal.hide();
                    this.disabled = true;
                    this.innerHTML =
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Applying...';

                    fetch("/admin/settings/apply-coa-template", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
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
                                InventMagApp.showToast(
                                    "Success",
                                    data.message,
                                    "success"
                                );
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                InventMagApp.showToast(
                                    "Error",
                                    data.message || "An error occurred.",
                                    "error"
                                );
                            }
                        })
                        .catch((error) => {
                            console.error("Error:", error);
                            InventMagApp.showToast(
                                "Error",
                                "An unexpected error occurred.",
                                "error"
                            );
                        })
                        .finally(() => {
                            this.disabled = false;
                            this.innerHTML = originalButtonText;
                        });
                };
            });
        }
    }

    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const startDate = document.querySelector('[name="start_date"]').value;
            const endDate = document.querySelector('[name="end_date"]').value;
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
    form.action = "/admin/accounting/export-all";
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
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

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
    const accountId = document.getElementById('account_id').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

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
    const endDate = document.getElementById('end_date').value;

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
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

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
    const endDate = document.getElementById('end_date').value;

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

window.exportIncomeStatement = exportIncomeStatement;
window.exportTrialBalance = exportTrialBalance;
window.exportGeneralLedger = exportGeneralLedger;
window.exportJournal = exportJournal;
window.exportBalanceSheet = exportBalanceSheet;
window.exportAgedReceivables = exportAgedReceivables;
window.exportAccounts = exportAccounts;
