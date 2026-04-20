@extends('admin.layouts.base')

@section('title', __('messages.stock_opname_view') . ': ' . $stockOpname->reference_number)

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
                            @if($stockOpname->status !== 'Completed' && $stockOpname->status !== 'Canceled')
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelOpnameModal" data-action-url="{{ route('admin.stock-opname.cancel', $stockOpname->id) }}">
                                    {{ __('messages.cancel_opname') }}
                                </button>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeOpnameModal" data-action-url="{{ route('admin.stock-opname.complete', $stockOpname->id) }}">
                                    <i class="ti ti-check me-2"></i> {{ __('messages.complete_post_adjustments') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}" 
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
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
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-9 order-1">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title">{{ __('messages.counting_sheet') }}</h3>
                                @if($stockOpname->status !== 'Completed' && $stockOpname->status !== 'Canceled')
                                    <button type="submit" form="countsForm" class="btn btn-primary btn-sm">
                                        <i class="ti ti-device-floppy me-2"></i> {{ __('messages.save_counts') }}
                                    </button>
                                @endif
                            </div>
                            <div class="table-responsive">
                                <form id="countsForm" action="{{ route('admin.stock-opname.update-counts', $stockOpname->id) }}" method="POST">
                                    @csrf
                                    <table class="table table-vcenter card-table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('messages.product_code') }}</th>
                                                <th>{{ __('messages.product_name') }}</th>
                                                <th class="text-center">{{ __('messages.expected_qty') }}</th>
                                                <th class="text-center" style="width: 150px;">{{ __('messages.physical_count') }}</th>
                                                <th class="text-center">{{ __('messages.variance') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($items as $item)
                                                <tr>
                                                    <td><strong>{{ $item->product->code }}</strong></td>
                                                    <td>{{ $item->product->name }}</td>
                                                    <td class="text-center">{{ $item->expected_qty }}</td>
                                                    <td class="text-center">
                                                        @if($stockOpname->status !== 'Completed' && $stockOpname->status !== 'Canceled')
                                                            <input type="number" step="0.01" class="form-control text-center" 
                                                                name="counts[{{ $item->product_id }}]" 
                                                                value="{{ $item->counted_qty }}" placeholder="{{ __('messages.physical_count') }}">
                                                        @else
                                                            <span class="fw-bold">{{ $item->counted_qty ?? '-' }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center fw-bold">
                                                        @if($item->variance !== null)
                                                            @if($item->variance > 0)
                                                                <span class="text-success">+{{ $item->variance }}</span>
                                                            @elseif($item->variance < 0)
                                                                <span class="text-danger">{{ $item->variance }}</span>
                                                            @else
                                                                <span class="text-muted">0</span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </form>
                            </div>
                            @if ($items->hasPages())
                                <div class="card-footer d-flex align-items-center">
                                    {{ $items->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.layouts.modals.stock-opname.stockopnamemodals')
@endsection