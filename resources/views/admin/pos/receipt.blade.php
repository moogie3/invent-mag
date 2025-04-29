@extends('admin.layouts.receiptbase')

@section('title', 'POS Receipt')

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title no-print">POS Receipt</h2>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-secondary" onclick="javascript:window.print();">
                            <i class="ti ti-printer me-1"></i> Print Receipt
                        </button>
                        <a href="{{ route('admin.pos') }}" class="btn btn-primary">
                            <i class="ti ti-shopping-cart me-1"></i> Back to POS
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-md-10 mx-auto">
                        <div class="card card-primary receipt-card">
                            <div class="card-body">
                                <div class="receipt-container">

                                    {{-- Receipt Header --}}
                                    <div class="text-center mb-3 receipt-header">
                                        <h3 class="mb-1">{{ Auth::user()->shopname ?? 'No Shop Name' }}</h3>
                                        <div class="receipt-meta">
                                            <span class="receipt-number">#{{ $sale->invoice }}</span>
                                            <span class="receipt-date">
                                                {{ optional($sale->order_date)->format('d M Y, H:i:s') ?? now()->format('d M Y, H:i:s') }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Customer Info --}}
                                    <div class="customer-section mb-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <h5 class="section-title">Customer</h5>
                                                <p class="mb-0">{{ $sale->customer->name ?? 'Walk-in Customer' }}</p>
                                                @if ($sale->customer?->phone_number)
                                                    <p class="mb-0 text-muted small">{{ $sale->customer->phone_number }}</p>
                                                @endif
                                            </div>
                                            <div class="col-md-6 text-md-end">
                                                <h5 class="section-title">Payment</h5>
                                                <p class="mb-0 text-muted small">Method: {{ $sale->payment_type }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Products Table --}}
                                    <div class="table-responsive mb-3">
                                        <table class="table table-items">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Price</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($sale->items as $item)
                                                    <tr>
                                                        <td>{{ $item->product->name }}</td>
                                                        <td class="text-center">{{ $item->quantity }}</td>
                                                        <td class="text-end">
                                                            {{ \App\Helpers\CurrencyHelper::format($item->customer_price) }}
                                                        </td>
                                                        <td class="text-end">
                                                            {{ \App\Helpers\CurrencyHelper::format($item->calculated_total) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Totals --}}
                                    <div class="totals-section">
                                        <div class="total-row">
                                            <span>Subtotal</span>
                                            <span>{{ \App\Helpers\CurrencyHelper::format($subTotal) }}</span>
                                        </div>

                                        @if ($orderDiscountAmount > 0)
                                            <div class="total-row">
                                                <span>Discount</span>
                                                <span>-{{ \App\Helpers\CurrencyHelper::format($orderDiscountAmount) }}</span>
                                            </div>
                                        @endif

                                        <div class="total-row">
                                            <span>Tax ({{ $taxRate }}%)</span>
                                            <span>{{ \App\Helpers\CurrencyHelper::format($taxAmount) }}</span>
                                        </div>

                                        <div class="total-row total-main">
                                            <span>Total</span>
                                            <span>{{ \App\Helpers\CurrencyHelper::format($grandTotal) }}</span>
                                        </div>

                                        <div class="payment-rows">
                                            <div class="total-row">
                                                <span>Amount Received</span>
                                                <span>{{ \App\Helpers\CurrencyHelper::format($amountReceived) }}</span>
                                            </div>
                                            <div class="total-row">
                                                <span>Change</span>
                                                <span>{{ \App\Helpers\CurrencyHelper::format($change) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Footer --}}
                                    <div class="text-center mt-3 receipt-footer">
                                        <p class="mb-1">Thank you for your purchase!</p>
                                        <p class="small text-muted mb-0">{{ Auth::user()->shopname ?? 'No Shop Name' }}</p>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
