@extends('admin.layouts.base')

@section('title', __('messages.hr_attendance') . ' ' . $period->year . '-' . str_pad($period->month, 2, '0', STR_PAD_LEFT))

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
                            {{ __('messages.hr_attendance') }} - {{ $period->year }}-{{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}
                        </h2>
                        <div class="btn-list">
                            <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-secondary">
                                <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                            @if($period->status === 'open')
                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#closePeriodModal">
                                    {{ __('messages.hr_close_period') }}
                                </button>
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkFillModal">
                                    <i class="ti ti-select"></i> {{ __('messages.hr_bulk_fill') }}
                                </button>
                            @endif
                        </div>
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
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th class="sticky-col bg-white">{{ __('messages.hr_employee') }}</th>
                                                            @for($day = 1; $day <= cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year); $day++)
                                                                <th class="text-center {{ in_array(date('N', mktime(0,0,0,$period->month,$day,$period->year)), [6,7]) ? 'table-secondary' : '' }}>{{ $day }}</th>
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
                                                                        @if($period->status === 'open' && !$isWeekend)
                                                                        contenteditable="true"
                                                                        data-employee="{{ $employee->id }}"
                                                                        data-date="{{ $date }}"
                                                                        data-period="{{ $period->id }}"
                                                                        @endif
                                                                    >
                                                                        @if($record)
                                                                            @switch($record->status)
                                                                                @case('present')
                                                                                    <span class="badge bg-success">P</span>
                                                                                    @break
                                                                                @case('absent')
                                                                                    <span class="badge bg-danger">A</span>
                                                                                    @break
                                                                                @case('sick')
                                                                                    <span class="badge bg-warning">S</span>
                                                                                    @break
                                                                                @case('leave')
                                                                                    <span class="badge bg-info">L</span>
                                                                                    @break
                                                                                @case('holiday')
                                                                                    <span class="badge bg-secondary">H</span>
                                                                                    @break
                                                                                @case('half_day')
                                                                                    <span class="badge bg-primary">½</span>
                                                                                    @break
                                                                                @default
                                                                                    @if($isWeekend)
                                                                                        <span class="text-muted">-</span>
                                                                                    @endif
                                                                            @endswitch
                                                                        @else
                                                                            @if(!$isWeekend && $period->status === 'open')
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
    <div class="modal modal-blur" id="bulkFillModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur" id="closePeriodModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.hr_close_period') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('admin.hr.attendance.close-period', $period) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <p>{{ __('messages.close_period_confirm') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('messages.hr_close_period') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection