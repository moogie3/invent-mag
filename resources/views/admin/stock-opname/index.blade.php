@extends('admin.layouts.base')

@section('title', __('messages.stock_opname') ?? 'Stock Opname')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}" 
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            {{ __('messages.warehouse') }}
                        </div>
                        <h2 class="page-title d-flex align-items-center">
                            <i class="ti ti-clipboard-check me-2"></i> {{ __('messages.stock_opname') }}
                            <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                                <i class="ti ti-help-circle fs-3"></i>
                            </a>
                        </h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <a href="{{ route('admin.stock-opname.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                                <i class="ti ti-plus me-2"></i>
                                {{ __('messages.new_stock_opname') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}" 
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('messages.stock_opname') }}</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.stock_opname_ref') }}</th>
                                    <th>{{ __('messages.stock_opname_warehouse') }}</th>
                                    <th>{{ __('messages.stock_opname_status') }}</th>
                                    <th>{{ __('messages.stock_opname_scheduled_date') }}</th>
                                    <th>{{ __('messages.stock_opname_created_by') }}</th>
                                    <th class="w-1">{{ __('messages.action') ?? 'Action' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($opnames as $opname)
                                    <tr>
                                        <td><strong>{{ $opname->reference_number }}</strong></td>
                                        <td>{{ $opname->warehouse->name }}</td>
                                        <td>
                                            @if($opname->status === 'Draft')
                                                <span class="badge bg-secondary text-white">{{ $opname->status }}</span>
                                            @elseif($opname->status === 'In Progress')
                                                <span class="badge bg-warning text-white">{{ $opname->status }}</span>
                                            @elseif($opname->status === 'Completed')
                                                <span class="badge bg-success text-white">{{ $opname->status }}</span>
                                            @else
                                                <span class="badge bg-danger text-white">{{ $opname->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $opname->scheduled_date->format('d M Y') }}</td>
                                        <td>
                                            {{ $opname->createdBy->name }}<br>
                                            <span class="badge bg-blue-lt mt-1">{{ $opname->createdBy->getRoleNames()->first() ?? 'User' }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.stock-opname.show', $opname->id) }}" class="btn btn-icon btn-ghost-primary" title="{{ __('messages.view') ?? 'View' }}">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No stock opnames found. Click "{{ __('messages.new_stock_opname') }}" to create one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($opnames->hasPages())
                        <div class="card-footer d-flex align-items-center">
                            {{ $opnames->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection