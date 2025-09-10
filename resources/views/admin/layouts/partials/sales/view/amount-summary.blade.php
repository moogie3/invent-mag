<div class="card border">
    <div class="card-body p-3">
        <h5 class="mb-3 card-title">Amount Summary</h5>
        <div class="d-flex justify-content-between mb-2">
            <div>Subtotal:</div>
            <div>{{ \App\Helpers\CurrencyHelper::format($subtotal) }}</div>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div>
                Order Discount
                <small class="text-muted">
                    ({{ ($sales->order_discount_type ?? 'fixed') === 'percentage' ? ($sales->order_discount ?? 0) . '%' : 'Fixed' }})
                </small>:
            </div>
            <div class="text-danger">-
                {{ \App\Helpers\CurrencyHelper::format($orderDiscount) }}
            </div>
        </div>
        <div class="d-flex justify-content-between mb-2">
                <div>
                    Tax
                    <small class="text-muted">
                        ({{ $sales->tax_rate ?? 0 }}%)
                    </small>:
                </div>
                <div>
                    {{ \App\Helpers\CurrencyHelper::format($taxAmount) }}
                </div>
            </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <div class="fs-5"><strong>Grand Total:</strong></div>
            <div class="fs-3 fw-bold text-primary">
                {{ \App\Helpers\CurrencyHelper::format($finalTotal) }}
            </div>
        </div>
    </div>
</div>
