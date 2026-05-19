@extends('admin.layouts.base')

@section('title', __('messages.opening_balance'))

@section('content')
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="page-header d-print-none mt-4">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        {{ __('messages.accounting') }}
                    </div>
                    <h2 class="page-title d-flex align-items-center">
                        <i class="ti ti-scale me-2"></i>{{ __('messages.opening_balance_setup') }}
                        <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">
                        {{ __('messages.opening_balance_description') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body mt-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><i class="ti ti-check icon"></i></div>
                        <div>{{ session('success') }}</div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><i class="ti ti-alert-circle icon"></i></div>
                        <div>{{ session('error') }}</div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            @endif

            <form id="openingBalanceForm" action="{{ route('admin.accounting.opening-balance.store') }}" method="POST">
                @csrf
                
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.opening_balance_date') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="balance_date" class="form-label">{{ __('messages.balance_date') }}</label>
                                <input type="date" name="balance_date" id="balance_date" 
                                    class="form-control" 
                                    value="{{ $balanceDate }}" 
                                    max="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-8">
                                <label for="notes" class="form-label">{{ __('messages.notes') }} ({{ __('messages.optional') }})</label>
                                <input type="text" name="notes" id="notes" 
                                    class="form-control" 
                                    placeholder="{{ __('messages.opening_balance_notes_placeholder') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.account_balances') }}</h3>
                        <div class="card-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-onclick="resetAllBalances()">
                                <i class="ti ti-refresh me-1"></i>{{ __('messages.reset_all') }}
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table" id="balancesTable">
                            <thead>
                                <tr>
                                    <th class="w-25">{{ __('messages.account') }}</th>
                                    <th class="w-15">{{ __('messages.type') }}</th>
                                    <th class="w-20">{{ __('messages.debit') }}</th>
                                    <th class="w-20">{{ __('messages.credit') }}</th>
                                    <th class="w-20">{{ __('messages.balance') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $groupedAccounts = $accounts->groupBy('type');
                                @endphp
                                @foreach($groupedAccounts as $type => $typeAccounts)
                                    <tr class="table-secondary">
                                        <td colspan="5" class="fw-bold">
                                            {{ ucfirst($type) }} ({{ $typeAccounts->count() }} {{ __('messages.accounts') }})
                                        </td>
                                    </tr>
                                    @foreach($typeAccounts as $account)
                                        @php
                                            $existing = $existingBalances[$account->id] ?? null;
                                            $debit = $existing ? $existing['debit_amount'] : 0;
                                            $credit = $existing ? $existing['credit_amount'] : 0;
                                            $balance = $existing ? $existing['balance'] : 0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2">{{ $account->code }}</span>
                                                    <span>{{ __($account->name) }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <select name="balances[{{ $account->id }}][type]" 
                                                    class="form-select form-select-sm balance-type"
                                                    data-account-id="{{ $account->id }}"
                                                    data-onchange="updateBalance({{ $account->id }})">
                                                    <option value="debit" {{ $debit > 0 ? 'selected' : '' }}>{{ __('messages.debit') }}</option>
                                                    <option value="credit" {{ $credit > 0 ? 'selected' : '' }}>{{ __('messages.credit') }}</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                    name="balances[{{ $account->id }}][debit]" 
                                                    class="form-control form-control-sm debit-input" 
                                                    value="{{ $debit > 0 ? $debit : '' }}"
                                                    placeholder="0.00"
                                                    step="0.01"
                                                    min="0"
                                                    data-account-id="{{ $account->id }}"
                                                    oninput="syncAmount({{ $account->id }}, 'debit')">
                                            </td>
                                            <td>
                                                <input type="number" 
                                                    name="balances[{{ $account->id }}][credit]" 
                                                    class="form-control form-control-sm credit-input"
                                                    value="{{ $credit > 0 ? $credit : '' }}"
                                                    placeholder="0.00"
                                                    step="0.01"
                                                    min="0"
                                                    data-account-id="{{ $account->id }}"
                                                    oninput="syncAmount({{ $account->id }}, 'credit')">
                                            </td>
                                            <td class="balance-display fw-bold" id="balance_{{ $account->id }}">
                                                {{ \App\Helpers\CurrencyHelper::format($balance) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="2" class="text-end fw-bold">{{ __('messages.totals') }}</td>
                                    <td class="text-end fw-bold" id="totalDebit">
                                        {{ \App\Helpers\CurrencyHelper::format($summary['totalDebit'] ?? 0) }}
                                    </td>
                                    <td class="text-end fw-bold" id="totalCredit">
                                        {{ \App\Helpers\CurrencyHelper::format($summary['totalCredit'] ?? 0) }}
                                    </td>
                                    <td class="text-end fw-bold" id="totalDifference">
                                        @php
                                            $diff = ($summary['totalDebit'] ?? 0) - ($summary['totalCredit'] ?? 0);
                                            $isBalanced = abs($diff) < 0.01;
                                        @endphp
                                        <span class="{{ $isBalanced ? 'text-success' : 'text-danger' }}">
                                            {{ \App\Helpers\CurrencyHelper::format($diff) }}
                                        </span>
                                        @if($isBalanced)
                                            <i class="ti ti-check text-success ms-1"></i>
                                        @else
                                            <i class="ti ti-alert-triangle text-danger ms-1"></i>
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <div class="d-flex">
                                        <div>
                                            <i class="ti ti-info-circle me-2"></i>
                                        </div>
                                        <div>
                                            <h4 class="alert-title">{{ __('messages.information') }}</h4>
                                            <div class="text-muted">
                                                {{ __('messages.opening_balance_info') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-danger" 
                                        data-onclick="deleteOpeningBalances()"
                                        {{ $summary['count'] == 0 ? 'disabled' : '' }}>
                                        <i class="ti ti-trash me-1"></i>{{ __('messages.delete_opening_balances') }}
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="saveBtn">
                                        <i class="ti ti-device-floppy me-1"></i>{{ __('messages.save_opening_balances') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if($summary['count'] > 0)
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.current_opening_balances') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-label">{{ __('messages.total_debit') }}</div>
                                    <div class="stat-value text-primary">{{ \App\Helpers\CurrencyHelper::format($summary['totalDebit']) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-label">{{ __('messages.total_credit') }}</div>
                                    <div class="stat-value text-info">{{ \App\Helpers\CurrencyHelper::format($summary['totalCredit']) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-label">{{ __('messages.difference') }}</div>
                                    <div class="stat-value {{ $summary['isBalanced'] ? 'text-success' : 'text-danger' }}">
                                        {{ \App\Helpers\CurrencyHelper::format($summary['difference']) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-label">{{ __('messages.accounts_with_balance') }}</div>
                                    <div class="stat-value">{{ $summary['count'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <form id="deleteForm" action="{{ route('admin.accounting.opening-balance.destroy') }}" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
        <input type="hidden" name="balance_date" value="{{ $balanceDate }}">
    </form>

@endsection
