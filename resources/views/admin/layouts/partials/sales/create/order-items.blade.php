<div class="mb-5">
    <h4 class="card-title mb-4">
        <i class="ti ti-box me-2 text-primary"></i> {{ __('messages.order_items_title') }}
    </h4>
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
                    <th>{{ __('messages.table_action') }}</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <!-- Dynamic Items -->
            </tbody>
        </table>
    </div>
</div>
