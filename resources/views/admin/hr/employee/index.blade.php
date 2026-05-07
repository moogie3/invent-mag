@extends('admin.layouts.base')

@section('title', __('messages.hr_employees'))

@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="{{ $containerClass ?? 'container-xl' }}">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <h2 class="mb-0"><i class="ti ti-id-badge me-2"></i>{{ __('messages.hr_employees') }}</h2>
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
                                                    <i class="ti ti-id-badge fs-1 me-3 text-primary"></i>
                                                    <div>
                                                        <h2 class="mb-1">{{ __('messages.hr_employees') }}</h2>
                                                        <div class="text-muted">
                                                            {{ __('messages.hr_total_employees') }} <strong>{{ $employees->count() }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="btn-list">
                                                    <button class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#createEmployeeModal">
                                                        <i class="ti ti-plus fs-4"></i> {{ __('messages.add') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table card-table table-vcenter">
                                                <thead style="font-size: large">
                                                    <tr>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-code">{{ __('messages.hr_employee_code') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-name">{{ __('messages.name') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-dept">{{ __('messages.hr_department') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-position">{{ __('messages.hr_position') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-type">{{ __('messages.hr_employment_type') }}</button></th>
                                                        <th><button class="table-sort fs-4 py-3" data-sort="sort-status">{{ __('messages.status') }}</button></th>
                                                        <th style="width:180px;text-align:center" class="fs-4 py-3">{{ __('messages.table_action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-tbody">
                                                    @forelse($employees as $employee)
                                                        <tr>
                                                            <td class="sort-code">{{ $employee->employee_code }}</td>
                                                            <td class="sort-name">{{ $employee->name }}</td>
                                                            <td class="sort-dept">{{ $employee->department }}</td>
                                                            <td class="sort-position">{{ $employee->position }}</td>
                                                            <td class="sort-type">
                                                                @if($employee->employment_type === 'permanent')
                                                                    <span class="badge bg-success">{{ __('messages.hr_permanent') }}</span>
                                                                @elseif($employee->employment_type === 'contract')
                                                                    <span class="badge bg-warning">{{ __('messages.hr_contract') }}</span>
                                                                @else
                                                                    <span class="badge bg-secondary">{{ __('messages.hr_part_time') }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="sort-status">
                                                                @if($employee->status === 'active')
                                                                    <span class="badge bg-success">{{ __('messages.hr_active') }}</span>
                                                                @elseif($employee->status === 'inactive')
                                                                    <span class="badge bg-secondary">{{ __('messages.hr_inactive') }}</span>
                                                                @elseif($employee->status === 'resigned')
                                                                    <span class="badge bg-warning">{{ __('messages.hr_resigned') }}</span>
                                                                @else
                                                                    <span class="badge bg-danger">{{ __('messages.hr_terminated') }}</span>
                                                                @endif
                                                            </td>
                                                            <td style="text-align:center">
                                                                <div class="btn-list flex-nowrap justify-content-center">
                                                                    <button type="button" class="btn btn-icon btn-ghost-primary"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editEmployeeModal"
                                                                        data-id="{{ $employee->id }}"
                                                                        data-employee_code="{{ $employee->employee_code }}"
                                                                        data-name="{{ $employee->name }}"
                                                                        data-department="{{ $employee->department }}"
                                                                        data-position="{{ $employee->position }}"
                                                                        data-employment_type="{{ $employee->employment_type }}"
                                                                        data-join_date="{{ $employee->join_date }}"
                                                                        data-base_salary="{{ $employee->base_salary }}"
                                                                        data-status="{{ $employee->status }}"
                                                                        data-bank_name="{{ $employee->bank_name }}"
                                                                        data-bank_account="{{ $employee->bank_account }}"
                                                                        data-notes="{{ $employee->notes }}"
                                                                        title="{{ __('messages.edit') }}">
                                                                        <i class="ti ti-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-icon btn-ghost-danger"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#deleteEmployeeModal"
                                                                        data-id="{{ $employee->id }}"
                                                                        data-name="{{ $employee->name }}"
                                                                        title="{{ __('messages.delete') }}">
                                                                        <i class="ti ti-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="7">
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

    <div class="modal modal-blur fade" id="createEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.hr_employee_new') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('admin.hr.employee.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_employee_code') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="employee_code" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_department') }}</label>
                                    <input type="text" name="department" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_position') }}</label>
                                    <input type="text" name="position" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_employment_type') }} <span class="text-danger">*</span></label>
                                    <select name="employment_type" class="form-select" required>
                                        <option value="permanent">{{ __('messages.hr_permanent') }}</option>
                                        <option value="contract">{{ __('messages.hr_contract') }}</option>
                                        <option value="part_time">{{ __('messages.hr_part_time') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_join_date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="join_date" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_base_salary') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="base_salary" class="form-control" step="0.01" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.status') }} <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select" required>
                                        <option value="active">{{ __('messages.hr_active') }}</option>
                                        <option value="inactive">{{ __('messages.hr_inactive') }}</option>
                                        <option value="resigned">{{ __('messages.hr_resigned') }}</option>
                                        <option value="terminated">{{ __('messages.hr_terminated') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_bank_name') }}</label>
                                    <input type="text" name="bank_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_bank_account') }}</label>
                                    <input type="text" name="bank_account" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('messages.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
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

    <div class="modal modal-blur fade" id="editEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.edit') }} {{ __('messages.hr_employee') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editEmployeeForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_employee_code') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="employee_code" id="edit_employee_code" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="edit_name" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_department') }}</label>
                                    <input type="text" name="department" id="edit_department" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_position') }}</label>
                                    <input type="text" name="position" id="edit_position" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_employment_type') }} <span class="text-danger">*</span></label>
                                    <select name="employment_type" id="edit_employment_type" class="form-select" required>
                                        <option value="permanent">{{ __('messages.hr_permanent') }}</option>
                                        <option value="contract">{{ __('messages.hr_contract') }}</option>
                                        <option value="part_time">{{ __('messages.hr_part_time') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_join_date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="join_date" id="edit_join_date" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_base_salary') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="base_salary" id="edit_base_salary" class="form-control" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.status') }} <span class="text-danger">*</span></label>
                                    <select name="status" id="edit_status" class="form-select" required>
                                        <option value="active">{{ __('messages.hr_active') }}</option>
                                        <option value="inactive">{{ __('messages.hr_inactive') }}</option>
                                        <option value="resigned">{{ __('messages.hr_resigned') }}</option>
                                        <option value="terminated">{{ __('messages.hr_terminated') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_bank_name') }}</label>
                                    <input type="text" name="bank_name" id="edit_bank_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">{{ __('messages.hr_bank_account') }}</label>
                                    <input type="text" name="bank_account" id="edit_bank_account" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('messages.notes') }}</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
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

    <div class="modal modal-blur fade" id="deleteEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.delete') }} {{ __('messages.hr_employee') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="deleteEmployeeForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p>{{ __('messages.delete_employee_confirm') }} <strong id="delete_employee_name"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('messages.delete') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editEmployeeModal');
    editModal.addEventListener('show.bs.modal', function(e) {
        const button = e.relatedTarget;
        document.getElementById('edit_id').value = button.dataset.id;
        document.getElementById('edit_employee_code').value = button.dataset.employee_code;
        document.getElementById('edit_name').value = button.dataset.name;
        document.getElementById('edit_department').value = button.dataset.department || '';
        document.getElementById('edit_position').value = button.dataset.position || '';
        document.getElementById('edit_employment_type').value = button.dataset.employment_type;
        document.getElementById('edit_join_date').value = button.dataset.join_date;
        document.getElementById('edit_base_salary').value = button.dataset.base_salary;
        document.getElementById('edit_status').value = button.dataset.status;
        document.getElementById('edit_bank_name').value = button.dataset.bank_name || '';
        document.getElementById('edit_bank_account').value = button.dataset.bank_account || '';
        document.getElementById('edit_notes').value = button.dataset.notes || '';
        document.getElementById('editEmployeeForm').action = '/admin/hr/employee/' + button.dataset.id;
    });

    const deleteModal = document.getElementById('deleteEmployeeModal');
    deleteModal.addEventListener('show.bs.modal', function(e) {
        const button = e.relatedTarget;
        document.getElementById('delete_employee_name').textContent = button.dataset.name;
        document.getElementById('deleteEmployeeForm').action = '/admin/hr/employee/' + button.dataset.id;
    });
});
</script>
@endsection