<form id="edit-po-form" enctype="multipart/form-data" method="POST" action="{{ route('admin.po.update', $pos->id) }}">
    @csrf
    @method('PUT')
    <div class="card-body p-4">
        @php
            // Calculate summary info once and make it available to all partials
            $summary = \App\Helpers\PurchaseHelper::calculateInvoiceSummary(
                $pos->items,
                $pos->discount_total,
                $pos->discount_total_type,
            );
        @endphp

        @include('admin.layouts.partials.po.edit.po-info-section')
        @include('admin.layouts.partials.po.edit.items-table')
        @include('admin.layouts.partials.po.edit.summary-section')
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
                            <th>{{ __('messages.po_payment_type') }}</th>
                            <th>{{ __('messages.po_notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pos->payments as $payment)
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
        @if ($pos->balance > 0)
        <div class="col-md-6">
            <h4 class="card-title mb-4">
                <i class="ti ti-plus me-2 text-primary"></i>{{ __('messages.add_payment') }}
            </h4>
            <form action="{{ route('admin.po.add-payment', $pos->id) }}" method="POST" id="add-payment-form">
                @csrf
                <div class="mb-3">
                    <label for="amount" class="form-label">{{ __('messages.amount') }}</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" max="{{ $pos->balance }}" required>
                </div>
                <div class="mb-3">
                    <label for="payment_date" class="form-label">{{ __('messages.payment_date') }}</label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label for="payment_method" class="form-label">{{ __('messages.pos_payment_method') }}</label>
                    <select class="form-select" id="payment_method" name="payment_method" required>
                        <option value="Cash">{{ __('messages.payment_method_cash') }}</option>
                        <option value="Card">{{ __('messages.pos_payment_method_card') }}</option>
                        <option value="Transfer">{{ __('messages.payment_method_transfer') }}</option>
                        <option value="eWallet">{{ __('messages.pos_payment_method_ewallet') }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">{{ __('messages.po_notes') }}</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> {{ __('messages.add_payment') }}
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
