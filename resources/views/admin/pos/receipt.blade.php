@extends('admin.layouts.receiptbase')

@section('title', 'POS Receipt')

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">{{ __('messages.overview') }}</div>
                        <h2 class="page-title no-print"><i class="ti ti-receipt me-2"></i>{{ __('messages.pos_receipt_title') }}</h2>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-secondary" onclick="javascript:window.print();">
                            <i class="ti ti-printer me-1"></i> {{ __('messages.print_receipt') }}
                        </button>
                        <a href="{{ route('admin.pos') }}" class="btn btn-primary">
                            <i class="ti ti-shopping-cart me-1"></i> {{ __('messages.back_to_pos') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="row row-deck row-cards">
                    <div class="col-md-10 mx-auto">
                        <div class="card card-primary receipt-card receipt-preview">
                            <div class="card-body">
                                <div class="receipt-container">

                                    {{-- Receipt Header --}}
                                    <div class="text-center mb-3 receipt-header">
                                        <h3 class="mb-1">{{ Auth::user()->shopname ?? __('messages.no_shop_name') }}</h3>
                                        <div class="receipt-meta">
                                            <div class="receipt-number">{{ __('messages.invoice_colon') }} #{{ $sale->invoice }}</div>
                                            <div class="receipt-date">
                                                {{ optional($sale->order_date)->format('d M Y, H:i:s') ?? now()->format('d M Y, H:i:s') }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Customer Info --}}
                                    <div class="customer-section mb-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <h5 class="section-title">{{ __('messages.customer') }}</h5>
                                                <p class="mb-0">{{ $sale->customer->name ?? __('messages.walk_in_customer') }}</p>
                                                @if ($sale->customer?->phone_number)
                                                    <p class="mb-0 text-muted small">{{ $sale->customer->phone_number }}</p>
                                                @endif
                                            </div>
                                            <div class="col-md-6">
                                                <h5 class="section-title">{{ __('messages.payment') }}</h5>
                                                <p class="mb-0 text-muted small">{{ $sale->payment_type }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Products Table --}}
                                    <div class="table-responsive mb-3">
                                        <table class="table table-items">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('messages.item') }}</th>
                                                    <th class="text-center">{{ __('messages.qty') }}</th>
                                                    <th class="text-end">{{ __('messages.price') }}</th>
                                                    <th class="text-end">{{ __('messages.total') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($sale->salesItems as $item)
                                                    <tr>
                                                        <td>{{ $item->product->name }}</td>
                                                        <td class="text-center">{{ $item->quantity }}</td>
                                                        <td class="text-end">
                                                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->customer_price) }}
                                                        </td>
                                                        <td class="text-end">
                                                            {{ \App\Helpers\CurrencyHelper::formatWithPosition($item->calculated_total) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Totals --}}
                                    <div class="totals-section">
                                        <div class="total-row">
                                            <span>{{ __('messages.subtotal') }}</span>
                                            <span>{{ \App\Helpers\CurrencyHelper::formatWithPosition($subTotal) }}</span>
                                        </div>

                                        @if ($orderDiscountAmount > 0)
                                            <div class="total-row">
                                                <span>{{ __('messages.discount') }}</span>
                                                <span>-{{ \App\Helpers\CurrencyHelper::formatWithPosition($orderDiscountAmount) }}</span>
                                            </div>
                                        @endif

                                        <div class="total-row">
                                            <span>{{ __('messages.tax') }} ({{ $taxRate }}%)</span>
                                            <span>{{ \App\Helpers\CurrencyHelper::formatWithPosition($taxAmount) }}</span>
                                        </div>

                                        <div class="total-row total-main">
                                            <span>{{ __('messages.total_uppercase') }}</span>
                                            <span>{{ \App\Helpers\CurrencyHelper::formatWithPosition($grandTotal) }}</span>
                                        </div>

                                        <div class="payment-rows">
                                            <div class="total-row">
                                                <span>{{ __('messages.amount_received') }}</span>
                                                <span>{{ \App\Helpers\CurrencyHelper::formatWithPosition($amountReceived) }}</span>
                                            </div>
                                            <div class="total-row">
                                                <span>{{ __('messages.change') }}</span>
                                                <span>{{ \App\Helpers\CurrencyHelper::formatWithPosition($change) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Footer --}}
                                    <div class="text-center mt-3 receipt-footer">
                                        <p class="mb-1">{{ __('messages.thank_you_for_purchase') }}</p>
                                        <p class="small text-muted mb-0">{{ Auth::user()->shopname ?? __('messages.no_shop_name') }}</p>
                                        <p class="small text-muted mb-0">{{ __('messages.please_come_again') }}</p>
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
