<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="ti ti-box"></i> {{ __('messages.order_items') }}</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light text-center">
                    <tr>
                        <th>{{ __('messages.no') }}</th>
                        <th>{{ __('messages.product') }}</th>
                        <th>{{ __('messages.available_stock') }}</th>
                        <th>{{ __('messages.quantity') }}</th>
                        <th>{{ __('messages.price') }}</th>
                        <th>{{ __('messages.discount') }}</th>
                        <th>{{ __('messages.amount') }}</th>
                        <th>{{ __('messages.action') }}</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <!-- Dynamic Items -->
                </tbody>
            </table>
        </div>
    </div>
</div>
