<div class="row mt-3">
    <div class="col-md-6">
        <div class="alert alert-info">
            <i class="ti ti-info-circle me-2"></i>{{ __('messages.journal_entry_tip') }}
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-light">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <span>{{ __('messages.total_debit') }}:</span>
                    <strong id="totalDebit">{{ \App\Helpers\CurrencyHelper::format($entry->total_debit) }}</strong>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span>{{ __('messages.total_credit') }}:</span>
                    <strong id="totalCredit">{{ \App\Helpers\CurrencyHelper::format($entry->total_credit) }}</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>{{ __('messages.difference') }}:</span>
                    <strong id="difference">{{ \App\Helpers\CurrencyHelper::format($entry->total_debit - $entry->total_credit) }}</strong>
                </div>
                <div class="mt-2">
                    @php
                        $isBalanced = abs($entry->total_debit - $entry->total_credit) < 0.01;
                    @endphp
                    <span id="balanceStatus" class="badge bg-{{ $isBalanced ? 'success' : 'danger' }}">
                        {{ $isBalanced ? __('messages.balanced') : __('messages.not_balanced') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
