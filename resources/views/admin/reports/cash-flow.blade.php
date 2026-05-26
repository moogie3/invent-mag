@extends('admin.layouts.base')

@section('title', __('messages.cash_flow_statement'))

@section('content')
    <div class="page-wrapper">
        <div class="{{ $containerClass ?? "container-xl" }}">
            <div class="page-header d-print-none">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            {{ __('messages.reports') }}
                        </div>
                        <h2 class="page-title d-flex align-items-center">
                            <i class="ti ti-currency-dollar me-2"></i>{{ __('messages.cash_flow_statement') }}
                            <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                                <i class="ti ti-help-circle fs-3"></i>
                            </a>
                        </h2>
                        <div class="text-muted mt-1">
                            {{ __('messages.cash_flow_statement_summary') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-body">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.report_period') }}:
                            {{ \Carbon\Carbon::parse($start_date)->format('M d, Y') }} -
                            {{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}</h3>
                        <div class="card-actions">
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="ti ti-printer fs-4 me-2"></i> {{ __('messages.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"
                                            data-onclick="exportCashFlow('pdf')">Export as PDF</a></li>
                                    <li><a class="dropdown-item" href="#"
                                            data-onclick="exportCashFlow('csv')">Export as CSV</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.reports.cash-flow') }}" method="GET" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('messages.start_date') }}</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control"
                                        value="{{ $start_date }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('messages.end_date') }}</label>
                                        <input type="date" name="end_date" id="end_date" class="form-control"
                                            value="{{ $end_date }}">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit"
                                        class="btn btn-primary">{{ __('messages.generate_report') }}</button>
                                </div>
                            </div>
                        </form>

                        {{-- Operating Activities --}}
                        <div class="report-section mb-4">
                            <h4 class="mb-3">{{ __('messages.operating_activities') }}</h4>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.account') }}</th>
                                        <th class="text-end">{{ __('messages.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="fw-bold">
                                        <td>{{ __('messages.net_income') }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($net_income) }}</td>
                                    </tr>
                                    @if(count($non_cash_items) > 0)
                                        <tr class="table-secondary">
                                            <td colspan="2" class="fw-bold">{{ __('messages.non_cash_items') }}</td>
                                        </tr>
                                        @foreach($non_cash_items as $item)
                                            <tr>
                                                <td class="ps-4">{{ __($item['account_name']) }}</td>
                                                <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item['amount']) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="fw-bold">
                                            <td>{{ __('messages.total_non_cash') }}</td>
                                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($non_cash_total) }}</td>
                                        </tr>
                                    @endif
                                    @if(count($working_capital_changes) > 0)
                                        <tr class="table-secondary">
                                            <td colspan="2" class="fw-bold">{{ __('messages.changes_in_working_capital') }}</td>
                                        </tr>
                                        @foreach($working_capital_changes as $item)
                                            <tr>
                                                <td class="ps-4">{{ __($item['account_name']) }}</td>
                                                <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item['amount']) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="fw-bold">
                                            <td>{{ __('messages.total_working_capital') }}</td>
                                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($working_capital_total) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="fw-bold border-top">
                                        <td>{{ __('messages.net_cash_operating') }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($operating_cash_flow) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Investing Activities --}}
                        <div class="report-section mb-4">
                            <h4 class="mb-3">{{ __('messages.investing_activities') }}</h4>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.account') }}</th>
                                        <th class="text-end">{{ __('messages.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($investing_activities as $item)
                                        <tr>
                                            <td>{{ __($item['account_name']) }}</td>
                                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item['amount']) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted text-center">{{ __('messages.no_investing_activities') }}</td>
                                        </tr>
                                    @endforelse
                                    <tr class="fw-bold border-top">
                                        <td>{{ __('messages.net_cash_investing') }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($investing_cash_flow) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Financing Activities --}}
                        <div class="report-section mb-4">
                            <h4 class="mb-3">{{ __('messages.financing_activities') }}</h4>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.account') }}</th>
                                        <th class="text-end">{{ __('messages.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($financing_activities as $item)
                                        <tr>
                                            <td>{{ __($item['account_name']) }}</td>
                                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item['amount']) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted text-center">{{ __('messages.no_financing_activities') }}</td>
                                        </tr>
                                    @endforelse
                                    <tr class="fw-bold border-top">
                                        <td>{{ __('messages.net_cash_financing') }}</td>
                                        <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($financing_cash_flow) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Summary --}}
                        <div class="report-section net-income mt-5 border-top pt-3">
                            <h3 class="d-flex justify-content-between">
                                <span>{{ __('messages.net_increase_decrease_cash') }}</span>
                                <span class="text-end {{ $net_change_in_cash >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ \App\Helpers\CurrencyHelper::format($net_change_in_cash) }}
                                </span>
                            </h3>
                            <div class="d-flex justify-content-between mt-2">
                                <span class="text-muted">{{ __('messages.cash_beginning_period') }}</span>
                                <span>{{ \App\Helpers\CurrencyHelper::format($opening_cash_balance) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-1 fw-bold">
                                <span>{{ __('messages.cash_end_period') }}</span>
                                <span>{{ \App\Helpers\CurrencyHelper::format($closing_cash_balance) }}</span>
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
        function exportCashFlow(format) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('admin.reports.cash-flow.export') }}';
            form.innerHTML = `
                @csrf
                <input type="hidden" name="export_option" value="${format}">
                <input type="hidden" name="start_date" value="{{ $start_date }}">
                <input type="hidden" name="end_date" value="{{ $end_date }}">
            `;
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>
@endsection
