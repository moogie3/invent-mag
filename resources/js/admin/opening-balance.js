import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(function() {
    if (!window.location.pathname.includes('/admin/accounting/opening-balance')) return;

    // Sync amount when user types in debit or credit field
    window.syncAmount = function(accountId, source) {
        const typeSelect = document.querySelector(`select[data-account-id="${accountId}"]`);
        const debitInput = document.querySelector(`input[data-account-id="${accountId}"].debit-input`);
        const creditInput = document.querySelector(`input[data-account-id="${accountId}"].credit-input`);
        
        if (source === 'debit') {
            if (debitInput.value) {
                creditInput.value = '';
                typeSelect.value = 'debit';
            }
        } else {
            if (creditInput.value) {
                debitInput.value = '';
                typeSelect.value = 'credit';
            }
        }
        
        window.updateBalance(accountId);
    };

    // Update balance display for an account
    window.updateBalance = function(accountId) {
        const typeSelect = document.querySelector(`select[data-account-id="${accountId}"]`);
        const debitInput = document.querySelector(`input[data-account-id="${accountId}"].debit-input`);
        const creditInput = document.querySelector(`input[data-account-id="${accountId}"].credit-input`);
        const balanceDisplay = document.getElementById(`balance_${accountId}`);
        
        const debit = parseFloat(debitInput.value) || 0;
        const credit = parseFloat(creditInput.value) || 0;
        const type = typeSelect.value;
        
        let balance = 0;
        if (type === 'debit') {
            balance = debit;
        } else {
            balance = -credit;
        }
        
        balanceDisplay.textContent = window.formatCurrency(balance);
        window.calculateTotals();
    };

    // Calculate totals for all accounts
    window.calculateTotals = function() {
        let totalDebit = 0;
        let totalCredit = 0;
        
        document.querySelectorAll('.debit-input').forEach(input => {
            totalDebit += parseFloat(input.value) || 0;
        });
        
        document.querySelectorAll('.credit-input').forEach(input => {
            totalCredit += parseFloat(input.value) || 0;
        });
        
        const difference = totalDebit - totalCredit;
        
        document.getElementById('totalDebit').textContent = window.formatCurrency(totalDebit);
        document.getElementById('totalCredit').textContent = window.formatCurrency(totalCredit);
        
        const diffElement = document.getElementById('totalDifference');
        diffElement.innerHTML = `
            <span class="${Math.abs(difference) < 0.01 ? 'text-success' : 'text-danger'}">
                ${window.formatCurrency(difference)}
            </span>
            ${Math.abs(difference) < 0.01 ? '<i class="ti ti-check text-success ms-1"></i>' : '<i class="ti ti-alert-triangle text-danger ms-1"></i>'}
        `;
    };

    // Format currency
    window.formatCurrency = function(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 2
        }).format(amount);
    };

    // Reset all balances
    window.resetAllBalances = function() {
        if (!confirm(window.openingBalanceTranslations?.reset_all_confirm || 'Are you sure you want to reset all balances?')) {
            return;
        }
        
        document.querySelectorAll('.debit-input').forEach(input => input.value = '');
        document.querySelectorAll('.credit-input').forEach(input => input.value = '');
        document.querySelectorAll('.balance-type').forEach(select => select.value = 'debit');
        document.querySelectorAll('.balance-display').forEach(display => display.textContent = window.formatCurrency(0));
        
        window.calculateTotals();
    };

    // Delete opening balances
    window.deleteOpeningBalances = function() {
        if (!confirm(window.openingBalanceTranslations?.delete_opening_confirm || 'Are you sure you want to delete all opening balances for this date?')) {
            return;
        }
        
        document.getElementById('deleteForm').submit();
    };

    // Initialize on page load
    window.calculateTotals();
    
    // Handle balance date change
    const balanceDateInput = document.getElementById('balance_date');
    if (balanceDateInput) {
        balanceDateInput.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('date', this.value);
            window.location.href = url.toString();
        });
    }
});

// Make functions available globally
window.syncAmount = window.syncAmount || function() {};
window.updateBalance = window.updateBalance || function() {};
window.calculateTotals = window.calculateTotals || function() {};
window.formatCurrency = window.formatCurrency || function(amount) { return amount; };
window.resetAllBalances = window.resetAllBalances || function() {};
window.deleteOpeningBalances = window.deleteOpeningBalances || function() {};
