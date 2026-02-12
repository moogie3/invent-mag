@extends('admin.layouts.base')

@section('title', __('messages.adjustment_log'))

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            {{ __('messages.reports') }}
                        </div>
                        <h2 class="page-title">
                            <i class="ti ti-file-text me-2"></i> {{ __('messages.adjustment_log') }}
                        </h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.adjustment_log') }}</h3>
                        <div class="ms-auto">
                            <div class="btn-list">
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle" type="button"
                                        id="exportAdjustmentLogDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-printer me-2"></i> {{ __('messages.export') }}
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="exportAdjustmentLogDropdown">
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="exportAdjustmentLog('pdf')">
                                                Export as PDF
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="exportAdjustmentLog('csv')">
                                                Export as CSV
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead style="font-size: large">
                                <tr>
                                    <th class="fs-4 py-3">{{ __('messages.date') }}</th>
                                    <th class="fs-4 py-3">{{ __('messages.product') }}</th>
                                    <th class="fs-4 py-3">{{ __('messages.type') }}</th>
                                    <th class="fs-4 py-3">{{ __('messages.quantity_before') }}</th>
                                    <th class="fs-4 py-3">{{ __('messages.quantity_after') }}</th>
                                    <th class="fs-4 py-3">{{ __('messages.change') }}</th>
                                    <th class="fs-4 py-3">{{ __('messages.reason_for_adjustment') }}</th>
                                    <th class="fs-4 py-3">{{ __('messages.adjusted_by') }}</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($adjustments as $log)
                                    <tr>
                                        <td>{{ $log->created_at->translatedFormat('d M Y, H:i') }}</td>
                                        <td>
                                            @if ($log->product)
                                                <a
                                                    href="{{ route('admin.product.edit', $log->product->id) }}">{{ $log->product->name }}</a>
                                            @else
                                                {{ __('messages.product_not_found') }}
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-lt">{{ $log->adjustment_type }}</span>
                                        </td>
                                        <td>{{ $log->quantity_before }}</td>
                                        <td>{{ $log->quantity_after }}</td>
                                        @php
                                            $change = $log->quantity_after - $log->quantity_before;
                                            $changeClass =
                                                $change > 0
                                                    ? 'text-success'
                                                    : ($change < 0
                                                        ? 'text-danger'
                                                        : 'text-muted');
                                            $changeSign = $change > 0 ? '+' : '';
                                        @endphp
                                        <td>
                                            <span
                                                class="{{ $changeClass }}">{{ $changeSign }}{{ $change }}</span>
                                        </td>
                                        <td>{{ $log->reason ?: 'N/A' }}</td>
                                        <td>{{ $log->adjustedBy ? $log->adjustedBy->name : __('messages.system') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <div class="empty">
                                                <div class="empty-img">
                                                    <i class="ti ti-file-text" style="font-size: 3rem;"></i>
                                                </div>
                                                <p class="empty-title">{{ __('messages.no_stock_adjustments_found') }}</p>
                                                <p class="empty-subtitle text-muted">
                                                    {{ __('messages.no_stock_adjustments_found_message') }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($adjustments->hasPages())
                        <div class="card-footer d-flex align-items-center">
                            {{ $adjustments->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
