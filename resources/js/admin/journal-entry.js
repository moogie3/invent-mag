let rowCounter = null;

document.addEventListener("DOMContentLoaded", function () {
    initializeJournalEntry();
});

function initializeJournalEntry() {
    const form = document.getElementById("journalEntryForm");
    if (form) {
        rowCounter = parseInt(form.dataset.rowCounter) || 2;
        setupEventListeners();
    }
    updateTotals();
}

function setupEventListeners() {
    const container = document.getElementById("transactionsContainer");
    if (container) {
        container.addEventListener("input", handleInput);
        container.addEventListener("change", handleAccountChange);
    }
}

function handleInput(event) {
    if (event.target.classList.contains("amount-input") || event.target.classList.contains("transaction-type")) {
        updateTotals();
    }
}

function handleAccountChange(event) {
    if (event.target.classList.contains("account-select")) {
        const row = event.target.closest(".transaction-row");
        updateAccountSelect(row);
    }
    if (event.target.classList.contains("transaction-type")) {
        updateTotals();
    }
}

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
        updateTotals();
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
    updateTotals();
};

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
            balanceStatusEl.className = "badge bg-success";
        } else {
            balanceStatusEl.textContent =
                window.translations?.not_balanced || "Not Balanced";
            balanceStatusEl.className = "badge bg-danger";
        }
    }

    return {
        totalDebit,
        totalCredit,
        isBalanced: Math.abs(totalDebit - totalCredit) < 0.01,
    };
};

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

    const { totalDebit, totalCredit, isBalanced } = updateTotals();
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

    document.getElementById("transactionsInput").value =
        JSON.stringify(transactions);

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
                    ).content,
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
        form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">`;
        document.body.appendChild(form);
        form.submit();
    }
};

window.reverseEntry = function (url) {
    const reason = prompt(
        window.translations?.enter_reason ||
            "Please enter a reason for reversing this entry:"
    );
    if (reason !== null && reason.trim() !== "") {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = url;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
            <input type="hidden" name="notes" value="${reason.replace(/"/g, "&quot;")}">
        `;
        document.body.appendChild(form);
        form.submit();
    } else if (reason !== null) {
        showNotification("Reason cannot be empty", "warning");
    }
};

window.voidEntry = function (url) {
    const reason = prompt(
        window.translations?.enter_void_reason ||
            "Please enter a reason for voiding this entry:"
    );
    if (reason !== null && reason.trim() !== "") {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = url;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
            <input type="hidden" name="reason" value="${reason.replace(/"/g, "&quot;")}">
        `;
        document.body.appendChild(form);
        form.submit();
    } else if (reason !== null) {
        showNotification("Reason cannot be empty", "warning");
    }
};

window.reverseEntry = function (url) {
    const reason = prompt(
        window.translations?.enter_reason ||
            "Please enter a reason for reversing this entry:",
    );
    if (reason !== null && reason.trim() !== "") {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = url;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${document.querySelector("meta[name=csrf-token]").content}">
            <input type="hidden" name="notes" value="${reason.replace(/"/g, "&quot;")}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
};

window.voidEntry = function (url) {
    const reason = prompt(
        window.translations?.enter_void_reason ||
            "Please enter a reason for voiding this entry:",
    );
    if (reason !== null && reason.trim() !== "") {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = url;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${document.querySelector("meta[name=csrf-token]").content}">
            <input type="hidden" name="reason" value="${reason.replace(/"/g, "&quot;")}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
};

function showNotification(message, type = "info") {
    const title = type.charAt(0).toUpperCase() + type.slice(1);
    if (typeof InventMagApp !== "undefined" && typeof InventMagApp.showToast === "function") {
        InventMagApp.showToast(title, message, type);
    } else if (typeof toastr !== "undefined") {
        toastr[type](message);
    } else if (typeof showToast !== "undefined") {
        showToast(message, type);
    } else {
        console.log(`[${type}] ${message}`);
    }
}
