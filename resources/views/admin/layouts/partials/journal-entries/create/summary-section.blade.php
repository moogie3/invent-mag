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
                    <strong id="totalDebit">Rp 0,00</strong>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span>{{ __('messages.total_credit') }}:</span>
                    <strong id="totalCredit">Rp 0,00</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>{{ __('messages.difference') }}:</span>
                    <strong id="difference">Rp 0,00</strong>
                </div>
                <div class="mt-2">
                    <span id="balanceStatus" class="badge bg-danger">{{ __('messages.not_balanced') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
