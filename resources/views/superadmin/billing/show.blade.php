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
                            <h2 class="page-title mb-0">
                                Order #{{ $order->order_number }}
                                <a href="#" class="ms-2 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                                    <i class="ti ti-help-circle fs-5"></i>
                                </a>
                            </h2>
                        </div>
                    </div>
                </div>
                @if($order->status !== 'voided')
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#void-confirm-modal">
                        <i class="ti ti-ban me-2"></i>Void Order
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-check me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-triangle me-1"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
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
                                <dd class="col-7 fw-bold fs-4">{{ App\Helpers\CurrencyHelper::formatPlatform($order->amount) }}</dd>

                                <dt class="col-5">Status</dt>
                                <dd class="col-7">
                                    @if($order->status === 'paid')
                                        <span class="badge bg-success text-white fs-6">Paid</span>
                                    @elseif($order->status === 'pending')
                                        <span class="badge bg-warning text-white fs-6">Pending</span>
                                    @elseif($order->status === 'failed')
                                        <span class="badge bg-danger text-white fs-6">Failed</span>
                                    @elseif($order->status === 'voided')
                                        <span class="badge bg-secondary text-white fs-6">Voided</span>
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

                                <dt class="col-12"><hr class="my-2"></dt>

                                <dt class="col-5"><i class="ti ti-building-community me-1 text-muted"></i>Tenant</dt>
                                <dd class="col-7">{{ $order->tenant?->name ?? 'Deleted' }}</dd>

                                <dt class="col-5">Email</dt>
                                <dd class="col-7">{{ $order->tenant?->users()->first()?->email ?? 'N/A' }}</dd>

                                <dt class="col-5">Plan</dt>
                                <dd class="col-7">{{ $order->plan?->name ?? 'N/A' }}</dd>

                                <dt class="col-5">Billing Cycle</dt>
                                <dd class="col-7">{{ $order->plan?->billing_cycle ?? 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">

                    @if($order->payment_info)
                    @php
                        $rawPayment = is_array($order->payment_info) ? $order->payment_info : json_decode(json_encode($order->payment_info), true);
                        $skipKeys = ['payment_proof_raw'];
                    @endphp
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-credit-card me-2 text-muted"></i>Payment Details</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                @foreach($rawPayment as $key => $value)
                                    @if(in_array($key, $skipKeys)) @continue @endif
                                    @if(is_string($value) || is_numeric($value))
                                    <dt class="col-5 text-capitalize">{{ str_replace('_', ' ', $key) }}</dt>
                                    <dd class="col-7">{{ $value }}</dd>
                                    @elseif(is_array($value))
                                    <dt class="col-5 text-capitalize">{{ str_replace('_', ' ', $key) }}</dt>
                                    <dd class="col-7"><code>{{ json_encode($value) }}</code></dd>
                                    @endif
                                @endforeach
                            </dl>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($order->status !== 'voided')
<div class="modal modal-blur fade" id="void-confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body text-center py-4">
                <span class="avatar avatar-lg mb-3 bg-danger-lt rounded-3">
                    <i class="ti ti-alert-triangle fs-2 text-danger"></i>
                </span>
                <h4 class="fw-bold mb-2">Void Order #{{ $order->order_number }}?</h4>
                <p class="text-muted mb-0">
                    Void order for <strong>{{ App\Helpers\CurrencyHelper::formatPlatform($order->amount) }}</strong>.
                    This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('superadmin.billing.void', $order) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-ban me-1"></i>Void Order
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endSection
