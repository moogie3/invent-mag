@extends('superadmin.layouts.base')
@section('title', 'Payment Overview')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-report-money"></i> Payments</div>
                    <h2 class="page-title">
                        Payment Overview
                        <a href="#" class="ms-3 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">Revenue summary and transaction insights.</div>
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
                            <div class="h1 mb-0 text-success">{{ App\Helpers\CurrencyHelper::formatPlatform($stats['totalRevenue']) }}</div>
                        </div>
                        <i class="ti ti-currency-dollar stat-icon text-success"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Total Orders</div>
                            <div class="h1 mb-0">{{ $stats['totalOrders'] }}</div>
                        </div>
                        <i class="ti ti-shopping-cart stat-icon"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Paid</div>
                            <div class="h1 mb-0 text-success">{{ $stats['paidOrders'] }}</div>
                        </div>
                        <i class="ti ti-circle-check stat-icon text-success"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Pending</div>
                            <div class="h1 mb-0 text-warning">{{ $stats['pendingOrders'] }}</div>
                        </div>
                        <i class="ti ti-clock stat-icon text-warning"></i>
                    </div>
                </div>
            </div>

            <div class="row row-deck row-cards mb-4">
                <div class="col-lg-8">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-chart-line me-2 text-muted"></i>Monthly Revenue (12 Months)</h3>
                        </div>
                        <div class="card-body">
                            @if($monthlyRevenue->count() > 0)
                            <div class="chart-container" style="height: 280px;">
                                <canvas id="monthlyRevenueChart"></canvas>
                            </div>
                            @else
                            <div class="text-center py-5 text-muted">
                                <i class="ti ti-chart-bar-off fs-1 mb-2 d-block"></i>
                                No revenue data yet.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-credit-card me-2 text-muted"></i>By Payment Gateway</h3>
                        </div>
                        <div class="card-body">
                            @if($revenueByGateway->count() > 0)
                            <div class="chart-container" style="height: 240px;">
                                <canvas id="gatewayChart"></canvas>
                            </div>
                            <div class="mt-3">
                                @foreach($revenueByGateway as $gateway)
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small text-capitalize">{{ $gateway->gateway }}</span>
                                    <div class="text-end">
                                        <div class="fw-semibold">{{ App\Helpers\CurrencyHelper::formatPlatform($gateway->total) }}</div>
                                        <div class="text-muted small">{{ $gateway->count }} transactions</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="text-center py-5 text-muted">
                                <i class="ti ti-credit-card-off fs-1 mb-2 d-block"></i>
                                No payment data yet.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 rounded-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-history me-2 text-muted"></i>Recent Transactions</h3>
                    <a href="{{ route('superadmin.transactions.index') }}" class="btn btn-sm btn-ghost ms-auto">View All</a>
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
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $order)
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
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No transactions yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<span id="paymentOverviewChartData"
    data-chart='{!! json_encode([
        'monthlyRevenue' => $monthlyRevenue->count() > 0 ? ['labels' => $chartMonths, 'data' => $chartRevenue] : [],
        'revenueByGateway' => $revenueByGateway->count() > 0 ? ['labels' => $gatewayLabels, 'data' => $gatewayData] : [],
    ]) !!}' class="d-none"></span>
@vite('resources/js/superadmin/payment-overview.js')
@endsection
