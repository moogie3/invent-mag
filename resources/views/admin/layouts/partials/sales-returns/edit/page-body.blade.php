<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body">
                        <form id="sales-return-edit-form" action="{{ route('admin.sales-returns.update', $salesReturn) }}" method="POST"
                            data-is-completed-or-canceled="{{ $isCompletedOrCanceled ? 'true' : 'false' }}"
                            data-status="{{ $salesReturn->status }}">
                            @csrf
                            @method('PUT')

                            {{-- Return Information Section --}}
                            <div class="mb-5 border-bottom pb-4">
                                <h4 class="card-title mb-4">
                                    <i class="ti ti-receipt-refund me-2 text-primary"></i> {{ __('messages.sr_return_information_title') }}
                                </h4>
                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <label class="form-label fw-bold">{{ __('messages.sales_invoice') }}</label>
                                        <input type="hidden" name="sales_id" value="{{ $salesReturn->sales_id }}">
                                        <div class="form-control-plaintext fw-bold">
                                            <a href="{{ route('admin.sales.view', $salesReturn->sales_id) }}" class="text-primary">
                                                {{ $salesReturn->sale->invoice }}
                                            </a>
                                            <span class="text-muted">({{ App\Helpers\CurrencyHelper::format($salesReturn->sale->total) }})</span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label fw-bold required">{{ __('messages.return_date') }}</label>
                                        <input type="date" name="return_date"
                                            class="form-control @error('return_date') is-invalid @enderror"
                                            value="{{ old('return_date', $salesReturn->return_date->format('Y-m-d')) }}">
                                        @error('return_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="form-label fw-bold">{{ __('messages.reason_for_return') }}</label>
                                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3"
                                        placeholder="{{ __('messages.enter_reason_for_return') }}">{{ old('reason', $salesReturn->reason) }}</textarea>
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
                                    {{-- This will be populated by JavaScript --}}
                                </div>
                            </div>

                            <input type="hidden" name="items" id="items-json">
                            <input type="hidden" id="sales-return-items" value="{{ json_encode($salesReturn->items) }}">

                            {{-- Return Summary Section --}}
                            <div class="row mb-5">
                                <div class="col-md-6">
                                    <div class="h-100 p-4 bg-light rounded-3">
                                        <h4 class="fw-semibold mb-3 d-flex align-items-center">
                                            <i class="ti ti-report me-2 text-primary"></i> {{ __('messages.sr_return_summary_title') }}
                                        </h4>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('messages.total_return_amount') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ti ti-currency"></i>
                                                </span>
                                                <input type="text" name="total_amount" id="total-amount"
                                                    class="form-control @error('total_amount') is-invalid @enderror"
                                                    value="{{ old('total_amount', $salesReturn->total_amount) }}" readonly>
                                                @error('total_amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold required">{{ __('messages.status') }}</label>
                                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                                @foreach (\App\Models\SalesReturn::$statuses as $status)
                                                    <option value="{{ $status }}"
                                                        {{ old('status', $salesReturn->status) == $status ? 'selected' : '' }}>
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
                                <a href="{{ route('admin.sales-returns.index') }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-x me-2"></i>
                                    {{ __('messages.cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-check me-2"></i>
                                    {{ __('messages.update_sales_return') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
