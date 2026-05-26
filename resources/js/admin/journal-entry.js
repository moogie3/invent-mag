import { onPageLoad } from './utils/onPageLoad.js';

let rowCounter = null;

// Define functions before they're used
function formatCurrency(amount) {
    if (typeof window.currencySettings !== "undefined") {
        return new Intl.NumberFormat(window.currencySettings.locale, {
            style: "currency",
            currency: window.currencySettings.currency_code,
            minimumFractionDigits: window.currencySettings.decimal_places,
            maximumFractionDigits: window.currencySettings.decimal_places,
        }).format(amount);
    }
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

function showNotification(message, type = "info") {
    const title = type.charAt(0).toUpperCase() + type.slice(1);
    if (typeof window.InventMagApp !== "undefined" && typeof window.InventMagApp.showToast === "function") {
        window.InventMagApp.showToast(title, message, type);
    } else if (typeof toastr !== "undefined") {
        toastr[type](message);
    } else if (typeof showToast !== "undefined") {
        showToast(message, type);
    } else {
        console.log(`[${type}] ${message}`);
    }
}

function updateAccountSelect(row) {
    const select = row.querySelector(".account-select");
    if (!select) return;

    const selectedCode = select.value;
    const rows = document.querySelectorAll(
        ".transaction-row:not(.header-row):not(.template-row)",
    );

    rows.forEach((r) => {
        if (r !== row) {
            const otherSelect = r.querySelector(".account-select");
            if (otherSelect) {
                const options = otherSelect.querySelectorAll("option");
                options.forEach((opt) => {
                    opt.disabled =
                        opt.value === selectedCode && selectedCode !== "";
                });
            }
        }
    });
}

function handleInput(event) {
    if (event.target.classList.contains("amount-input") || event.target.classList.contains("transaction-type")) {
        window.updateTotals();
    }
}

function handleAccountChange(event) {
    if (event.target.classList.contains("account-select")) {
        const row = event.target.closest(".transaction-row");
        updateAccountSelect(row);
    }
    if (event.target.classList.contains("transaction-type")) {
        window.updateTotals();
    }
}

function setupEventListeners() {
    const container = document.getElementById("transactionsContainer");
    if (container) {
        container.addEventListener("input", handleInput);
        container.addEventListener("change", handleAccountChange);
    }
}

function initializeJournalEntry() {
    const form = document.getElementById("journalEntryForm");
    if (form) {
        rowCounter = parseInt(form.dataset.rowCounter) || 2;
        setupEventListeners();
    }
    window.updateTotals();
}

// Attach click handlers for the create-form save buttons.
// These live in transactions-card (create) partial and must be wired after
// the real validateAndSubmit function is defined above.
function initSaveBtns() {
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const savePostBtn  = document.getElementById('savePostBtn');

    if (saveDraftBtn && !saveDraftBtn.dataset.listenerAttached) {
        saveDraftBtn.dataset.listenerAttached = 'true';
        saveDraftBtn.addEventListener('click', function () {
            window.validateAndSubmit(false);
        });
    }

    if (savePostBtn && !savePostBtn.dataset.listenerAttached) {
        savePostBtn.dataset.listenerAttached = 'true';
        savePostBtn.addEventListener('click', function () {
            window.validateAndSubmit(true);
        });
    }
}

// Attach click handler for the edit-form update button.
function initUpdateBtn() {
    const updateBtn = document.getElementById('updateEntryBtn');
    if (updateBtn && !updateBtn.dataset.listenerAttached) {
        updateBtn.dataset.listenerAttached = 'true';
        updateBtn.addEventListener('click', function () {
            window.validateAndSubmit();
        });
    }
}

onPageLoad(function () {
    if (!window.location.pathname.includes("/admin/accounting/journal-entries")) return;
    initializeJournalEntry();
    initDeleteModal();
    initSaveBtns();
    initUpdateBtn();
});

// Delete modal initialization
function initDeleteModal() {
    const deleteModal = document.getElementById("deleteModal");
    
    if (!deleteModal) return;
    if (deleteModal.dataset.initialized) return;
    deleteModal.dataset.initialized = 'true';
    
    deleteModal.addEventListener("show.bs.modal", function(event) {
        const button = event.relatedTarget;
        if (!button) return;
        
        const entryId = button.dataset.id;
        const deleteForm = document.getElementById("deleteForm");
        if (deleteForm && entryId) {
            deleteForm.action = `/admin/accounting/journal-entries/${entryId}`;
        }
    });
    
    const deleteForm = document.getElementById("deleteForm");
    if (deleteForm && !deleteForm.dataset.handlerAttached) {
        deleteForm.dataset.handlerAttached = 'true';
        deleteForm.addEventListener("submit", function(e) {
            e.preventDefault();
            handleDeleteSubmit(this);
        });
    }
}

function handleDeleteSubmit(form) {
    if (form.dataset.submitting === 'true') return;
    form.dataset.submitting = 'true';
    
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Deleting...';
    }

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof window.InventMagApp !== 'undefined' && typeof window.InventMagApp.showToast === 'function') {
                window.InventMagApp.showToast('Success', data.message || 'Deleted successfully', 'success');
            }
            const deleteModal = bootstrap.Modal.getInstance(document.getElementById("deleteModal"));
            if (deleteModal) {
                deleteModal._element.addEventListener('hidden.bs.modal', function handler() {
                    deleteModal._element.removeEventListener('hidden.bs.modal', handler);
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    location.reload();
                }, { once: true });
                deleteModal.hide();
            } else {
                location.reload();
            }
        } else {
            if (typeof window.InventMagApp !== 'undefined' && typeof window.InventMagApp.showToast === 'function') {
                window.InventMagApp.showToast('Error', data.message || 'Failed to delete', 'error');
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Delete';
            }
            form.dataset.submitting = 'false';
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        if (typeof window.InventMagApp !== 'undefined' && typeof window.InventMagApp.showToast === 'function') {
            window.InventMagApp.showToast('Error', 'Failed to delete. Please try again.', 'error');
        }
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Delete';
        }
        form.dataset.submitting = 'false';
    });
}

