@extends('superadmin.layouts.base')
@section('title', 'Invoices')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-file-invoice"></i> Payments</div>
                    <h2 class="page-title">Invoices</h2>
                    <div class="text-muted mt-1">All paid subscription invoices.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible mb-3" role="alert">
                <i class="ti ti-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            <div class="row row-deck row-cards mb-4">
                <div class="col-sm-6">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Total Invoices</div>
                            <div class="h1 mb-0">{{ $stats['total_invoices'] }}</div>
                        </div>
                        <i class="ti ti-file-invoice stat-icon"></i>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Total Revenue</div>
                            <div class="h1 mb-0 text-success">${{ number_format($stats['total_revenue'], 2) }}</div>
                        </div>
                        <i class="ti ti-currency-dollar stat-icon text-success"></i>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 rounded-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-search me-2 text-muted"></i>Search Invoices</h3>
                </div>
                <div class="card-body border-bottom py-3">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Search by Order # or Tenant</label>
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="ti ti-search me-1"></i>Search
                            </button>
                            <a href="{{ route('superadmin.invoices.index') }}" class="btn btn-ghost">
                                <i class="ti ti-refresh me-1"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Tenant</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Paid At</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $order)
                            <tr>
                                <td><code>{{ $order->order_number }}</code></td>
                                <td>{{ $order->tenant?->name ?? 'Deleted' }}</td>
                                <td>{{ $order->plan?->name ?? 'N/A' }}</td>
                                <td class="fw-semibold">${{ number_format($order->amount, 2) }}</td>
                                <td class="text-muted">{{ $order->paid_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('superadmin.invoices.show', $order) }}" class="btn btn-icon-only btn-ghost" title="View invoice">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No paid invoices found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($invoices->hasPages())
                <div class="card-footer d-flex justify-content-center border-top">
                    {{ $invoices->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endSection
