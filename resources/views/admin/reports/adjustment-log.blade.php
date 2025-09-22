@extends('admin.layouts.base')

@section('title', 'Stock Adjustment Log')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Reports
                        </div>
                        <h2 class="page-title">
                            <i class="ti ti-file-text me-2"></i> Stock Adjustment Log
                        </h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Qty Before</th>
                                    <th>Qty After</th>
                                    <th>Change</th>
                                    <th>Reason</th>
                                    <th>Adjusted By</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($adjustments as $log)
                                    <tr>
                                        <td>{{ $log->created_at->format('d M Y, H:i') }}</td>
                                        <td>
                                            @if($log->product)
                                            <a href="{{ route('admin.product.edit', $log->product->id) }}">{{ $log->product->name }}</a>
                                            @else
                                            Product not found
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-lt">{{ $log->adjustment_type }}</span>
                                        </td>
                                        <td>{{ $log->quantity_before }}</td>
                                        <td>{{ $log->quantity_after }}</td>
                                        @php
                                            $change = $log->quantity_after - $log->quantity_before;
                                            $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-danger' : 'text-muted');
                                            $changeSign = $change > 0 ? '+' : '';
                                        @endphp
                                        <td>
                                            <span class="{{ $changeClass }}">{{ $changeSign }}{{ $change }}</span>
                                        </td>
                                        <td>{{ $log->reason ?: 'N/A' }}</td>
                                        <td>{{ $log->adjustedBy ? $log->adjustedBy->name : 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No stock adjustments found.</td>
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
