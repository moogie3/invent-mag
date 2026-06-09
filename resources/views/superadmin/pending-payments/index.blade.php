@extends('superadmin.layouts.base')
@section('title', 'Pending Payments')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-clock"></i> Payments</div>
                    <h2 class="page-title">Pending Payments</h2>
                    <div class="text-muted mt-1">Review and approve pending subscription payments.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards mb-4">
                <div class="col-sm-6">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Pending Payments</div>
                            <div class="h1 mb-0 text-warning">{{ $stats['total_pending'] }}</div>
                        </div>
                        <i class="ti ti-clock stat-icon text-warning"></i>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Total Pending Amount</div>
                            <div class="h1 mb-0 text-warning">${{ number_format($stats['total_amount'], 2) }}</div>
                        </div>
                        <i class="ti ti-currency-dollar stat-icon text-warning"></i>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 rounded-4">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Tenant</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th class="w-1">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingOrders as $order)
                            <tr>
                                <td><code>{{ $order->order_number }}</code></td>
                                <td>
                                    <a href="{{ route('superadmin.tenants.show', $order->tenant) }}" class="text-reset">{{ $order->tenant?->name ?? 'Deleted' }}</a>
                                </td>
                                <td>{{ $order->plan?->name ?? 'N/A' }}</td>
                                <td class="fw-semibold">${{ number_format($order->amount, 2) }}</td>
                                <td class="text-muted">{{ $order->created_at->format('M d, Y H:i') }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <form method="POST" action="{{ route('superadmin.pending-payments.approve', $order) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this payment? The tenant plan will be activated.')">
                                                <i class="ti ti-check me-1"></i>Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('superadmin.pending-payments.reject', $order) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this payment? It will be marked as canceled.')">
                                                <i class="ti ti-x me-1"></i>Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="ti ti-circle-check text-success fs-1 mb-2 d-block"></i>
                                    No pending payments. All caught up!
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($pendingOrders->hasPages())
                <div class="card-footer d-flex justify-content-center border-top">
                    {{ $pendingOrders->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endSection
