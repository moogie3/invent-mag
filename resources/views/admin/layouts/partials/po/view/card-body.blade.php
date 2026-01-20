<div class="card-body p-4">
    @include('admin.layouts.partials.po.view.po-info-section')
    @include('admin.layouts.partials.po.view.items-table')
    @include('admin.layouts.partials.po.view.summary-section')

    <div class="row mt-4">
        <div class="col-md-12">
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
    </div>
</div>
