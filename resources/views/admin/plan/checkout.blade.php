@extends('admin.layouts.base')

@section('title', __('plan.checkout'))

@section('content')
<div class="page-wrapper">
    <div class="page-body">
        <div class="container-xl">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 pb-0">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h3 class="mb-0">
                                        <i class="ti ti-file-invoice text-primary me-2"></i>
                                        {{ __('plan.invoice') }}
                                    </h3>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-warning text-white">{{ __('plan.pending_payment') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted mb-1">{{ __('plan.from') }}</h6>
                                    <p class="mb-0 fw-bold">{{ config('app.name', 'InventMag') }}</p>
                                    <p class="text-muted small mb-0">support@inventmag.com</p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <h6 class="text-uppercase text-muted mb-1">{{ __('plan.invoice_date') }}</h6>
                                    <p class="mb-0">{{ $order->created_at->format('F d, Y') }}</p>
                                    <p class="text-danger small mb-0">
                                        <i class="ti ti-clock me-1"></i>
                                        {{ __('plan.payment_expires') }}: <span id="countdown" data-expires="{{ $order->created_at->addHours(24)->toIso8601String() }}">--:--:--</span>
                                    </p>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <thead>
                                        <tr class="text-uppercase text-muted small">
                                            <th>{{ __('plan.description') }}</th>
                                            <th class="text-center">{{ __('plan.qty') }}</th>
                                            <th class="text-end">{{ __('plan.price') }}</th>
                                            <th class="text-end">{{ __('plan.total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $newPlan->name }} {{ __('plan.subscription_plan') }}</div>
                                                <div class="text-muted small">{{ __('plan.monthly_billing') }}</div>
                                            </td>
                                            <td class="text-center">1</td>
                                            <td class="text-end">${{ number_format($newPlan->price, 0) }}</td>
                                            <td class="text-end fw-bold">${{ number_format($newPlan->price, 0) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end">{{ __('plan.subtotal') }}:</td>
                                            <td class="text-end">${{ number_format($newPlan->price, 0) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end">{{ __('plan.tax') }} (0%):</td>
                                            <td class="text-end">$0</td>
                                        </tr>
                                        <tr class="border-top">
                                            <td colspan="3" class="text-end fw-bold">{{ __('plan.grand_total') }}:</td>
                                            <td class="text-end">
                                                <div class="fw-bold text-primary fs-5">${{ number_format($newPlan->price, 0) }}</div>
                                                <div class="text-muted small">~ Rp {{ number_format($priceIdr, 0, ',', '.') }}</div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="bg-light rounded-3 p-3">
                                        <h6 class="mb-2">{{ __('plan.current_plan') }}</h6>
                                        <p class="mb-0">
                                            @if($currentPlan)
                                                <span class="badge bg-success text-white">{{ $currentPlan->name }}</span>
                                            @else
                                                <span class="badge bg-secondary text-white">{{ __('plan.no_plan') }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="bg-light rounded-3 p-3">
                                        <h6 class="mb-2">{{ __('plan.new_plan') }}</h6>
                                        <p class="mb-0">
                                            <span class="badge bg-primary text-white">{{ $newPlan->name }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-0">
                            @if(empty($snapToken))
                                <div class="alert alert-danger">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    {{ __('plan.token_error') }}
                                </div>
                                <a href="{{ route('admin.setting.plan') }}" class="btn btn-secondary rounded-pill">{{ __('plan.back_to_plans') }}</a>
                            @else
                                <div class="row g-3">
                                    <div class="col-12">
                                        <h5 class="mb-3">{{ __('plan.select_payment_method') }}</h5>
                                    </div>
                                    <div class="col-md-6">
                                        <button id="pay-button" 
                                                class="btn btn-primary w-100 rounded-3 py-3"
                                                data-snap-token="{{ $snapToken }}"
                                                data-order-id="{{ $order->order_number }}"
                                                data-client-key="{{ config('midtrans.client_key') }}"
                                                data-is-production="{{ config('midtrans.is_production') ? 'true' : 'false' }}"
                                                data-success-url="{{ route('admin.setting.plan') }}?success=1">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="ti ti-credit-card me-2 fs-5"></i>
                                                <div class="text-start">
                                                    <div class="fw-bold">{{ __('plan.pay_with_midtrans') }}</div>
                                                    <small class="text-white-50">{{ __('plan.credit_debit_card') }}</small>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <button class="btn btn-outline-secondary w-100 rounded-3 py-3" disabled>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="ti ti-building-bank me-2 fs-5"></i>
                                                <div class="text-start">
                                                    <div class="fw-bold">{{ __('plan.bank_transfer') }}</div>
                                                    <small class="text-muted">{{ __('plan.coming_soon') }}</small>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <a href="{{ route('admin.setting.plan') }}" class="text-muted text-decoration-none small">
                                        <i class="ti ti-arrow-left me-1"></i> {{ __('plan.cancel_and_back') }}
                                    </a>
                                </div>

                                <div id="payment-status" class="alert mt-3 d-none"></div>
                            @endif
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <p class="text-muted small mb-0">
                            <i class="ti ti-lock me-1"></i>
                            {{ __('plan.secure_payment') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
