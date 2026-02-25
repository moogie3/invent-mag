<div class="card-body p-4">
    @include('admin.layouts.partials.po.view.po-info-section')
    @include('admin.layouts.partials.po.view.items-table')
    @include('admin.layouts.partials.po.view.summary-section')

    <div class="row mt-4">
        <div class="col-md-6">
            <h4>Payment History</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Notes</th>
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
                            <td colspan="4" class="text-center">No payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pos->balance > 0)
        <div class="col-md-6">
            <h4>Add Payment</h4>
            <form action="{{ route('admin.po.add-payment', $pos->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" max="{{ $pos->balance }}" required>
                </div>
                <div class="mb-3">
                    <label for="payment_date" class="form-label">Payment Date</label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select class="form-select" id="payment_method" name="payment_method" required>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Transfer">Transfer</option>
                        <option value="eWallet">eWallet</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Payment</button>
            </form>
        </div>
        @endif
    </div>
</div>
