@extends('admin.layouts.base')

@section('title', __('messages.hr_payroll'))

@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="{{ $containerClass ?? 'container-xl' }}">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <h2 class="mb-0"><i class="ti ti-wallet me-2"></i>{{ __('messages.hr_payroll') }}</h2>
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
                                                    <i class="ti ti-wallet fs-1 me-3 text-primary"></i>
                                                    <div>
                                                        <h2 class="mb-1">{{ __('messages.hr_payroll') }}</h2>
                                                        <div class="text-muted">
                                                            {{ __('messages.hr_total_runs') }} <strong>{{ $runs->count() }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table card-table table-vcenter">
                                                <thead style="font-size: large">
                                                    <tr>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-period">{{ __('messages.period') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-status">{{ __('messages.status') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-run-date">{{ __('messages.hr_run_date') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-paid-date">{{ __('messages.hr_paid_date') }}</button></th>
                                                        <th style="width:180px;text-align:center" class="fs-4 py-3">{{ __('messages.table_action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-tbody">
                                                    @forelse($runs as $run)
                                                        <tr>
                                                            <td class="sort-period">{{ $run->period->year }}-{{ str_pad($run->period->month, 2, '0', STR_PAD_LEFT) }}</td>
                                                            <td class="sort-status">
                                                                @switch($run->status)
                                                                    @case('draft')
                                                                        <span class="badge bg-warning">{{ __('messages.draft') }}</span>
                                                                        @break
                                                                    @case('paid')
                                                                        <span class="badge bg-success">{{ __('messages.paid') }}</span>
                                                                        @break
                                                                    @default
                                                                        <span class="badge bg-secondary">{{ $run->status }}</span>
                                                                @endswitch
                                                            </td>
                                                            <td class="sort-run-date">{{ $run->run_date ? $run->run_date->format('Y-m-d') : '-' }}</td>
                                                            <td class="sort-paid-date">{{ $run->paid_date ? $run->paid_date->format('Y-m-d') : '-' }}</td>
                                                            <td style="text-align:center">
                                                                <div class="btn-list flex-nowrap justify-content-center">
                                                                    <a href="{{ route('admin.hr.payroll.show', $run) }}" class="btn btn-icon btn-ghost-primary" title="{{ __('messages.view') }}">
                                                                        <i class="ti ti-eye"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5">
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
@endsection