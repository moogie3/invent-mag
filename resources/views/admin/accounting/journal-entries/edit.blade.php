@extends('admin.layouts.base')

@section('title', __('messages.edit_journal_entry'))

@section('content')
<div class="container-xl">
    <div class="page-header d-print-none mt-4">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.accounting') }}</div>
                <h2 class="page-title">
                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                        <i class="ti ti-edit"></i>
                    </span>
                    {{ __('messages.edit_journal_entry') }}
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('admin.accounting.journal-entries.show', $entry) }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left"></i>
                    {{ __('messages.back') }}
                </a>
            </div>
        </div>
    </div>

    <div class="page-body mt-4">
        <form id="journalEntryForm" action="{{ route('admin.accounting.journal-entries.update', $entry) }}" method="POST" data-row-counter="{{ $entry->transactions->count() }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="transactions" id="transactionsInput">

            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">{{ __('messages.date') }}</label>
                            <input type="date" name="date" class="form-control" value="{{ $entry->date->toDateString() }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">{{ __('messages.description') }}</label>
                            <input type="text" name="description" class="form-control" value="{{ $entry->description }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('messages.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $entry->notes }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.transactions') }}</h3>
                    <div class="card-actions">
                        <button type="button" class="btn btn-sm btn-primary" onclick="addTransactionRow()">
                            <i class="ti ti-plus"></i>
                            {{ __('messages.add_transaction') }}
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
                            @include('admin.accounting.journal-entries.partials.transaction-row-edit', ['rowId' => $rowId, 'transaction' => $transaction, 'accounts' => $accounts])
                            @php $rowId++; @endphp
                        @endforeach
                    </div>

                    <template id="transactionRowTemplate">
                        @include('admin.accounting.journal-entries.partials.transaction-row', ['rowId' => '::ROW_ID::', 'accounts' => $accounts])
                    </template>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle me-2"></i>
                                {{ __('messages.journal_entry_tip') }}
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
                                        <span id="balanceStatus" class="badge bg-{{ abs($entry->total_debit - $entry->total_credit) < 0.01 ? 'success' : 'danger' }}">
                                            {{ abs($entry->total_debit - $entry->total_credit) < 0.01 ? __('messages.balanced') : __('messages.not_balanced') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.accounting.journal-entries.show', $entry) }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                        <button type="button" class="btn btn-primary" onclick="validateAndSubmit()">
                            <i class="ti ti-check"></i>
                            {{ __('messages.update_entry') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
