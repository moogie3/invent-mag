@extends('admin.layouts.base')

@section('title', __('messages.balance_sheet'))

@section('content')
    <div class="page-wrapper">
        <div class="{{ $containerClass ?? "container-xl" }}">
            <div class="page-header d-print-none">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            {{ __('messages.reports') }}
                        </div>
                        <h2 class="page-title">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-file-text"></i>
                            </span>
                            {{ __('messages.balance_sheet') }}
                        </h2>
                        <div class="text-muted mt-1">
                            {{ __('messages.balance_sheet_summary') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-body">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.as_of') }}:
                            {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</h3>
                        <div class="card-actions">
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="ti ti-printer fs-4 me-2"></i> {{ __('messages.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="exportBalanceSheet('pdf')">Export
                                            as PDF</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportBalanceSheet('csv')">Export
                                            as CSV</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="balance-sheet-filter-form" action="{{ route('admin.reports.balance-sheet') }}"
                            method="GET" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('messages.end_date') }}</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control"
                                        value="{{ $endDate }}">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit"
                                        class="btn btn-primary">{{ __('messages.generate_report') }}</button>
                                </div>
                            </div>
                        </form>

                        <div class="report-section mb-4">
                            <h4 class="mb-3">{{ __('messages.assets') }}</h4>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.account') }}</th>
                                        <th class="text-end">{{ __('messages.balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($assets as $account)
                                        <tr>
                                            <td>{{ __($account->name) }}</td>
                                            <td class="text-end">
                                                {{ \App\Helpers\CurrencyHelper::format($account->calculated_balance) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="fw-bold">
                                        <td>{{ __('messages.total_assets') }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalAssets) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="report-section mb-4">
                            <h4 class="mb-3">{{ __('messages.liabilities') }}</h4>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.account') }}</th>
                                        <th class="text-end">{{ __('messages.balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($liabilities as $account)
                                        <tr>
                                            <td>{{ __($account->name) }}</td>
                                            <td class="text-end">
                                                {{ \App\Helpers\CurrencyHelper::format($account->calculated_balance) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="fw-bold">
                                        <td>{{ __('messages.total_liabilities') }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalLiabilities) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="report-section mb-4">
                            <h4 class="mb-3">{{ __('messages.equity') }}</h4>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.account') }}</th>
                                        <th class="text-end">{{ __('messages.balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($equity as $account)
                                        <tr>
                                            <td>{{ __($account->name) }}</td>
                                            <td class="text-end">
                                                {{ \App\Helpers\CurrencyHelper::format($account->calculated_balance) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="fw-bold">
                                        <td>{{ __('messages.total_equity') }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalEquity) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="report-section accounting-equation mt-5 border-top pt-3">
                            <h3 class="d-flex justify-content-between">
                                <span>{{ __('messages.total_liabilities_and_equity') }}</span>
                                <span
                                    class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalLiabilities + $totalEquity) }}</span>
                            </h3>
                            <h3 class="d-flex justify-content-between text-success">
                                <span>{{ __('messages.accounting_equation_balanced') }}</span>
                                <span
                                    class="text-end">{{ $equation_balanced ? __('messages.yes') : __('messages.no') }}</span>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
