@extends('admin.layouts.base')

@section('title', __('messages.general_ledger'))

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none mt-4">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        {{ __('messages.accounting') }}
                    </div>
                    <h2 class="page-title">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-book"></i>
                        </span>
                        {{ __('messages.general_ledger') }}
                    </h2>
                    <div class="text-muted mt-1">
                        {{ __('messages.general_ledger_summary') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body mt-4">
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.accounting.ledger') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="account_id" class="form-label">{{ __('messages.account') }}</label>
                            <select name="account_id" id="account_id" class="form-select" required>
                                <option value="">{{ __('messages.select_an_account') }}</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @if ($selectedAccount && $selectedAccount->id == $account->id) selected @endif>
                                        {{ __($account->name) }} ({{ $account->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">{{ __('messages.start_date') }}</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">{{ __('messages.end_date') }}</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ $endDate }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">{{ __('messages.filter') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            @if ($selectedAccount)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.ledger_for') }} {{ __($selectedAccount->name) }}</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.date') }}</th>
                                    <th>{{ __('messages.description') }}</th>
                                    <th class="text-end">{{ __('messages.debit') }}</th>
                                    <th class="text-end">{{ __('messages.credit') }}</th>
                                    <th class="text-end">{{ __('messages.balance') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-end">
                                        <strong>{{ __('messages.opening_balance') }}</strong></td>
                                    <td class="text-end">
                                        <strong>{{ \App\Helpers\CurrencyHelper::format($openingBalance) }}</strong>
                                    </td>
                                </tr>
                                @php $balance = $openingBalance; @endphp
                                @forelse($transactions as $transaction)
                                    @php
                                        if ($transaction->type == 'debit') {
                                            $balance += $transaction->amount;
                                        } else {
                                            $balance -= $transaction->amount;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $transaction->journalEntry->date->format('d M Y') }}</td>
                                        <td>
                                            {{ $transaction->journalEntry->description }}
                                            @if ($transaction->journalEntry->sourceable && method_exists($transaction->journalEntry->sourceable, 'path'))
                                                <a href="{{ $transaction->journalEntry->sourceable->path() }}"
                                                    target="_blank" class="ms-2">({{ __('messages.view_source') }})</a>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($transaction->type == 'debit')
                                                {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($transaction->type == 'credit')
                                                {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
                                            @endif
                                        </td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($balance) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            {{ __('messages.no_transactions_found_period') }}</td>
                                    </tr>
                                @endforelse
                                <tr>
                                    <td colspan="4" class="text-end">
                                        <strong>{{ __('messages.closing_balance') }}</strong></td>
                                    <td class="text-end">
                                        <strong>{{ \App\Helpers\CurrencyHelper::format($closingBalance) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex align-items-center">
                        {{ $transactions->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="empty">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-search"
                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                            <path d="M12 21h-5a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v4"></path>
                            <path d="M16.5 17.5m-2.5 0a2.5 2.5 0 1 0 5 0a2.5 2.5 0 1 0 -5 0"></path>
                            <path d="M18.5 19.5l2.5 2.5"></path>
                        </svg>
                    </div>
                    <p class="empty-title">{{ __('messages.select_an_account') }}</p>
                    <p class="empty-subtitle text-muted">
                        {{ __('messages.select_account_to_view_ledger') }}
                    </p>
                </div>
            @endif
        </div>
    </div>
@endsection
