<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="card shadow-sm">
            <div class="card-header border-0">
                <h3 class="card-title">
                    <i class="ti ti-file-invoice me-2"></i>
                    {{ __('messages.purchase_return_details') }}
                </h3>
            </div>
            <div class="card-body">
                <form id="purchase-return-edit-form" action="{{ route('admin.por.update', $por) }}" method="POST"
                    data-is-completed-or-canceled="{{ $isCompletedOrCanceled ? 'true' : 'false' }}"
                    data-status="{{ $por->status }}">
                    @csrf
                    @method('PUT')

                    {{-- Purchase Invoice Selection --}}
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label class="form-label required">{{ __('messages.purchase_invoice') }}</label>
                            <input type="hidden" name="purchase_id" value="{{ $por->purchase_id }}">
                            <select id="purchase-select" class="form-select @error('purchase_id') is-invalid @enderror"
                                disabled>
                                <option value="{{ $por->purchase->id }}" selected>
                                    {{ $por->purchase->invoice }}
                                    ({{ App\Helpers\CurrencyHelper::format($por->purchase->total) }})
                                </option>
                            </select>
                            @error('purchase_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label class="form-label required">{{ __('messages.return_date') }}</label>
                            <input type="date" name="return_date"
                                class="form-control @error('return_date') is-invalid @enderror"
                                value="{{ old('return_date', $por->return_date->format('Y-m-d')) }}">
                            @error('return_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Reason for Return --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.reason_for_return') }}</label>
                        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3"
                            placeholder="{{ __('messages.reason') }}">{{ old('reason', $por->reason) }}</textarea>
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
                            <div id="product-return-list">
                                {{-- This will be populated by JavaScript --}}
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="items" id="items-json">
                    <input type="hidden" id="purchase-return-items" value="{{ json_encode($por->items) }}">


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
                                    value="{{ old('total_amount', $por->total_amount) }}" readonly>
                                @error('total_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label class="form-label required">{{ __('messages.status') }}</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                @foreach (\App\Models\PurchaseReturn::$statuses as $status)
                                    <option value="{{ $status }}"
                                        {{ old('status', $por->status) == $status ? 'selected' : '' }}>
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
                        <a href="{{ route('admin.por.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-x me-2"></i>
                            {{ __('messages.cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>
                            {{ __('messages.update_purchase_return') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
