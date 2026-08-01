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
                                            <form method="POST" action="{{ $employee ? route('admin.hr.employee.update', $employee) : route('admin.hr.employee.store') }}" novalidate data-validate-form>
                                                @csrf
                                                @if($employee) @method('PUT') @endif
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold" for="employee_code">{{ __('messages.hr_employee_code') }} <span class="text-danger">*</span></label>
                                                            <input type="text" name="employee_code" id="employee_code" class="form-control @error('employee_code') is-invalid @enderror" value="{{ old('employee_code', $employee->employee_code ?? '') }}" required>
                                                            @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold" for="name">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                                                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $employee->name ?? '') }}" required>
                                                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold" for="department">{{ __('messages.hr_department') }}</label>
                                                            <input type="text" name="department" id="department" class="form-control @error('department') is-invalid @enderror" value="{{ old('department', $employee->department ?? '') }}">
                                                            @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold" for="position">{{ __('messages.hr_position') }}</label>
                                                            <input type="text" name="position" id="position" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $employee->position ?? '') }}">
                                                            @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold" for="employment_type">{{ __('messages.hr_employment_type') }} <span class="text-danger">*</span></label>
                                                            <select name="employment_type" id="employment_type" class="form-select @error('employment_type') is-invalid @enderror" required>
                                                                <option value="permanent" {{ old('employment_type', $employee->employment_type ?? '') === 'permanent' ? 'selected' : '' }}>{{ __('messages.hr_permanent') }}</option>
                                                                <option value="contract" {{ old('employment_type', $employee->employment_type ?? '') === 'contract' ? 'selected' : '' }}>{{ __('messages.hr_contract') }}</option>
                                                                <option value="part_time" {{ old('employment_type', $employee->employment_type ?? '') === 'part_time' ? 'selected' : '' }}>{{ __('messages.hr_part_time') }}</option>
                                                            </select>
                                                            @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold" for="join_date">{{ __('messages.hr_join_date') }} <span class="text-danger">*</span></label>
                                                            <input type="date" name="join_date" id="join_date" class="form-control @error('join_date') is-invalid @enderror" value="{{ old('join_date', $employee->join_date ?? '') }}" required>
                                                            @error('join_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold" for="base_salary">{{ __('messages.hr_base_salary') }} <span class="text-danger">*</span></label>
                                                            <input type="number" name="base_salary" id="base_salary" class="form-control @error('base_salary') is-invalid @enderror" value="{{ old('base_salary', $employee->base_salary ?? 0) }}" step="0.01" required>
                                                            @error('base_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold" for="status">{{ __('messages.status') }} <span class="text-danger">*</span></label>
                                                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                                                <option value="active" {{ old('status', $employee->status ?? 'active') === 'active' ? 'selected' : '' }}>{{ __('messages.hr_active') }}</option>
                                                                <option value="inactive" {{ old('status', $employee->status ?? '') === 'inactive' ? 'selected' : '' }}>{{ __('messages.hr_inactive') }}</option>
                                                                <option value="resigned" {{ old('status', $employee->status ?? '') === 'resigned' ? 'selected' : '' }}>{{ __('messages.hr_resigned') }}</option>
                                                                <option value="terminated" {{ old('status', $employee->status ?? '') === 'terminated' ? 'selected' : '' }}>{{ __('messages.hr_terminated') }}</option>
                                                            </select>
                                                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold" for="bank_name">{{ __('messages.hr_bank_name') }}</label>
                                                            <input type="text" name="bank_name" id="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name', $employee->bank_name ?? '') }}">
                                                            @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold" for="bank_account">{{ __('messages.hr_bank_account') }}</label>
                                                            <input type="text" name="bank_account" id="bank_account" class="form-control @error('bank_account') is-invalid @enderror" value="{{ old('bank_account', $employee->bank_account ?? '') }}">
                                                            @error('bank_account')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold" for="notes">{{ __('messages.notes') }}</label>
                                                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $employee->notes ?? '') }}</textarea>
                                                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
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