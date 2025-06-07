<div class="card shadow-sm border-1 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="ti ti-history fs-3 me-2 text-primary"></i> Recent Transactions
        </h3>
        <div class="card-actions">
            <div class="dropdown">
                <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                </a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#">View All Sales</a>
                    <a class="dropdown-item" href="#">View All Purchases</a>
                    <a class="dropdown-item" href="{{ route('admin.transactions') }}">View Transactions</a>
                    <a class="dropdown-item" href="#">Export Transactions</a>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter table-hover mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Invoice</th>
                    <th>Customer/Supplier</th>
                    <th>Date</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">Status</th>
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
                                {{ $transaction['type'] == 'sale' ? 'Sales' : 'Purchase' }}</div>
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
                        <td colspan="6" class="text-center py-3 text-muted">No recent
                            transactions found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($hasMore)
        <div class="card-footer text-center py-3">
            <a href="{{ route('admin.transactions') }}" class="btn btn-link text-decoration-none">
                <i class="ti ti-eye me-1"></i>
                View other recent activities
            </a>
        </div>
    @endif
</div>
