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
                                                <div class="btn-list">
                                                    <div class="dropdown d-inline-block me-2">
                                                        <button class="btn btn-secondary dropdown-toggle" type="button"
                                                            id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                                                        </button>
                                                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                                            <li>
                                                                <a class="dropdown-item" href="#" data-onclick="exportPayrollRuns('pdf'); return false;">
                                                                    {{ __('messages.export_as_pdf') }}
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" data-onclick="exportPayrollRuns('csv'); return false;">
                                                                    {{ __('messages.export_as_csv') }}
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <button class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#runPayrollModal">
                                                        <i class="ti ti-player-play fs-4"></i> Run Payroll
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="invoiceTableContainer">
                                        <div class="table-responsive">
                                            <table class="table card-table table-vcenter">
                                                <thead style="font-size: large">
                                                    <tr>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-runperiod">{{ __('messages.period') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-status">{{ __('messages.status') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-run-date">{{ __('messages.hr_run_date') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-paid-date">{{ __('messages.hr_paid_date') }}</button></th>
                                                        <th style="width:180px;text-align:center" class="fs-4 py-3">{{ __('messages.table_action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-tbody">
                                                    @forelse($runs as $run)
                                                        <tr>
                                                            <td class="sort-runperiod" data-date="{{ $run->period->year }}-{{ str_pad($run->period->month, 2, '0', STR_PAD_LEFT) }}-01">{{ \Carbon\Carbon::createFromDate($run->period->year, $run->period->month, 1)->translatedFormat('F Y') }}</td>
                                                            <td class="sort-status">
                                                                @switch($run->status)
                                                                    @case('draft')
                                                                        <span class="badge bg-warning text-white">{{ __('messages.draft') }}</span>
                                                                        @break
                                                                    @case('processed')
                                                                        <span class="badge bg-info text-white">Processed</span>
                                                                        @break
                                                                    @case('paid')
                                                                        <span class="badge bg-success text-white">{{ __('messages.paid') }}</span>
                                                                        @break
                                                                    @default
                                                                        <span class="badge bg-secondary text-white">{{ ucfirst($run->status) }}</span>
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
    </div>

    <div class="modal modal-blur fade" id="runPayrollModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">Run Payroll</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="runPayrollForm">
                    @csrf
                    <div class="modal-body">
                        @if($availablePeriods->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Attendance Period</label>
                            <select name="period_id" id="period_id" class="form-select" required data-onchange="document.getElementById('runPayrollForm').action = '/admin/hr/payroll/run/' + this.value;">
                                <option value="" disabled selected>-- Select a period --</option>
                                @foreach($availablePeriods as $period)
                                    <option value="{{ $period->id }}">{{ \Carbon\Carbon::createFromDate($period->year, $period->month, 1)->translatedFormat('F Y') }} ({{ ucfirst($period->status) }})</option>
                                @endforeach
                            </select>
                            <div class="form-hint mt-2">Only periods that haven't been run yet are shown. It is recommended to close the period before running payroll.</div>
                        </div>
                        @else
                        <div class="text-center py-4">
                            <i class="ti ti-calendar-off text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">No Periods Available</h5>
                            <p class="text-muted mb-0">All attendance periods have been processed, or none exist yet.</p>
                            <p class="text-muted">Go to <strong>HR → Attendance</strong> to open a new period first.</p>
                            <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-primary mt-3">
                                <i class="ti ti-calendar-plus me-2"></i>Open Attendance Period
                            </a>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                        @if($availablePeriods->isNotEmpty())
                        <button type="submit" class="btn btn-primary"><i class="ti ti-player-play me-2"></i>Run</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection