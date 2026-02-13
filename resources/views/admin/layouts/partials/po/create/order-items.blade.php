<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white border-bottom">
        <h3 class="card-title"><i class="ti ti-box"></i> {{ __('messages.po_order_items_title') }}</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="text-center">
                    <tr>
                        <th>{{ __('messages.table_no') }}</th>
                        <th>{{ __('messages.table_product') }}</th>
                        <th>{{ __('messages.available_stock') }}</th>
                        <th>{{ __('messages.quantity') }}</th>
                        <th>{{ __('messages.table_price') }}</th>
                        <th>{{ __('messages.table_discount') }}</th>
                        <th>{{ __('messages.table_amount') }}</th>
                        <th>{{ __('messages.table_expiry_date') }}</th>
                        <th>{{ __('messages.table_action') }}</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <!-- Dynamic Items -->
                </tbody>
            </table>
        </div>
    </div>
</div>
