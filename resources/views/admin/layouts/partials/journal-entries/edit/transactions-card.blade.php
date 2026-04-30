<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.transactions') }}</h3>
        <div class="card-actions">
            <button type="button" class="btn btn-sm btn-primary" onclick="addTransactionRow()">
                <i class="ti ti-plus me-2"></i>{{ __('messages.add_transaction') }}
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="transactionsContainer">
            <div class="transaction-row header-row d-none d-md-flex">
                <div class="col-md-4">{{ __('messages.account') }}</div>
                <div class="col-md-2">{{ __('messages.type') }}</div>
                <div class="col-md-4">{{ __('messages.amount') }}</div>
                <div class="col-md-2 text-end">{{ __('messages.actions') }}</div>
            </div>
            @php $rowId = 0; @endphp
            @foreach($entry->transactions as $transaction)
                @include('admin.layouts.partials.journal-entries.edit.transaction-row', ['rowId' => $rowId, 'transaction' => $transaction, 'accounts' => $accounts])
                @php $rowId++; @endphp
            @endforeach
        </div>
        
        <template id="transactionRowTemplate">
            @include('admin.layouts.partials.journal-entries.create.transaction-row', ['rowId' => '::ROW_ID::', 'accounts' => $accounts])
        </template>
        
        @include('admin.layouts.partials.journal-entries.edit.summary-section')
    </div>
    <div class="card-footer bg-transparent">
        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.accounting.journal-entries.show', $entry) }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
            <button type="button" class="btn btn-primary" id="updateEntryBtn">
                <i class="ti ti-check me-2"></i>{{ __('messages.update_entry') }}
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    if (typeof window.updateTotals !== 'function') {
        window.updateTotals = function() {
            return { totalDebit: 0, totalCredit: 0, isBalanced: true };
        };
    }
    
    if (typeof window.validateAndSubmit !== 'function') {
        window.validateAndSubmit = function() {};
    }
    
    if (typeof window.handleAccountChange !== 'function') {
        window.handleAccountChange = function(event) {};
    }
    
    if (typeof window.handleInput !== 'function') {
        window.handleInput = function(event) {};
    }
    
    function initUpdateBtn() {
        var updateBtn = document.getElementById('updateEntryBtn');
        if (updateBtn && !updateBtn.dataset.listenerAttached) {
            updateBtn.dataset.listenerAttached = 'true';
            updateBtn.addEventListener('click', function() {
                if (typeof window.validateAndSubmit === 'function') {
                    window.validateAndSubmit();
                }
            });
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUpdateBtn);
    } else {
        initUpdateBtn();
    }
})();
</script>
