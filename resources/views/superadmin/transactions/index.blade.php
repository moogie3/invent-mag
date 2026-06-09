@extends('superadmin.layouts.base')
@section('title', 'Transactions')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-arrows-exchange"></i> Payments</div>
                    <h2 class="page-title">Transactions</h2>
                    <div class="text-muted mt-1">All subscription payment transactions.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Total</div>
                            <div class="h1 mb-0">{{ $stats['total'] }}</div>
                        </div>
                        <i class="ti ti-shopping-cart stat-icon"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Paid</div>
                            <div class="h1 mb-0 text-success">{{ $stats['paid'] }}</div>
                        </div>
                        <i class="ti ti-circle-check stat-icon text-success"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Pending</div>
                            <div class="h1 mb-0 text-warning">{{ $stats['pending'] }}</div>
                        </div>
                        <i class="ti ti-clock stat-icon text-warning"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Failed</div>
                            <div class="h1 mb-0 text-danger">{{ $stats['failed'] }}</div>
                        </div>
                        <i class="ti ti-x stat-icon text-danger"></i>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 rounded-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-filter me-2 text-muted"></i>Filter Transactions</h3>
                </div>
                <div class="card-body border-bottom py-3">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                                <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                                <option value="canceled" @selected(request('status') === 'canceled')>Canceled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Order # or Tenant..." value="{{ request('search') }}">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="ti ti-filter me-1"></i>Apply Filters
                            </button>
                            <a href="{{ route('superadmin.transactions.index') }}" class="btn btn-ghost">
                                <i class="ti ti-refresh me-1"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Tenant</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td><code>{{ $order->order_number }}</code></td>
                                <td>
                                    <a href="{{ route('superadmin.tenants.show', $order->tenant) }}" class="text-reset">{{ $order->tenant?->name ?? 'Deleted' }}</a>
                                </td>
                                <td>{{ $order->plan?->name ?? 'N/A' }}</td>
                                <td class="fw-semibold">${{ number_format($order->amount, 2) }}</td>
                                <td>
                                    @if($order->status === 'paid')
                                        <span class="badge bg-success text-white">Paid</span>
                                    @elseif($order->status === 'pending')
                                        <span class="badge bg-warning text-white">Pending</span>
                                    @elseif($order->status === 'failed')
                                        <span class="badge bg-danger text-white">Failed</span>
                                    @elseif($order->status === 'expired')
                                        <span class="badge bg-secondary text-white">Expired</span>
                                    @else
                                        <span class="badge bg-dark text-white">{{ $order->status }}</span>
                                    @endif
                                </td>
                                <td><span class="text-muted small text-capitalize">{{ $order->change_type ?? 'new' }}</span></td>
                                <td class="text-muted">{{ $order->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No transactions found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($orders->hasPages())
                <div class="card-footer d-flex justify-content-center border-top">
                    {{ $orders->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endSection
