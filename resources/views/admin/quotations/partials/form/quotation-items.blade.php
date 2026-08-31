<div class="mb-5">
    <h4 class="card-title mb-4">
        <i class="ti ti-box me-2 text-primary"></i> {{ __('messages.quotation_items') }}
    </h4>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label fw-bold">{{ __('messages.products') }}</label>
            <select id="productSelector" class="form-select">
                <option value="">{{ __('messages.select_product') }}</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->selling_price ?? 0 }}" data-name="{{ $product->name }}">
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold">{{ __('messages.quantity') }}</label>
            <input type="number" id="itemQuantity" class="form-control" value="1" min="1">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold">{{ __('messages.table_price') }}</label>
            <input type="number" id="itemPrice" class="form-control" value="0" step="1" min="0">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold d-block invisible">{{ __('messages.add') }}</label>
            <button type="button" class="btn btn-primary w-100" id="addItemBtn">
                <i class="ti ti-plus"></i> {{ __('messages.add') }}
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="text-center">
                <tr>
                    <th>{{ __('messages.table_no') }}</th>
                    <th>{{ __('messages.table_product') }}</th>
                    <th>{{ __('messages.quantity') }}</th>
                    <th>{{ __('messages.table_price') }}</th>
                    <th>{{ __('messages.table_discount') }}</th>
                    <th>{{ __('messages.table_amount') }}</th>
                    <th>{{ __('messages.table_action') }}</th>
                </tr>
            </thead>
            <tbody id="quotationItemsBody"
                data-lang-percentage="{{ __('messages.percentage') }}"
                data-lang-fixed="{{ __('messages.fixed') }}"
                data-lang-delete="{{ __('messages.delete') }}"
                data-currency-symbol="{{ \App\Helpers\CurrencyHelper::getCurrencySymbol() }}"
                data-decimal-places="{{ \App\Models\CurrencySetting::first()->decimal_places ?? 0 }}"
                data-decimal-separator="{{ \App\Models\CurrencySetting::first()->decimal_separator ?? '.' }}"
                data-thousand-separator="{{ \App\Models\CurrencySetting::first()->thousand_separator ?? ',' }}"
                data-currency-code="{{ \App\Models\CurrencySetting::first()->currency_code ?? 'USD' }}"
                data-position="{{ \App\Models\CurrencySetting::first()->position ?? 'prefix' }}">
                @if(isset($quotation) && !empty($quotation->items))
                    @foreach(collect($quotation->items)->map(fn($item) => is_array($item) ? (object) $item : $item) as $index => $item)
                        @include('admin.quotations.partials.form.quotation-item-row', ['item' => $item, 'index' => $index])
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
