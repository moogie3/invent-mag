@extends('admin.layouts.base')

@section('title', __('messages.hr_attendance'))

@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="{{ $containerClass ?? 'container-xl' }}">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <h2 class="mb-0"><i class="ti ti-calendar-check me-2"></i>{{ __('messages.hr_attendance') }}</h2>
                        <a href="#" class="text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </div>
                    <hr class="my-0">
                    <div class="row g-0">
                        <div class="col-12 col-md-3 border-end">
                            @include('admin.layouts.menu')
                        </div>
                        <div class="col-12 col-md-9 d-flex flex-column">
                            <div class="row row-deck row-cards">
                                <div class="col-md-12">
                                    <div class="card border-0 shadow-none rounded-3">
                                        <div class="card-body border-bottom py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-calendar-check fs-1 me-3 text-primary"></i>
                                                    <div>
                                                        <h2 class="mb-1">{{ __('messages.hr_attendance') }}</h2>
                                                        <div class="text-muted">
                                                            {{ __('messages.hr_total_periods') }} <strong>{{ $periods->count() }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="btn-list">
                                                    <button class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#openPeriodModal">
                                                        <i class="ti ti-plus fs-4"></i> {{ __('messages.hr_open_period') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table card-table table-vcenter">
                                                <thead style="font-size: large">
                                                    <tr>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-period">{{ __('messages.period') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-status">{{ __('messages.status') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-closed">{{ __('messages.hr_close_period') }}</button></th>
                                                        <th style="width:180px;text-align:center" class="fs-4 py-3">{{ __('messages.table_action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-tbody">
                                                    @forelse($periods as $period)
                                                        <tr>
                                                            <td class="sort-period">{{ $period->year }}-{{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}</td>
                                                            <td class="sort-status">
                                                                @if($period->status === 'open')
                                                                    <span class="badge bg-success">{{ __('messages.open') }}</span>
                                                                @else
                                                                    <span class="badge bg-secondary">{{ __('messages.closed') }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="sort-closed">{{ $period->closed_at ? $period->closed_at->format('Y-m-d') : '-' }}</td>
                                                            <td style="text-align:center">
                                                                <div class="btn-list flex-nowrap justify-content-center">
                                                                    @if($period->status === 'open')
                                                                        <a href="{{ route('admin.hr.attendance.show', $period) }}" class="btn btn-icon btn-ghost-primary" title="{{ __('messages.view') }}">
                                                                            <i class="ti ti-eye"></i>
                                                                        </a>
                                                                    @else
                                                                        <span class="text-muted">-</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4">
                                                                <div class="empty">
                                                                    <div class="empty-img">
                                                                        <i class="ti ti-mood-sad" style="font-size: 5rem; color: #ccc;"></i>
                                                                    </div>
                                                                    <p class="empty-title">{{ __('messages.no_data') }}</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur" id="openPeriodModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.hr_open_period') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('admin.hr.attendance.open-period') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('messages.year') }}</label>
                            <input type="number" name="year" class="form-control" value="{{ date('Y') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('messages.month') }}</label>
                            <input type="number" name="month" class="form-control" value="{{ date('n') }}" min="1" max="12" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection