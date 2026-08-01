<form id="edit-sales-form" enctype="multipart/form-data" method="POST" action="{{ route('admin.sales.update', $sales->id) }}" novalidate data-validate-form>
    @csrf
    @method('PUT')
    <div class="card-body p-4">
        @php
            // Use the helper to calculate summary info accounting for returns
            $summary = \App\Helpers\SalesHelper::calculateInvoiceSummaryWithReturns(
                $sales->salesItems,
                $sales->order_discount ?? 0,
                $sales->order_discount_type ?? 'fixed',
                $sales->tax_rate ?? 0,
                $sales->relationLoaded('salesReturns') ? $sales->salesReturns : collect(),
                $sales->tax_type ?? 'exclusive'
            );
        @endphp

        @include('admin.layouts.partials.sales.edit.sales-info-section')
        @include('admin.layouts.partials.sales.edit.items-table')
        @include('admin.layouts.partials.sales.edit.summary-section')
        <input type="hidden" name="products" id="products-json">
    </div>
</form>

<div class="card-body p-4 border-top">
    <div class="row g-4">
        <div class="col-md-6">
            <h4 class="card-title mb-4">
                <i class="ti ti-history me-2 text-primary"></i>{{ __('messages.payment_history') }}
            </h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('messages.payment_date') }}</th>
                            <th>{{ __('messages.amount') }}</th>
                            <th>{{ __('messages.payment_type') }}</th>
                            <th>{{ __('messages.notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                <td>{{ \App\Helpers\CurrencyHelper::format($payment->amount) }}</td>
                                <td>{{ $payment->payment_method }}</td>
                                <td>{{ $payment->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">{{ __('messages.no_payment_history_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($sales->balance > 0)
        <div class="col-md-6">
            <h4 class="card-title mb-4">
                <i class="ti ti-plus me-2 text-primary"></i>{{ __('messages.add_payment') }}
            </h4>
            <form action="{{ route('admin.sales.add-payment', $sales->id) }}" method="POST" id="add-payment-form" novalidate data-validate-form>
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold" for="amount">{{ __('messages.cash_amount') }}</label>
                    <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" step="0.01" min="0" value="{{ old('amount', '0') }}">
                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">{{ __('messages.balance') }}: {{ \App\Helpers\CurrencyHelper::format($sales->balance) }}</small>
                </div>
                
                @php
                    $availableCredit = \App\Helpers\SalesHelper::getCustomerAvailableCredit($sales->customer_id);
                @endphp
                
                @if($availableCredit > 0)
                <div class="mb-3 p-3 bg-info-lt rounded">
                    <label class="form-label fw-bold" for="credit_amount">
                        <i class="ti ti-credit-card me-1"></i>{{ __('messages.use_credit') }}
                    </label>
                    <div class="d-flex align-items-center mb-2">
                        <span class="text-success fw-bold fs-5">{{ \App\Helpers\CurrencyHelper::format($availableCredit) }}</span>
                        <span class="text-muted ms-2">{{ __('messages.available_credits') }}</span>
                    </div>
                    <input type="number" class="form-control @error('credit_amount') is-invalid @enderror" id="credit_amount" name="credit_amount" 
                           step="0.01" min="0" max="{{ min($availableCredit, $sales->balance) }}" 
                           placeholder="0" value="{{ old('credit_amount', '0') }}">
                    @error('credit_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">{{ __('messages.credit_will_be_applied_to_balance') }}</small>
                </div>
                @endif
                
                <div class="mb-3">
                    <label for="payment_date" class="form-label fw-bold">{{ __('messages.payment_date') }}</label>
                    <input type="date" class="form-control @error('payment_date') is-invalid @enderror" id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                    @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="payment_method" class="form-label fw-bold">{{ __('messages.payment_type') }}</label>
                    <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" required>
                        <option value="Cash" {{ old('payment_method') == 'Cash' ? 'selected' : '' }}>{{ __('messages.payment_method_cash') }}</option>
                        <option value="Card" {{ old('payment_method') == 'Card' ? 'selected' : '' }}>{{ __('messages.payment_method_card') }}</option>
                        <option value="Transfer" {{ old('payment_method') == 'Transfer' ? 'selected' : '' }}>{{ __('messages.payment_method_transfer') }}</option>
                        <option value="eWallet" {{ old('payment_method') == 'eWallet' ? 'selected' : '' }}>{{ __('messages.payment_method_ewallet') }}</option>
                    </select>
                    @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label fw-bold">{{ __('messages.notes') }}</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> {{ __('messages.add_payment') }}
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
