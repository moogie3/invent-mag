<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.purchase_return_details') }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.purchase-returns.update', $purchaseReturn) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.purchase_invoice') }}</label>
                        <select name="purchase_id" id="purchase-select"
                            class="form-select @error('purchase_id') is-invalid @enderror">
                            <option value="">{{ __('messages.select_purchase') }}</option>
                            @foreach ($purchases as $purchase)
                                <option value="{{ $purchase->id }}"
                                    {{ old('purchase_id', $purchaseReturn->purchase_id) == $purchase->id ? 'selected' : '' }}>
                                    {{ $purchase->invoice }}
                                    ({{ App\Helpers\CurrencyHelper::format($purchase->total) }})
                                </option>
                            @endforeach
                        </select>
                        @error('purchase_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.return_date') }}</label>
                        <input type="date" name="return_date"
                            class="form-control @error('return_date') is-invalid @enderror"
                            value="{{ old('return_date', $purchaseReturn->return_date->format('Y-m-d')) }}">
                        @error('return_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.reason_for_return') }}</label>
                        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3">{{ old('reason', $purchaseReturn->reason) }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('messages.products_to_return') }}</h3>
                        </div>
                        <div class="card-body">
                            <div id="product-return-list">
                                {{-- Products will be loaded here via Javascript --}}
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="items" id="items-json" value="{{ old('items', json_encode($purchaseReturn->items->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->total,
                        ];
                    }))) }}">

                    <div class="mb-3 mt-4">
                        <label class="form-label">{{ __('messages.total_return_amount') }}</label>
                        <input type="text" name="total_amount" id="total-amount"
                            class="form-control @error('total_amount') is-invalid @enderror"
                            value="{{ old('total_amount', $purchaseReturn->total_amount) }}" readonly>
                        @error('total_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.status') }}</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach (\App\Models\PurchaseReturn::$statuses as $status)
                                <option value="{{ $status }}"
                                    {{ old('status', $purchaseReturn->status) == $status ? 'selected' : '' }}>{{ $status }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('messages.update_purchase_return') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
