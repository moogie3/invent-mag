@extends('admin.layouts.base')

@section('title', $employee ? __('messages.edit') : __('messages.add'))

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
                            <i class="ti ti-id-badge me-2"></i>
                            {{ $employee ? __('messages.edit') : __('messages.add') }} {{ __('messages.hr_employee') }}
                        </h2>
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
                                            <form method="POST" action="{{ $employee ? route('admin.hr.employee.update', $employee) : route('admin.hr.employee.store') }}">
                                                @csrf
                                                @if($employee) @method('PUT') @endif
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">{{ __('messages.hr_employee_code') }} <span class="text-danger">*</span></label>
                                                            <input type="text" name="employee_code" class="form-control" value="{{ old('employee_code', $employee->employee_code ?? '') }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                                                            <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name ?? '') }}" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">{{ __('messages.hr_department') }}</label>
                                                            <input type="text" name="department" class="form-control" value="{{ old('department', $employee->department ?? '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">{{ __('messages.hr_position') }}</label>
                                                            <input type="text" name="position" class="form-control" value="{{ old('position', $employee->position ?? '') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">{{ __('messages.hr_employment_type') }} <span class="text-danger">*</span></label>
                                                            <select name="employment_type" class="form-select" required>
                                                                <option value="permanent" {{ old('employment_type', $employee->employment_type ?? '') === 'permanent' ? 'selected' : '' }}>{{ __('messages.hr_permanent') }}</option>
                                                                <option value="contract" {{ old('employment_type', $employee->employment_type ?? '') === 'contract' ? 'selected' : '' }}>{{ __('messages.hr_contract') }}</option>
                                                                <option value="part_time" {{ old('employment_type', $employee->employment_type ?? '') === 'part_time' ? 'selected' : '' }}>{{ __('messages.hr_part_time') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">{{ __('messages.hr_join_date') }} <span class="text-danger">*</span></label>
                                                            <input type="date" name="join_date" class="form-control" value="{{ old('join_date', $employee->join_date ?? '') }}" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">{{ __('messages.hr_base_salary') }} <span class="text-danger">*</span></label>
                                                            <input type="number" name="base_salary" class="form-control" value="{{ old('base_salary', $employee->base_salary ?? 0) }}" step="0.01" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">{{ __('messages.status') }} <span class="text-danger">*</span></label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="active" {{ old('status', $employee->status ?? 'active') === 'active' ? 'selected' : '' }}>{{ __('messages.hr_active') }}</option>
                                                                <option value="inactive" {{ old('status', $employee->status ?? '') === 'inactive' ? 'selected' : '' }}>{{ __('messages.hr_inactive') }}</option>
                                                                <option value="resigned" {{ old('status', $employee->status ?? '') === 'resigned' ? 'selected' : '' }}>{{ __('messages.hr_resigned') }}</option>
                                                                <option value="terminated" {{ old('status', $employee->status ?? '') === 'terminated' ? 'selected' : '' }}>{{ __('messages.hr_terminated') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">{{ __('messages.hr_bank_name') }}</label>
                                                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $employee->bank_name ?? '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">{{ __('messages.hr_bank_account') }}</label>
                                                            <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account', $employee->bank_account ?? '') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">{{ __('messages.notes') }}</label>
                                                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $employee->notes ?? '') }}</textarea>
                                                </div>
                                                
                                                <div class="mt-4">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="ti ti-device-floppy"></i> {{ __('messages.save') }}
                                                    </button>
                                                    <a href="{{ route('admin.hr.employee.index') }}" class="btn btn-secondary">
                                                        {{ __('messages.cancel') }}
                                                    </a>
                                                </div>
                                            </form>
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