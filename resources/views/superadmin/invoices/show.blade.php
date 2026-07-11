@extends('superadmin.layouts.base')
@section('title', 'Invoice #' . $order->order_number)
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('superadmin.invoices.index') }}" class="btn btn-ghost btn-sm me-2">
                            <i class="ti ti-arrow-left"></i>
                        </a>
                        <div>
                            <div class="page-pretitle"><i class="ti ti-file-invoice"></i> Payments / Invoices</div>
                            <h2 class="page-title mb-0">
                                Invoice #{{ $order->order_number }}
                                <a href="#" class="ms-2 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                                    <i class="ti ti-help-circle fs-5"></i>
                                </a>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" id="btn-print-invoice">
                        <i class="ti ti-printer me-1"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-5">
                    <div class="row mb-4">
                        <div class="col-6">
                            <h3 class="mb-1">Invent-MAG</h3>
                            <p class="text-muted mb-0">Subscription Invoice</p>
                        </div>
                        <div class="col-6 text-end">
                            <h4 class="mb-1">Invoice #{{ $order->order_number }}</h4>
                            <p class="text-muted mb-0">Date: {{ $order->paid_at?->format('M d, Y') ?? $order->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-4">
                        <div class="col-6">
                            <strong>Bill To:</strong>
                            <p class="mb-0">{{ $order->tenant?->name ?? 'N/A' }}</p>
                            <p class="mb-0 text-muted">{{ $order->tenant?->users()->first()?->email ?? '' }}</p>
                        </div>
                        <div class="col-6 text-end">
                            <strong>Plan:</strong>
                            <p class="mb-0">{{ $order->plan?->name ?? 'N/A' }}</p>
                            <p class="mb-0 text-muted text-capitalize">{{ $order->plan?->billing_cycle ?? '' }} subscription</p>
                        </div>
                    </div>
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    {{ $order->plan?->name ?? 'Subscription' }} - {{ $order->plan?->billing_cycle ?? 'N/A' }}
                                    @if($order->change_type)
                                    <br><small class="text-muted text-capitalize">{{ $order->change_type }}</small>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">{{ App\Helpers\CurrencyHelper::formatPlatform($order->amount) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="text-end">Total</th>
                                <th class="text-end fs-4">{{ App\Helpers\CurrencyHelper::formatPlatform($order->amount) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="row mt-3">
                        <div class="col-6">
                            <strong>Payment Status:</strong>
                            <span class="badge bg-success text-white ms-2">Paid</span>
                        </div>
                        <div class="col-6 text-end text-muted small">
                            @if($order->payment_info && isset($order->payment_info->payment_type))
                            Payment via {{ ucfirst(str_replace('_', ' ', $order->payment_info->payment_type)) }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ $cspNonce }}">
document.getElementById('btn-print-invoice')?.addEventListener('click', function() {
    window.print();
});
</script>
@endpush
@endsection
