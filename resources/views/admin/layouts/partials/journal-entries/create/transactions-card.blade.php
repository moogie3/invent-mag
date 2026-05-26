<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.transactions') }}</h3>
        <div class="card-actions">
            <button type="button" class="btn btn-sm btn-primary" data-onclick="addTransactionRow()">
                <i class="ti ti-plus me-2"></i>{{ __('messages.add_transaction') }}
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="transactionsContainer">
            <div class="transaction-row header-row d-none d-md-flex mb-2 fw-bold">
                <div class="col-md-4">{{ __('messages.account') }}</div>
                <div class="col-md-2">{{ __('messages.type') }}</div>
                <div class="col-md-4">{{ __('messages.amount') }}</div>
                <div class="col-md-2 text-end">{{ __('messages.actions') }}</div>
            </div>
            @for($rowId = 0; $rowId < 2; $rowId++)
                @include('admin.layouts.partials.journal-entries.create.transaction-row', ['rowId' => $rowId, 'accounts' => $accounts])
            @endfor
        </div>
        
        <template id="transactionRowTemplate">
            @include('admin.layouts.partials.journal-entries.create.transaction-row', ['rowId' => '::ROW_ID::', 'accounts' => $accounts])
        </template>
        
        @include('admin.layouts.partials.journal-entries.create.summary-section')
    </div>
    <div class="card-footer bg-transparent">
        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.accounting.journal-entries.index') }}" class="btn btn-secondary rounded-2">{{ __('messages.cancel') }}</a>
            <div>
                <button type="button" class="btn btn-outline-secondary me-2 rounded-2" id="saveDraftBtn">
                    <i class="ti ti-device-floppy me-2"></i>{{ __('messages.save_as_draft') }}
                </button>
                <button type="button" class="btn btn-primary rounded-2" id="savePostBtn">
                    <i class="ti ti-check me-2"></i>{{ __('messages.save_and_post') }}
                </button>
            </div>

{{-- Button listeners and function stubs handled by resources/js/admin/journal-entry.js --}}
        </div>
    </div>
</div>
