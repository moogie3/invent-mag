@extends('admin.layouts.base')

@section('title', __('messages.aged_payables_report'))

@section('content')
    <div class="page-wrapper">
        <div class="container-xl">
            <div class="page-header d-print-none">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            {{ __('messages.reports') }}
                        </div>
                        <h2 class="page-title">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-receipt-tax"></i>
                            </span>
                            {{ __('messages.aged_payables_report') }}
                        </h2>
                        <div class="text-muted mt-1">
                            {{ __('messages.aged_payables_report_summary') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-body">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.unpaid_bills_by_age') }}</h3>
                        <div class="card-actions">
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="ti ti-printer fs-4 me-2"></i> {{ __('messages.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"
                                            onclick="exportAgedPayables('pdf')">Export as PDF</a></li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="exportAgedPayables('csv')">Export as CSV</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach ($aging as $bucket => $bills)
                            @if ($bills->count() > 0)
                                <h4 class="mb-3">{{ __('messages.bucket_' . $bucket) }}</h4>
                                <div class="table-responsive mb-4">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('messages.supplier') }}</th>
                                                <th>{{ __('messages.invoice_no') }}</th>
                                                <th>{{ __('messages.due_date') }}</th>
                                                <th class="text-end">{{ __('messages.days_overdue') }}</th>
                                                <th class="text-end">{{ __('messages.amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($bills as $bill)
                                                <tr>
                                                    <td>{{ $bill->supplier->name ?? __('messages.unknown_supplier') }}</td>
                                                    <td>{{ $bill->invoice }}</td>
                                                    <td>{{ \App\Helpers\CurrencyHelper::formatDate($bill->due_date) }}</td>
                                                    <td class="text-end">{{ $bill->days_overdue }}</td>
                                                    <td class="text-end">
                                                        {{ \App\Helpers\CurrencyHelper::format($bill->total) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold">
                                                <td colspan="4" class="text-end">
                                                    {{ __('messages.total_for_bucket', ['bucket' => $bucket]) }}</td>
                                                <td class="text-end">
                                                    {{ \App\Helpers\CurrencyHelper::format($bills->sum('total')) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
