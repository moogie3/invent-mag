<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.reports.stock-transfer') }}" method="POST" id="stockTransferForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.from_warehouse') }}</label>
                    <select class="form-select @error('from_warehouse_id') is-invalid @enderror" name="from_warehouse_id" required>
                        <option value="">{{ __('messages.select_warehouse') }}</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('from_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                                @if ($warehouse->is_main)
                                    ({{ __('messages.table_main') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('from_warehouse_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.to_warehouse') }}</label>
                    <select class="form-select @error('to_warehouse_id') is-invalid @enderror" name="to_warehouse_id" required>
                        <option value="">{{ __('messages.select_warehouse') }}</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('to_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                                @if ($warehouse->is_main)
                                    ({{ __('messages.table_main') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('to_warehouse_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">{{ __('messages.product') }}</label>
                    <select class="form-select @error('product_id') is-invalid @enderror" name="product_id" id="productSelect" required>
                        <option value="">{{ __('messages.select_product') }}</option>
                    </select>
                    @error('product_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.available_quantity') }}</label>
                    <input type="text" class="form-control" id="availableQuantity" readonly disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('messages.quantity_to_transfer') }}</label>
                    <input type="number" class="form-control @error('quantity') is-invalid @enderror" name="quantity" min="1" required>
                    @error('quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">{{ __('messages.reason') }}</label>
                    <textarea class="form-control" name="reason" rows="3" placeholder="{{ __('messages.stock_transfer_reason_placeholder') }}">{{ old('reason') }}</textarea>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end mt-4">
                <a href="{{ route('admin.reports.adjustment-log') }}" class="btn btn-secondary me-2">{{ __('messages.cancel') }}</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-transfer me-2"></i>{{ __('messages.transfer') }}
                </button>
            </div>
        </form>
    </div>
</div>
