@extends('superadmin.layouts.base')
@section('title', 'Billing')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-currency-dollar"></i> Configuration</div>
                    <h2 class="page-title">
                        Billing
                        <a href="#" class="ms-3 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">Subscription orders and revenue.</div>
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
                            <div class="subheader">Total Revenue</div>
                            <div class="h1 mb-0 text-success">{{ App\Helpers\CurrencyHelper::formatPlatform($stats['total_revenue']) }}</div>
                        </div>
                        <i class="ti ti-currency-dollar stat-icon text-success"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Total Orders</div>
                            <div class="h1 mb-0">{{ $stats['total_orders'] }}</div>
                        </div>
                        <i class="ti ti-shopping-cart stat-icon"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Paid</div>
                            <div class="h1 mb-0 text-success">{{ $stats['paid_orders'] }}</div>
                        </div>
                        <i class="ti ti-check stat-icon text-success"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Pending</div>
                            <div class="h1 mb-0 text-warning">{{ $stats['pending_orders'] }}</div>
                        </div>
                        <i class="ti ti-clock stat-icon text-warning"></i>
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
                                <th>Status</th>
                                <th>Date</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td><code>{{ $order->order_number }}</code></td>
                                <td>{{ $order->tenant?->name ?? 'Deleted' }}</td>
                                <td>{{ $order->plan?->name ?? 'N/A' }}</td>
                                <td class="fw-semibold">{{ App\Helpers\CurrencyHelper::formatPlatform($order->amount) }}</td>
                                <td>
                                    @if($order->status === 'paid')
                                        <span class="badge bg-success text-white">Paid</span>
                                    @elseif($order->status === 'pending')
                                        <span class="badge bg-warning text-white">Pending</span>
                                    @elseif($order->status === 'failed')
                                        <span class="badge bg-danger text-white">Failed</span>
                                    @else
                                        <span class="badge bg-secondary text-white">{{ $order->status }}</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $order->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('superadmin.billing.show', $order) }}" class="btn btn-icon-only btn-ghost" title="View order details">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No billing orders found.</td>
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