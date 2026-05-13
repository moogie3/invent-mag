@extends('admin.layouts.base')

@section('title', __('messages.hr_attendance') . ' - ' . \Carbon\Carbon::createFromDate($period->year, $period->month, 1)->translatedFormat('F Y'))

@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="{{ $containerClass ?? 'container-xl' }}">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <h2 class="mb-0">
                            <i class="ti ti-calendar-check me-2"></i>
                            {{ __('messages.hr_attendance') }}
                        </h2>
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
                                                        <h2 class="mb-1">{{ __('messages.hr_attendance') }} - {{ \Carbon\Carbon::createFromDate($period->year, $period->month, 1)->translatedFormat('F Y') }}</h2>
                                                    </div>
                                                </div>
                                                <div class="btn-list">
                                                    <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-secondary">
                                                        <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                                                    </a>
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-secondary dropdown-toggle" type="button"
                                                            id="exportShowDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                                                        </button>
                                                        <ul class="dropdown-menu" aria-labelledby="exportShowDropdown">
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="exportAttendancePeriod({{ $period->id }}, 'pdf'); return false;">
                                                                    {{ __('messages.export_as_pdf') }}
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="exportAttendancePeriod({{ $period->id }}, 'csv'); return false;">
                                                                    {{ __('messages.export_as_csv') }}
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    @if($period->status === 'open')
                                                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#closePeriodModal">
                                                            {{ __('messages.hr_close_period') }}
                                                        </button>
                                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkFillModal">
                                                            <i class="ti ti-select"></i> {{ __('messages.hr_bulk_fill') }}
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th class="sticky-col bg-white">{{ __('messages.hr_employee') }}</th>
                                                            @for($day = 1; $day <= cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year); $day++)
                                                                <th class="text-center {{ in_array(date('N', mktime(0,0,0,$period->month,$day,$period->year)), [6,7]) ? 'table-secondary' : '' }}">{{ $day }}</th>
                                                            @endfor
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($employees as $employee)
                                                            <tr>
                                                                <td class="sticky-col bg-white fw-semibold">{{ $employee->name }}</td>
                                                                @for($day = 1; $day <= cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year); $day++)
                                                                    @php
                                                                        $date = sprintf('%04d-%02d-%02d', $period->year, $period->month, $day);
                                                                        $record = $records->get($employee->id)?->firstWhere('date', $date);
                                                                        $weekday = date('N', mktime(0,0,0,$period->month,$day,$period->year));
                                                                        $isWeekend = in_array($weekday, [6,7]);
                                                                    @endphp
                                                                    <td class="text-center {{ $isWeekend ? 'table-secondary' : '' }}"
                                                                        @if($period->status === 'open')
                                                                        contenteditable="true"
                                                                        data-employee="{{ $employee->id }}"
                                                                        data-date="{{ $date }}"
                                                                        data-period="{{ $period->id }}"
                                                                        @endif
                                                                    >
                                                                        @if($record)
                                                                            @switch($record->status)
                                                                                @case('present')
                                                                                    <span class="badge bg-success text-white">P</span>
                                                                                    @break
                                                                                @case('absent')
                                                                                    <span class="badge bg-danger text-white">A</span>
                                                                                    @break
                                                                                @case('sick')
                                                                                    <span class="badge bg-warning text-white">S</span>
                                                                                    @break
                                                                                @case('leave')
                                                                                    <span class="badge bg-info text-white">L</span>
                                                                                    @break
                                                                                @case('holiday')
                                                                                    <span class="badge bg-secondary text-white">H</span>
                                                                                    @break
                                                                                @case('half_day')
                                                                                    <span class="badge bg-primary text-white">½</span>
                                                                                    @break
                                                                                @default
                                                                                    <span class="text-muted">-</span>
                                                                            @endswitch
                                                                        @else
                                                                            @if($period->status === 'open')
                                                                                <span class="text-muted">-</span>
                                                                            @endif
                                                                        @endif
                                                                    </td>
                                                                @endfor
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="{{ cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year) + 1 }}" class="text-center text-muted">
                                                                    {{ __('messages.no_data') }}
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

    @if($period->status === 'open')
    <div class="modal modal-blur fade" id="bulkFillModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.hr_bulk_fill') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('admin.hr.attendance.bulk-fill', $period) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('messages.hr_employee') }}</label>
                            <select name="employee_ids[]" class="form-select" multiple required>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('messages.status') }}</label>
                            <select name="status" class="form-select" required>
                                <option value="present">{{ __('messages.hr_present') }}</option>
                                <option value="absent">{{ __('messages.hr_absent') }}</option>
                                <option value="sick">{{ __('messages.hr_sick') }}</option>
                                <option value="leave">{{ __('messages.hr_leave') }}</option>
                                <option value="holiday">{{ __('messages.hr_holiday') }}</option>
                                <option value="half_day">{{ __('messages.hr_half_day') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('messages.hr_overtime_hours') }}</label>
                            <input type="number" name="overtime_hours" class="form-control" step="0.5" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Skip Days (Off Days)</label>
                            <div>
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="skip_days[]" value="1">
                                    <span class="form-check-label">Mon</span>
                                </label>
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="skip_days[]" value="2">
                                    <span class="form-check-label">Tue</span>
                                </label>
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="skip_days[]" value="3">
                                    <span class="form-check-label">Wed</span>
                                </label>
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="skip_days[]" value="4">
                                    <span class="form-check-label">Thu</span>
                                </label>
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="skip_days[]" value="5">
                                    <span class="form-check-label">Fri</span>
                                </label>
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="skip_days[]" value="6" checked>
                                    <span class="form-check-label">Sat</span>
                                </label>
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="skip_days[]" value="7" checked>
                                    <span class="form-check-label">Sun</span>
                                </label>
                            </div>
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

    <div class="modal modal-blur fade" id="closePeriodModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.hr_close_period') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                    <h3>{{ __('messages.are_you_sure') }}</h3>
                    <div class="text-muted">{{ __('messages.close_period_confirm') }}</div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button type="button" class="btn w-100" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                            </div>
                            <div class="col">
                                <form method="POST" action="{{ route('admin.hr.attendance.close-period', $period) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100">{{ __('messages.hr_close_period') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection