<div class="transaction-row row g-2 mb-2 align-items-center" data-row-id="{{ $rowId }}">
    <div class="col-md-4">
        <select class="form-select account-select" onchange="handleAccountChange(event)">
            <option value="">{{ __('messages.select_account') }}</option>
            @foreach($accounts as $account)
                <option value="{{ $account->code }}">{{ $account->code }} - {{ __($account->name) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select transaction-type">
            <option value="debit">{{ __('messages.debit') }}</option>
            <option value="credit">{{ __('messages.credit') }}</option>
        </select>
    </div>
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control amount-input" placeholder="0,00" step="0.01" min="0">
        </div>
    </div>
    <div class="col-md-2 text-end">
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTransactionRow(this)">
            <i class="ti ti-trash"></i>
        </button>
    </div>
</div>
