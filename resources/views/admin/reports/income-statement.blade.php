@extends('admin.layouts.base')

@section('title', __('messages.income_statement'))

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
                                <i class="ti ti-file-analytics"></i>
                            </span>
                            {{ __('messages.income_statement') }}
                        </h2>
                        <div class="text-muted mt-1">
                            {{ __('messages.income_statement_summary') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-body">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.report_period') }}:
                            {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} -
                            {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</h3>
                        <div class="card-actions">
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="ti ti-printer fs-4 me-2"></i> {{ __('messages.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"
                                            onclick="exportIncomeStatement('pdf')">Export as PDF</a></li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="exportIncomeStatement('csv')">Export as CSV</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.reports.income-statement') }}" method="GET" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('messages.start_date') }}</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control"
                                        value="{{ $startDate }}">
                                </div>
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
                            <h4 class="mb-3">{{ __('messages.revenue') }}</h4>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.account') }}</th>
                                        <th class="text-end">{{ __('messages.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($revenueAccounts as $account)
                                        <tr>
                                            <td>{{ __($account->name) }}</td>
                                            <td class="text-end">
                                                {{ \App\Helpers\CurrencyHelper::format(
                                                    $account->transactions->sum(function ($transaction) {
                                                        return $transaction->type === 'credit' ? $transaction->amount : -$transaction->amount;
                                                    }),
                                                ) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="fw-bold">
                                        <td>{{ __('messages.total_revenue') }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalRevenue) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="report-section mb-4">
                            <h4 class="mb-3">{{ __('messages.expenses') }}</h4>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.account') }}</th>
                                        <th class="text-end">{{ __('messages.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($expenseAccounts as $account)
                                        <tr>
                                            <td>{{ __($account->name) }}</td>
                                            <td class="text-end">
                                                {{ \App\Helpers\CurrencyHelper::format(
                                                    $account->transactions->sum(function ($transaction) {
                                                        return $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
                                                    }),
                                                ) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="fw-bold">
                                        <td>{{ __('messages.total_expenses') }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($totalExpenses) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="report-section net-income mt-5 border-top pt-3">
                            <h3 class="d-flex justify-content-between">
                                <span>{{ __('messages.net_income') }}</span>
                                <span class="text-end">{{ \App\Helpers\CurrencyHelper::format($netIncome) }}</span>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
