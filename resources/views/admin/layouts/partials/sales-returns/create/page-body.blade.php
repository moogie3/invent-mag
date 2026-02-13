<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="card shadow-sm">
            <div class="card-header border-0">
                <h3 class="card-title">
                    <i class="ti ti-file-invoice me-2"></i>
                    {{ __('messages.sales_return_details') }}
                </h3>
            </div>
            <div class="card-body">
                <form id="sales-return-form" action="{{ route('admin.sales-returns.store') }}" method="POST">
                    @csrf

                    {{-- Sales Invoice Selection --}}
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label class="form-label required">{{ __('messages.sales_invoice') }}</label>
                            <select name="sales_id" id="sales-select"
                                class="form-select @error('sales_id') is-invalid @enderror">
                                <option value="">{{ __('messages.select_sales') }}</option>
                                @foreach ($sales as $sale)
                                    <option value="{{ $sale->id }}"
                                        {{ old('sales_id') == $sale->id ? 'selected' : '' }}>
                                        {{ $sale->invoice }}
                                        ({{ App\Helpers\CurrencyHelper::format($sale->total) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('sales_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label class="form-label required">{{ __('messages.return_date') }}</label>
                            <input type="date" name="return_date"
                                class="form-control @error('return_date') is-invalid @enderror"
                                value="{{ old('return_date', date('Y-m-d')) }}">
                            @error('return_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Reason for Return --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.reason_for_return') }}</label>
                        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3"
                            placeholder="{{ __('messages.reason') }}">{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Products to Return Section --}}
                    <div class="card mt-4 border">
                        <div class="card-header bg-light">
                            <h3 class="card-title">
                                <i class="ti ti-package me-2"></i>
                                {{ __('messages.products_to_return') }}
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="product-return-list" class="text-muted">
                                <div class="empty">
                                    <div class="empty-icon">
                                        <i class="ti ti-shopping-cart fs-1"></i>
                                    </div>
                                    <p class="empty-title">{{ __('messages.no_items_selected') }}</p>
                                    <p class="empty-subtitle text-muted">
                                        {{ __('messages.select_a_sales_order_to_see_its_items') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="items" id="items-json">

                    {{-- Total and Status --}}
                    <div class="row mt-4">
                        <div class="col-lg-6 mb-3">
                            <label class="form-label">{{ __('messages.total_return_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ti ti-currency-dollar"></i>
                                </span>
                                <input type="text" name="total_amount" id="total-amount"
                                    class="form-control @error('total_amount') is-invalid @enderror"
                                    value="{{ old('total_amount', 0) }}" readonly>
                                @error('total_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label class="form-label required">{{ __('messages.status') }}</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                @foreach (\App\Models\SalesReturn::$statuses as $status)
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

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.sales-returns.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-x me-2"></i>
                            {{ __('messages.cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            {{ __('messages.create_sales_return') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>