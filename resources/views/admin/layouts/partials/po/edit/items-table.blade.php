<div class="card border mb-4">
    <div class="card-header py-2">
        <div class="row align-items-center">
            <div class="col">
                <h4 class="card-title mb-0">
                    <i class="ti ti-list me-2 text-primary"></i>Order Items
                </h4>
            </div>
            <div class="col-auto">
                <small class="text-muted">
                    Select <strong>%</strong> for percentage or <strong>Rp</strong> for fixed discount
                </small>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px">No</th>
                    <th>Product</th>
                    <th class="text-center" style="width: 100px">QTY</th>
                    <th class="text-end" style="width: 140px">Price</th>
                    <th class="text-end" style="width: 160px">Discount</th>
                    <th class="text-end" style="width: 140px">Amount</th>
                    <th class="text-center" style="width: 140px">Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pos->items as $index => $item)
                    @include('admin.layouts.partials.po.edit.item-row', [
                        'item' => $item,
                        'index' => $index,
                    ])
                @endforeach
            </tbody>
        </table>
    </div>
</div>
