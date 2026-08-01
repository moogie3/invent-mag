@extends('admin.layouts.base')

@section('title', __('messages.bank_reconciliation_import'))

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}"
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            <a href="{{ route('admin.bank-reconciliation.index') }}" class="text-muted text-decoration-none">
                                <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                        <h2 class="page-title d-flex align-items-center">
                            <i class="ti ti-upload me-2"></i> {{ __('messages.bank_reconciliation_import') }}
                            <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                                <i class="ti ti-help-circle fs-3"></i>
                            </a>
                        </h2>
                        <div class="text-muted mt-1">
                            {{ __('messages.bank_reconciliation_import_summary') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}"
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                <div class="row row-cards">
                    <div class="col-md-8 mx-auto">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('messages.bank_reconciliation_import_details') }}</h3>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.bank-reconciliation.store') }}" method="POST"
                                    enctype="multipart/form-data" novalidate data-validate-form>
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('messages.bank_reconciliation_bank') }}</label>
                                        <select class="form-select @error('bank') is-invalid @enderror" name="bank" required>
                                            <option value="">{{ __('messages.bank_reconciliation_select_bank') }}</option>
                                            @foreach($banks as $slug => $name)
                                                <option value="{{ $slug }}" {{ old('bank') == $slug ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        @error('bank')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('messages.bank_reconciliation_account') }}</label>
                                        <select class="form-select @error('account_id') is-invalid @enderror" name="account_id" required>
                                            <option value="">{{ __('messages.bank_reconciliation_select_account') }}</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" {{ old('account_id', $defaultAccountId) == $account->id ? 'selected' : '' }}>
                                                    {{ $account->code }} — {{ $account->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('account_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('messages.bank_reconciliation_statement_file') }}</label>
                                        <input type="file" class="form-control @error('statement') is-invalid @enderror"
                                            name="statement" accept=".csv,.txt" required>
                                        <div class="form-hint mt-2">
                                            {{ __('messages.bank_reconciliation_file_hint') }}
                                        </div>
                                        @error('statement')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('messages.bank_reconciliation_opening_balance') }}</label>
                                                <input type="number" step="0.01" class="form-control @error('opening_balance') is-invalid @enderror"
                                                    name="opening_balance" id="opening_balance"
                                                    value="{{ old('opening_balance', $defaultOpeningBalance > 0 ? number_format($defaultOpeningBalance, 2, '.', '') : '') }}" required>
                                                <div class="form-hint mt-2" id="openingBalanceHint">
                                                    @if ($defaultOpeningBalanceDate)
                                                        {{ __('messages.bank_reconciliation_opening_balance_synced', ['date' => \Carbon\Carbon::parse($defaultOpeningBalanceDate)->format('d M Y')]) }}
                                                    @else
                                                        {{ __('messages.bank_reconciliation_opening_balance_hint') }}
                                                    @endif
                                                </div>
                                                @error('opening_balance')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label required">{{ __('messages.bank_reconciliation_closing_balance') }}</label>
                                                <input type="number" step="0.01" class="form-control @error('closing_balance') is-invalid @enderror"
                                                    name="closing_balance" value="{{ old('closing_balance') }}" required>
                                                @error('closing_balance')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.bank_reconciliation_period_start') }}</label>
                                                <input type="date" class="form-control @error('period_start') is-invalid @enderror"
                                                    name="period_start" value="{{ old('period_start') }}">
                                                @error('period_start')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.bank_reconciliation_period_end') }}</label>
                                                <input type="date" class="form-control @error('period_end') is-invalid @enderror"
                                                    name="period_end" value="{{ old('period_end') }}">
                                                @error('period_end')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-footer text-end mt-4">
                                        <a href="{{ route('admin.bank-reconciliation.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-upload me-2"></i> {{ __('messages.bank_reconciliation_import') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const openingBalances = @json($accountOpeningBalances);
            const accountSelect = document.querySelector('select[name="account_id"]');
            const openingBalanceInput = document.getElementById('opening_balance');
            const hint = document.getElementById('openingBalanceHint');
            if (!accountSelect || !openingBalanceInput || !hint) return;

            const syncedTemplate = @json(__('messages.bank_reconciliation_opening_balance_synced', ['date' => ':date']));
            const hintText = @json(__('messages.bank_reconciliation_opening_balance_hint'));

            function applyOpeningBalance(accountId) {
                const data = openingBalances[accountId];
                if (!data) {
                    hint.textContent = hintText;
                    return;
                }
                if (openingBalanceInput.value === '' || openingBalanceInput.value === '0') {
                    openingBalanceInput.value = data.amount > 0 ? data.amount.toFixed(2) : '';
                }
                hint.textContent = data.date
                    ? syncedTemplate.replace(':date', new Date(data.date + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }))
                    : hintText;
            }

            accountSelect.addEventListener('change', function () {
                applyOpeningBalance(this.value);
            });
        });
    </script>
@endsection