// Expose functions to window
window.handleInput = function(event) {
    if (event.target.classList.contains("amount-input") || event.target.classList.contains("transaction-type")) {
        window.updateTotals();
    }
};

window.handleAccountChange = function(event) {
    if (event.target.classList.contains("account-select")) {
        const row = event.target.closest(".transaction-row");
        updateAccountSelect(row);
    }
    if (event.target.classList.contains("transaction-type")) {
        window.updateTotals();
    }
};

window.addTransactionRow = function () {
    const container = document.getElementById("transactionsContainer");
    const template = document.getElementById("transactionRowTemplate");
    if (template && container) {
        const newRow = template.content.cloneNode(true);
        const rowElement = newRow.querySelector(".transaction-row");
        rowElement.dataset.rowId = rowCounter;
        rowElement.innerHTML = rowElement.innerHTML.replace(/::ROW_ID::/g, rowCounter);
        container.appendChild(rowElement);
        rowCounter++;
        window.updateTotals();
    }
};

window.removeTransactionRow = function (button) {
    const rows = document.querySelectorAll(
        ".transaction-row:not(.header-row):not(.template-row)",
    );
    if (rows.length <= 2) {
        showNotification("Minimum two transactions are required", "warning");
        return;
    }
    button.closest(".transaction-row").remove();
    window.updateTotals();
};

