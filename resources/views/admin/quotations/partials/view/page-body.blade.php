<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-deck row-cards">
            <div class="col-md-12">
                @php
                    $statusBadgeClasses = [
                        'draft' => 'bg-secondary-lt',
                        'sent' => 'bg-primary-lt',
                        'accepted' => 'bg-success-lt',
                        'rejected' => 'bg-danger-lt',
                        'expired' => 'bg-warning-lt',
                        'converted' => 'bg-info-lt',
                    ];
                    $statusBarClasses = [
                        'draft' => 'bg-secondary',
                        'sent' => 'bg-primary',
                        'accepted' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'expired' => 'bg-warning',
                        'converted' => 'bg-info',
                    ];
                @endphp
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="row align-items-center w-100">
                            <div class="col">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="status-indicator {{ $statusBarClasses[$quotation->status] ?? 'bg-secondary' }}" style="width: 6px; height: 36px; border-radius: 3px;"></div>
                                    <div>
                                        <h4 class="mb-0">{{ $quotation->quotation_number }}</h4>
                                        <div class="text-muted">
                                            {{ $quotation->customer?->name ?? __('messages.not_available') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <span class="badge fs-6 px-3 py-2 rounded-pill {{ $statusBadgeClasses[$quotation->status] ?? 'bg-secondary-lt' }}">
                                    {{ __("messages.quotation_status_{$quotation->status}") }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4 border-bottom pb-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="h-100">
                                        <h5 class="card-title mb-3 text-secondary">
                                            <i class="ti ti-users me-2 text-muted"></i>{{ __('messages.customer') }}
                                        </h5>
                                        <h4 class="mb-2 fw-bold">{{ $quotation->customer?->name ?? __('messages.not_available') }}</h4>
                                        @if($quotation->customer?->address)
                                            <div class="text-muted mb-1">
                                                <i class="ti ti-map-pin me-1"></i>{{ $quotation->customer->address }}
                                            </div>
                                        @endif
                                        @if($quotation->customer?->phone)
                                            <div class="text-muted mb-1">
                                                <i class="ti ti-phone me-1"></i>{{ $quotation->customer->phone }}
                                            </div>
                                        @endif
                                        @if($quotation->customer?->email)
                                            <div class="text-muted mb-1">
                                                <i class="ti ti-mail me-1"></i>{{ $quotation->customer->email }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="h-100">
                                        <h5 class="card-title mb-3 text-secondary">
                                            <i class="ti ti-calendar-event me-2 text-muted"></i>{{ __('messages.quotation_details') }}
                                        </h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div><strong>{{ __('messages.quotation_date') }}</strong></div>
                                            <div>{{ $quotation->quotation_date?->format('d M Y') }}</div>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div><strong>{{ __('messages.valid_until') }}</strong></div>
                                            <div>{{ $quotation->valid_until?->format('d M Y') ?? '-' }}</div>
                                        </div>
                                        @if($quotation->user)
                                        <div class="d-flex justify-content-between mb-2">
                                            <div><strong>{{ __('messages.user') }}</strong></div>
                                            <div>{{ $quotation->user->name }}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 pb-4">
                            <div class="row align-items-center mb-4">
                                <div class="col">
                                    <h4 class="card-title mb-0">
                                        <i class="ti ti-box me-2 text-primary"></i>{{ __('messages.quotation_items') }}
                                    </h4>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="text-center">
                                        <tr>
                                            <th class="text-center" style="width: 60px">{{ __('messages.table_no') }}</th>
                                            <th>{{ __('messages.table_product') }}</th>
                                            <th class="text-center" style="width: 100px">{{ __('messages.quantity') }}</th>
                                            <th class="text-end" style="width: 140px">{{ __('messages.table_price') }}</th>
                                            <th class="text-end" style="width: 160px">{{ __('messages.table_discount') }}</th>
                                            <th class="text-end" style="width: 140px">{{ __('messages.table_amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $items = collect($quotation->items)->map(fn($item) => is_array($item) ? (object) $item : $item);
                                        @endphp
                                        @foreach($items as $index => $item)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $item->product_name ?? $item->name ?? __('messages.not_available') }}</div>
                                            </td>
                                            <td class="text-center">{{ $item->quantity }}</td>
                                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->price) }}</td>
                                            <td class="text-end">
                                                @if($item->discount > 0)
                                                    {{ $item->discount_type === 'percentage' ? $item->discount . '%' : \App\Helpers\CurrencyHelper::format($item->discount) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold">{{ \App\Helpers\CurrencyHelper::format($item->total) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6 d-flex flex-column gap-3">
                                @if($quotation->notes)
                                <div class="p-3 bg-body-secondary rounded-3">
                                    <h6 class="fw-bold mb-2"><i class="ti ti-notes me-1"></i>{{ __('messages.quotation_notes') }}</h6>
                                    <p class="text-muted mb-0">{{ $quotation->notes }}</p>
                                </div>
                                @endif
                                @if($quotation->terms_conditions)
                                <div class="p-3 bg-body-secondary rounded-3">
                                    <h6 class="fw-bold mb-2"><i class="ti ti-file-text me-1"></i>{{ __('messages.quotation_terms_conditions') }}</h6>
                                    <p class="text-muted mb-0">{{ $quotation->terms_conditions }}</p>
                                </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="h-100 p-4 bg-primary-lt rounded-3">
                                    <h5 class="card-title mb-3 text-primary">
                                        <i class="ti ti-receipt me-2"></i>{{ __('messages.amount_summary_title') }}
                                    </h5>
                                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-primary border-opacity-25">
                                        <span class="text-muted">{{ __('messages.quotation_subtotal') }}</span>
                                        <strong>{{ \App\Helpers\CurrencyHelper::format($quotation->subtotal) }}</strong>
                                    </div>
                                    @if($quotation->discount_amount > 0)
                                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-primary border-opacity-25">
                                        <span class="text-muted">{{ __('messages.quotation_discount') }}
                                            @if($quotation->discount_type === 'percentage')
                                                ({{ $quotation->discount_value }}%)
                                            @endif
                                        </span>
                                        <strong class="text-danger">-{{ \App\Helpers\CurrencyHelper::format($quotation->discount_amount) }}</strong>
                                    </div>
                                    @endif
                                    @if($quotation->tax_amount > 0)
                                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-primary border-opacity-25">
                                        <span class="text-muted">{{ __('messages.quotation_tax') }}
                                            @if($quotation->tax_type !== 'none')
                                                ({{ $quotation->tax_rate }}%, {{ $quotation->tax_type }})
                                            @endif
                                        </span>
                                        <strong>{{ \App\Helpers\CurrencyHelper::format($quotation->tax_amount) }}</strong>
                                    </div>
                                    @endif
                                    @php
                                        $currentTenant = app('currentTenant');
                                        $showPph = $currentTenant instanceof \App\Models\Tenant && $currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn');
                                    @endphp
                                    @if($showPph && ($quotation->pph_enabled ?? false) && ($quotation->pph_amount ?? 0) > 0)
                                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-primary border-opacity-25">
                                        <span class="text-muted">{{ __('messages.withholding_tax') }} ({{ $quotation->pph_rate }}%)</span>
                                        <strong class="text-danger">-{{ \App\Helpers\CurrencyHelper::format($quotation->pph_amount) }}</strong>
                                    </div>
                                    @endif
                                    <div class="mb-2 pb-2 border-bottom border-primary border-opacity-25"></div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold fs-5">{{ __('messages.quotation_total') }}</span>
                                        <strong class="fs-2 text-primary">{{ \App\Helpers\CurrencyHelper::format($quotation->total) }}</strong>
                                    </div>
                                    @if($quotation->tax_type === 'inclusive' && $quotation->tax_amount > 0)
                                    <div class="text-muted small text-end mt-1">
                                        {{ __('messages.quotation_tax_included') }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if(in_array($quotation->status, ['draft', 'sent', 'accepted']))
                        <div class="row mt-4 no-print">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    @if($quotation->status === 'draft')
                                    <form method="POST" action="{{ route('admin.quotations.status', $quotation->id) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="sent">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-send me-1"></i> {{ __('messages.mark_as_sent') }}
                                        </button>
                                    </form>
                                    @endif

                                    @if($quotation->status === 'sent')
                                    <form method="POST" action="{{ route('admin.quotations.status', $quotation->id) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="accepted">
                                        <button type="submit" class="btn btn-success">
                                            <i class="ti ti-check me-1"></i> {{ __('messages.mark_as_accepted') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.quotations.status', $quotation->id) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="ti ti-x me-1"></i> {{ __('messages.mark_as_rejected') }}
                                        </button>
                                    </form>
                                    @endif

                                    @if($quotation->status === 'accepted')
                                    <form method="POST" action="{{ route('admin.quotations.convert-to-sales', $quotation->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success">
                                            <i class="ti ti-report-money me-1"></i> {{ __('messages.convert_to_sales_order') }}
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($quotation->status === 'converted' && $quotation->convertedToSales)
                        <div class="row mt-4 no-print">
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <i class="ti ti-info-circle me-2"></i>
                                    {{ __('messages.quotation_converted_to_sales') }}
                                    <a href="{{ route('admin.sales.show', $quotation->converted_to_sales_id) }}" class="alert-link">
                                        {{ __('messages.view_sales_order') }} #{{ $quotation->convertedToSales->invoice }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
