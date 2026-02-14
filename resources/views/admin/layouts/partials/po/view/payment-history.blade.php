<div>
    <div class="row align-items-center mb-4">
        <div class="col">
            <h4 class="card-title mb-0">
                <i class="ti ti-history me-2 text-primary"></i>{{ __('messages.payment_history') }}
            </h4>
        </div>
    </div>
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