window.updateTotals = function () {
    let totalDebit = 0;
    let totalCredit = 0;

    document
        .querySelectorAll(
            ".transaction-row:not(.header-row):not(.template-row)",
        )
        .forEach((row) => {
            const type = row.querySelector(".transaction-type");
            const amountInput = row.querySelector(".amount-input");

            if (type && amountInput) {
                const amount = parseFloat(amountInput.value) || 0;
                if (type.value === "debit") {
                    totalDebit += amount;
                } else {
                    totalCredit += amount;
                }
            }
        });

    const totalDebitEl = document.getElementById("totalDebit");
    const totalCreditEl = document.getElementById("totalCredit");
    const differenceEl = document.getElementById("difference");
    const balanceStatusEl = document.getElementById("balanceStatus");

    if (totalDebitEl) totalDebitEl.textContent = formatCurrency(totalDebit);
    if (totalCreditEl) totalCreditEl.textContent = formatCurrency(totalCredit);
    if (differenceEl)
        differenceEl.textContent = formatCurrency(totalDebit - totalCredit);

    if (balanceStatusEl) {
        if (Math.abs(totalDebit - totalCredit) < 0.01) {
            balanceStatusEl.textContent =
                window.translations?.balanced || "Balanced";
            balanceStatusEl.className = "badge bg-success text-white";
        } else {
            balanceStatusEl.textContent =
                window.translations?.not_balanced || "Not Balanced";
            balanceStatusEl.className = "badge bg-danger text-white";
        }
    }

    return {
        totalDebit,
        totalCredit,
        isBalanced: Math.abs(totalDebit - totalCredit) < 0.01,
    };
};

window.validateAndSubmit = function (autoPost = false) {
    const rows = document.querySelectorAll(
        ".transaction-row:not(.header-row):not(.template-row)",
    );
    const transactions = [];

    rows.forEach((row) => {
        const accountSelect = row.querySelector(".account-select");
        const typeSelect = row.querySelector(".transaction-type");
        const amountInput = row.querySelector(".amount-input");

        if (
            accountSelect &&
            typeSelect &&
            amountInput &&
            accountSelect.value &&
            parseFloat(amountInput.value) > 0
        ) {
            transactions.push({
                account_code: accountSelect.value,
                type: typeSelect.value,
                amount: parseFloat(amountInput.value),
            });
        }
    });

    if (transactions.length < 2) {
        showNotification("Minimum two transactions are required", "warning");
        return Promise.reject(new Error("Minimum two transactions required"));
    }

    const { totalDebit, totalCredit, isBalanced } = window.updateTotals();
    if (!isBalanced) {
        showNotification(
            "Journal entry must be balanced. Debits: " +
                formatCurrency(totalDebit) +
                ", Credits: " +
                formatCurrency(totalCredit),
            "error",
        );
        return Promise.reject(new Error("Entry not balanced"));
    }

    const transactionsInputEl = document.getElementById("transactionsInput");
    if (transactionsInputEl) transactionsInputEl.value = JSON.stringify(transactions);

    const form = document.getElementById("journalEntryForm");
    if (form) {
        form.submit();
    }
};

window.searchAccounts = async function (query) {
    if (!query || query.length < 1) return [];

    try {
        const response = await fetch(
            `/admin/accounting/journal-entries/search/accounts?query=${encodeURIComponent(query)}`,
            {
            headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    )?.content ?? '',
                },
            },
        );
        const data = await response.json();
        return data.accounts || [];
    } catch (error) {
        console.error("Error searching accounts:", error);
        showNotification("Error searching accounts. Please try again.", "error");
        return [];
    }
};

window.confirmAction = function (url, title, message) {
    if (confirm(message)) {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = url;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content ?? ''}">
            <input type="hidden" name="_method" value="DELETE">
        `;
        document.body.appendChild(form);
        form.submit();
    }
};

window.reverseEntry = function (url) {
    const reverseModal = document.getElementById('reverseModal');
    const reverseForm = document.getElementById('reverseForm');
    if (reverseForm && reverseModal) {
        reverseForm.action = url;
        reverseModal.addEventListener('shown.bs.modal', function () {
            reverseForm.querySelector('textarea')?.focus();
        }, { once: true });
        const modal = new bootstrap.Modal(reverseModal);
        modal.show();
    }
};

window.voidEntry = function (url) {
    const voidModal = document.getElementById('voidModal');
    const voidForm = document.getElementById('voidForm');
    if (voidForm && voidModal) {
        voidForm.action = url;
        voidModal.addEventListener('shown.bs.modal', function () {
            voidForm.querySelector('textarea')?.focus();
        }, { once: true });
        const modal = new bootstrap.Modal(voidModal);
        modal.show();
    }
};
