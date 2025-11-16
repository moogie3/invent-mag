@extends('admin.layouts.base')

@section('title', __('messages.trial_balance'))

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
                            <i class="ti ti-scale"></i>
                        </span>
                        {{ __('messages.trial_balance') }}
                    </h2>
                    <div class="text-muted mt-1">
                        {{ __('messages.trial_balance_summary') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body mt-4">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.accounting.trial_balance') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">{{ __('messages.as_of_date') }}</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ $endDate }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">{{ __('messages.filter') }}</button>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>{{ __('messages.code') }}</th>
                                <th>{{ __('messages.account') }}</th>
                                <th class="text-end">{{ __('messages.debit') }}</th>
                                <th class="text-end">{{ __('messages.credit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $item)
                                <tr>
                                    <td class="text-muted">{{ $item['code'] }}</td>
                                    <td>{{ __($item['name']) }}</td>
                                    <td class="text-end">
                                        {{ $item['debit'] > 0 ? \App\Helpers\CurrencyHelper::format($item['debit']) : '-' }}
                                    </td>
                                    <td class="text-end">
                                        {{ $item['credit'] > 0 ? \App\Helpers\CurrencyHelper::format($item['credit']) : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">
                                        {{ __('messages.no_accounts_with_balances_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="2" class="text-end"><strong>{{ __('messages.total') }}</strong></td>
                                <td class="text-end">
                                    <strong>{{ \App\Helpers\CurrencyHelper::format($totalDebits) }}</strong>
                                </td>
                                <td class="text-end">
                                    <strong>{{ \App\Helpers\CurrencyHelper::format($totalCredits) }}</strong>
                                </td>
                            </tr>
                            @if (round($totalDebits, 2) !== round($totalCredits, 2))
                                <tr>
                                    <td colspan="4" class="text-center text-danger">
                                        <strong>{{ __('messages.totals_do_not_match') }}</strong>
                                    </td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
