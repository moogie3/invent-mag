@extends('admin.layouts.base')

@section('title', __('messages.stock_opname_view') . ': ' . $stockOpname->reference_number)

@section('header')
    @vite('resources/css/stock-opname.css')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}" 
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            <a href="{{ route('admin.stock-opname.index') }}" class="text-muted text-decoration-none">
                                <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                        <h2 class="page-title d-flex align-items-center">
                            <i class="ti ti-clipboard-check me-2"></i> {{ $stockOpname->reference_number }}
                            @if($stockOpname->status === 'Draft')
                                <span class="badge bg-secondary text-white ms-3">{{ __('messages.draft') }}</span>
                            @elseif($stockOpname->status === 'In Progress')
                                <span class="badge bg-warning text-white ms-3">{{ __('messages.in_progress') }}</span>
                            @elseif($stockOpname->status === 'Completed')
                                <span class="badge bg-success text-white ms-3">{{ __('messages.completed') }}</span>
                            @else
                                <span class="badge bg-danger text-white ms-3">{{ __('messages.canceled') }}</span>
                            @endif
                            <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                                <i class="ti ti-help-circle fs-3"></i>
                            </a>
                        </h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <button type="button" class="btn btn-outline-secondary" data-print-sheet>
                                <i class="ti ti-printer me-2"></i> {{ __('messages.print') }}
                            </button>
                            @if($stockOpname->status !== 'Completed' && $stockOpname->status !== 'Canceled')
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelOpnameModal" data-action-url="{{ route('admin.stock-opname.cancel', $stockOpname->id) }}">
                                    {{ __('messages.cancel_opname') }}
                                </button>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeOpnameModal" data-action-url="{{ route('admin.stock-opname.complete', $stockOpname->id) }}" data-preview-url="{{ route('admin.stock-opname.complete-preview', $stockOpname->id) }}">
                                    <i class="ti ti-check me-2"></i> {{ __('messages.complete_post_adjustments') }}
                                </button>
                            @elseif($stockOpname->status === 'Completed')
                                {{-- The completion just wrote StockAdjustment rows for this
                                     warehouse; this is the only way from here to the log
                                     that holds them. Pre-filtered to the warehouse so the
                                     entries of this count are what opens. --}}
                                <a href="{{ route('admin.reports.adjustment-log', ['warehouse_id' => $stockOpname->warehouse_id]) }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-file-text me-2"></i> {{ __('messages.view_adjustment_log') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}" 
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                {{-- The page header is d-print-none, so the sheet prints
                     with its own masthead: what was counted, where, and
                     two lines to sign it off. --}}
                <div class="d-none d-print-block mb-3">
                    <h3 class="mb-1">{{ __('messages.stock_opname_view') }}: {{ $stockOpname->reference_number }}</h3>
                    <div>
                        {{ __('messages.warehouse') }}: {{ $stockOpname->warehouse->name }}
                        &nbsp;|&nbsp; {{ __('messages.scheduled_date') }}: {{ $stockOpname->scheduled_date->format('d M Y') }}
                    </div>
                    @if($stockOpname->notes)
                        <div>{{ __('messages.notes') }}: {{ $stockOpname->notes }}</div>
                    @endif
                    <div class="d-flex gap-5 mt-4">
                        <div>{{ __('messages.counted_by') }}: ____________________</div>
                        <div>{{ __('messages.verified_by') }}: ____________________</div>
                    </div>
                </div>
                <div class="row row-cards">
                    <div class="col-md-3 order-2">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('messages.stock_opname_details') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="text-muted">{{ __('messages.warehouse') }}</div>
                                    <div class="fw-bold">{{ $stockOpname->warehouse->name }}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted">{{ __('messages.scheduled_date') }}</div>
                                    <div class="fw-bold">{{ $stockOpname->scheduled_date->format('d M Y') }}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted">{{ __('messages.stock_opname_created_by') }}</div>
                                    <div class="fw-bold">
                                        {{ $stockOpname->createdBy->name }}
                                        <span class="badge bg-blue-lt ms-2">{{ $stockOpname->createdBy->getRoleNames()->first() ?? __('messages.user') }}</span>
                                    </div>
                                </div>
                                @if($stockOpname->completed_at)
                                <div class="mb-3">
                                    <div class="text-muted">{{ __('messages.completed_at') }}</div>
                                    <div class="fw-bold">{{ $stockOpname->completed_at->format('d M Y H:i') }}</div>
                                </div>
                                @endif
                                @if($stockOpname->notes)
                                <div class="mb-3">
                                    <div class="text-muted">{{ __('messages.notes') }}</div>
                                    <div class="fw-bold">{{ $stockOpname->notes }}</div>
                                </div>
                                @endif
                                <hr class="my-3">
                                <div class="mb-3" data-counting-progress>
                                    <div class="text-muted mb-1"
                                         data-counting-progress-text
                                         data-template="{{ __('messages.counting_progress', ['counted' => ':counted', 'total' => ':total']) }}">{{ __('messages.counting_progress', ['counted' => (int) $totals->counted_rows, 'total' => (int) $totals->row_count]) }}</div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" data-counting-progress-bar role="progressbar"
                                             style="width: {{ $totals->row_count > 0 ? round($totals->counted_rows * 100 / $totals->row_count) : 0 }}%"
                                             aria-valuenow="{{ (int) $totals->counted_rows }}" aria-valuemin="0" aria-valuemax="{{ (int) $totals->row_count }}"></div>
                                    </div>
                                </div>
                                <div data-totals-block @if((int) $totals->counted_rows === 0) hidden @endif>
                                    <div class="text-muted mb-1">{{ __('messages.stock_opname_summary') }}</div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('messages.total') }} {{ __('messages.expected_qty') }}</span>
                                        <span class="fw-bold">{{ $totals->expected_total }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('messages.total') }} {{ __('messages.physical_count') }}</span>
                                        <span class="fw-bold" data-total-counted>{{ $totals->counted_total }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('messages.total') }} {{ __('messages.variance') }}</span>
                                        <span class="fw-bold" data-total-variance>{{ $totals->variance_total > 0 ? '+' : '' }}{{ $totals->variance_total }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('messages.excess_value') }}</span>
                                        <span class="fw-bold text-success" data-total-excess>{{ \App\Helpers\CurrencyHelper::format($totals->excess_total) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('messages.shrinkage_value') }}</span>
                                        <span class="fw-bold text-danger" data-total-shrinkage>{{ \App\Helpers\CurrencyHelper::format($totals->shrinkage_total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-9 order-1">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <h3 class="card-title me-2">{{ __('messages.counting_sheet') }}</h3>
                                    <div class="btn-group btn-group-sm d-print-none" role="group">
                                        <a href="{{ $allUrl }}" class="btn {{ $view === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('messages.all_rows') }}</a>
                                        <a href="{{ $uncountedUrl }}" class="btn {{ $view === 'uncounted' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('messages.uncounted') }}</a>
                                        <a href="{{ $varianceUrl }}" class="btn {{ $view === 'variance' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('messages.variance_only') }}</a>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap d-print-none">
                                    <form action="{{ route('admin.stock-opname.show', $stockOpname->id) }}" method="GET" class="d-flex gap-2">
                                        @if($view !== 'all')
                                            <input type="hidden" name="view" value="{{ $view }}">
                                        @endif
                                        <input type="search" name="q" value="{{ $search }}"
                                               class="form-control form-control-sm" style="min-width: 240px;"
                                               placeholder="{{ __('messages.scan_barcode') }}"
                                               data-scan-input data-search-input>
                                    </form>
                                    @if($stockOpname->status !== 'Completed' && $stockOpname->status !== 'Canceled')
                                        <span class="text-muted small" data-count-state hidden></span>
                                        <button type="submit" form="countsForm" class="btn btn-primary btn-sm">
                                            <i class="ti ti-device-floppy me-2"></i> {{ __('messages.save_counts') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="table-responsive">
                                <form id="countsForm" action="{{ route('admin.stock-opname.update-counts', $stockOpname->id) }}" method="POST"
                                      data-saving="{{ __('messages.saving') }}"
                                      data-saved="{{ __('messages.saved') }}"
                                      data-failed="{{ __('messages.save_failed') }}">
                                    @csrf
                                    <table class="table table-vcenter card-table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('messages.product_code') }}</th>
                                                <th>{{ __('messages.product_name') }}</th>
                                                <th class="text-center">{{ __('messages.expected_qty') }}</th>
                                                <th class="text-center" style="width: 150px;">{{ __('messages.physical_count') }}</th>
                                                <th class="text-center">{{ __('messages.variance') }}</th>
                                                <th class="text-center">{{ __('messages.variance_value') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($items as $item)
                                                <tr data-product-row="{{ $item->product_id }}"
                                                    data-barcode="{{ $item->product->barcode }}"
                                                    class="{{ $item->variance > 0 ? 'table-success' : ($item->variance < 0 ? 'table-danger' : '') }}">
                                                    <td><strong>{{ $item->product->code }}</strong></td>
                                                    <td>{{ $item->product->name }}</td>
                                                    <td class="text-center">{{ $item->expected_qty }}</td>
                                                    <td class="text-center">
                                                        @if($stockOpname->status !== 'Completed' && $stockOpname->status !== 'Canceled')
                                                            <input type="number" step="1" min="0" class="form-control text-center" 
                                                                name="counts[{{ $item->product_id }}]" 
                                                                value="{{ $item->counted_qty }}" placeholder="{{ __('messages.physical_count') }}">
                                                        @else
                                                            <span class="fw-bold">{{ $item->counted_qty ?? '-' }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center fw-bold" data-variance-cell>
                                                        <span data-variance-text class="{{ $item->variance > 0 ? 'text-success' : ($item->variance < 0 ? 'text-danger' : 'text-muted') }}">{{ $item->variance === null ? '-' : ($item->variance > 0 ? '+'.$item->variance : $item->variance) }}</span>
                                                    </td>
                                                    <td class="text-center" data-variance-value-cell>
                                                        {{-- The money figure the completion journal
                                                             will book for this line: what the service
                                                             already computed, now where the count is
                                                             actually read. Null until the row is
                                                             counted. --}}
                                                        @if($item->variance_value !== null)
                                                            {{ \App\Helpers\CurrencyHelper::format($item->variance_value) }}
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            @if($items->isEmpty())
                                                <tr>
                                                    <td colspan="6" class="text-center text-secondary py-4">
                                                        @if($search === '' && $view === 'variance')
                                                            {{ __('messages.no_variance_rows') }}
                                                        @elseif($search === '' && $view === 'uncounted')
                                                            {{ __('messages.no_uncounted_rows') }}
                                                        @else
                                                            {{ __('messages.no_data') }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </form>
                            </div>
                            <div data-pagination-footer class="card-footer d-flex align-items-center">
                                <p class="m-0 text-secondary">
                                    {{ __('messages.pagination_showing_entries', ['first' => $items->firstItem() ?? 0, 'last' => $items->lastItem() ?? 0, 'total' => $items->total()]) }}
                                </p>
                                <div class="ms-auto">
                                    {{ $items->appends(request()->query())->links('vendor.pagination.tabler') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.layouts.modals.stock-opname.stockopnamemodals')
@endsection