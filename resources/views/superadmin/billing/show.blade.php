@extends('superadmin.layouts.base')
@section('title', 'Order Details')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('superadmin.billing.index') }}" class="btn btn-ghost btn-sm me-2">
                            <i class="ti ti-arrow-left"></i>
                        </a>
                        <div>
                            <div class="page-pretitle"><i class="ti ti-currency-dollar"></i> Configuration / Billing</div>
                            <h2 class="page-title mb-0">Order #{{ $order->order_number }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-lg-6">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-info-circle me-2 text-muted"></i>Order Information</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-5">Order Number</dt>
                                <dd class="col-7"><code>{{ $order->order_number }}</code></dd>

                                <dt class="col-5">Amount</dt>
                                <dd class="col-7 fw-bold fs-4">${{ number_format($order->amount, 2) }}</dd>

                                <dt class="col-5">Status</dt>
                                <dd class="col-7">
                                    @if($order->status === 'paid')
                                        <span class="badge bg-success text-white fs-6">Paid</span>
                                    @elseif($order->status === 'pending')
                                        <span class="badge bg-warning text-white fs-6">Pending</span>
                                    @elseif($order->status === 'failed')
                                        <span class="badge bg-danger text-white fs-6">Failed</span>
                                    @else
                                        <span class="badge bg-secondary text-white fs-6">{{ $order->status }}</span>
                                    @endif
                                </dd>

                                <dt class="col-5">Change Type</dt>
                                <dd class="col-7">{{ $order->change_type ?? 'N/A' }}</dd>

                                <dt class="col-5">Paid At</dt>
                                <dd class="col-7">{{ $order->paid_at?->format('M d, Y H:i') ?? 'N/A' }}</dd>

                                <dt class="col-5">Created</dt>
                                <dd class="col-7">{{ $order->created_at->format('M d, Y H:i') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-users me-2 text-muted"></i>Related Records</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-5">Tenant</dt>
                                <dd class="col-7">{{ $order->tenant?->name ?? 'Deleted' }}</dd>

                                <dt class="col-5">Tenant Email</dt>
                                <dd class="col-7">{{ $order->tenant?->email ?? 'N/A' }}</dd>

                                <dt class="col-5">Plan</dt>
                                <dd class="col-7">{{ $order->plan?->name ?? 'N/A' }}</dd>

                                <dt class="col-5">Billing Cycle</dt>
                                <dd class="col-7">{{ $order->plan?->billing_cycle ?? 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>

                    @if($order->payment_info)
                    <div class="card shadow border-0 rounded-4 mt-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-credit-card me-2 text-muted"></i>Payment Info</h3>
                        </div>
                        <div class="card-body">
                            <pre class="m-0" style="max-height: 200px; overflow: auto;">{{ json_encode($order->payment_info, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endSection