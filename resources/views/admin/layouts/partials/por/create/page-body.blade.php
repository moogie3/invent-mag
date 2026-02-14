<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body">
                        <form id="purchase-return-form" action="{{ route('admin.por.store') }}" method="POST">
                            @csrf

                            {{-- Return Information Section --}}
                            <div class="mb-5 border-bottom pb-4">
                                <h4 class="card-title mb-4">
                                    <i class="ti ti-receipt-refund me-2 text-primary"></i> {{ __('messages.pr_return_information_title') }}
                                </h4>
                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <label class="form-label fw-bold required">{{ __('messages.purchase_invoice') }}</label>
                                        <select name="purchase_id" id="purchase-select"
                                            class="form-select @error('purchase_id') is-invalid @enderror">
                                            <option value="">{{ __('messages.select_purchase') }}</option>
                                            @foreach ($purchases as $purchase)
                                                <option value="{{ $purchase->id }}"
                                                    {{ old('purchase_id') == $purchase->id ? 'selected' : '' }}>
                                                    {{ $purchase->invoice }}
                                                    ({{ App\Helpers\CurrencyHelper::format($purchase->total) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('purchase_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label fw-bold required">{{ __('messages.return_date') }}</label>
                                        <input type="date" name="return_date"
                                            class="form-control @error('return_date') is-invalid @enderror"
                                            value="{{ old('return_date', date('Y-m-d')) }}">
                                        @error('return_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="form-label fw-bold">{{ __('messages.reason_for_return') }}</label>
                                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3"
                                        placeholder="{{ __('messages.enter_reason_for_return') }}">{{ old('reason') }}</textarea>
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Products to Return Section --}}
                            <div class="mb-5 border-bottom pb-4">
                                <div class="row align-items-center mb-4">
                                    <div class="col">
                                        <h4 class="card-title mb-0">
                                            <i class="ti ti-box me-2 text-primary"></i>{{ __('messages.products_to_return') }}
                                        </h4>
                                    </div>
                                </div>
                                <div id="product-return-list">
                                    <div class="empty-state text-center py-5">
                                        <i class="ti ti-shopping-cart-off fs-1 text-muted mb-3 d-block"></i>
                                        <h4 class="text-muted">{{ __('messages.no_items_selected') }}</h4>
                                        <p class="text-muted small">{{ __('messages.select_a_purchase_order_to_see_its_items') }}</p>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="items" id="items-json">

                            {{-- Return Summary Section --}}
                            <div class="row mb-5">
                                <div class="col-md-6">
                                    <div class="h-100 p-4 bg-light rounded-3">
                                        <h4 class="fw-semibold mb-3 d-flex align-items-center">
                                            <i class="ti ti-report me-2 text-primary"></i> {{ __('messages.pr_return_summary_title') }}
                                        </h4>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('messages.total_return_amount') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ti ti-currency"></i>
                                                </span>
                                                <input type="text" name="total_amount" id="total-amount"
                                                    class="form-control @error('total_amount') is-invalid @enderror"
                                                    value="{{ old('total_amount', 0) }}" readonly>
                                                @error('total_amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold required">{{ __('messages.status') }}</label>
                                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                                @foreach (\App\Models\PurchaseReturn::$statuses as $status)
                                                    <option value="{{ $status }}"
                                                        {{ old('status', 'Pending') == $status ? 'selected' : '' }}>
                                                        {{ $status }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.por.index') }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-x me-2"></i>
                                    {{ __('messages.cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-check me-2"></i>
                                    {{ __('messages.create_purchase_return') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
