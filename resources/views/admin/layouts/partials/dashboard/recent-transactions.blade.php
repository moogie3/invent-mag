<div class="card shadow-sm border-1 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="ti ti-history fs-3 me-2 text-primary"></i> {{ __('messages.recent_transactions') }}
        </h3>
    </div>
    <div class="table-responsive" style="min-height: 360px;">
        <table class="table table-vcenter table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('messages.type') }}</th>
                    <th>{{ __('messages.invoice') }}</th>
                    <th>{{ __('messages.customer_supplier') }}</th>
                    <th>{{ __('messages.date') }}</th>
                    <th class="text-end">{{ __('messages.amount') }}</th>
                    <th class="text-center">{{ __('messages.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $transactions = $recentTransactions ?? collect([]);
                    $limitedTransactions = $transactions->take(5);
                    $hasMore = $transactions->count() > 5;
                @endphp

                @forelse ($limitedTransactions as $transaction)
                    <tr>
                        <td>
                            <span
                                class="avatar avatar-sm {{ $transaction['type'] == 'sale' ? 'bg-success' : 'bg-info' }}-lt">
                                <i
                                    class="ti {{ $transaction['type'] == 'sale' ? 'ti-arrow-up' : 'ti-arrow-down' }}"></i>
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $transaction['invoice'] }}</div>
                            <div class="small text-muted">
                                {{ $transaction['type'] == 'sale' ? __('messages.sales_management') : __('messages.purchase_order') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $transaction['customer_supplier'] }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">
                                {{ \Carbon\Carbon::parse($transaction['date'])->format('M d, Y') }}
                            </div>
                            <div class="small text-muted">
                                {{ \Carbon\Carbon::parse($transaction['date'])->format('h:i A') }}
                            </div>
                        </td>
                        <td class="text-end fw-medium">
                            {{ \App\Helpers\CurrencyHelper::format($transaction['amount']) }}
                        </td>
                        <td class="text-center">
                            <span
                                class="badge {{ $transaction['status'] == 'Paid' ? 'bg-success' : ($transaction['status'] == 'Partial' ? 'bg-warning' : 'bg-danger') }}-lt">
                                {{ $transaction['status'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-3 text-muted">{{ __('messages.no_recent_transactions_found') }}
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer text-center">
        <a href="{{ route('admin.transactions') }}">{{ __('messages.view_all_transactions') }}</a>
    </div>
</div>
