<div class="card border mb-4">
    <div class="card-header py-2">
        <div class="row align-items-center">
            <div class="col">
                <h4 class="card-title mb-0">
                    <i class="ti ti-list me-2 text-primary"></i>{{ __('messages.po_order_items_title') }}
                </h4>
            </div>
            <div class="col-auto">
                <small class="text-muted">
                    {{ __('messages.discount_hint') }}
                </small>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px">{{ __('messages.table_no') }}</th>
                    <th>{{ __('messages.table_product') }}</th>
                    <th class="text-center" style="width: 100px">{{ __('messages.table_qty') }}</th>
                    <th class="text-end" style="width: 140px">{{ __('messages.table_price') }}</th>
                    <th class="text-end" style="width: 160px">{{ __('messages.table_discount') }}</th>
                    <th class="text-end" style="width: 140px">{{ __('messages.table_amount') }}</th>
                    <th class="text-center" style="width: 140px">{{ __('messages.table_expiry_date') }}</th>
                </tr>
            </thead>
            <tbody id="po-items-table-body">
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
