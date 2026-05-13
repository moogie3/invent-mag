import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(() => {
    initEmployeeModals();
    initAttendance();
    initPayroll();
});

function initEmployeeModals() {
    const createModal = document.getElementById('createEmployeeModal');
    if (createModal) {
        createModal.addEventListener('shown.bs.modal', function() {
            const form = createModal.querySelector('form');
            if (form) form.reset();
        });
    }

    document.querySelectorAll('[data-bs-target="#editEmployeeModal"]').forEach(btn => {
        btn.onclick = function(e) {
            // Use getAttribute to avoid dataset naming issues
            const get = (attr) => this.getAttribute(attr) || '';
            
            document.getElementById('edit_id').value = get('data-id');
            document.getElementById('edit_employee_code').value = get('data-employee_code');
            document.getElementById('edit_name').value = get('data-name');
            document.getElementById('edit_department').value = get('data-department');
            document.getElementById('edit_position').value = get('data-position');
            document.getElementById('edit_employment_type').value = get('data-employment_type');
            document.getElementById('edit_join_date').value = get('data-join_date');
            document.getElementById('edit_base_salary').value = get('data-base_salary');
            document.getElementById('edit_status').value = get('data-status');
            document.getElementById('edit_bank_name').value = get('data-bank_name');
            document.getElementById('edit_bank_account').value = get('data-bank_account');
            document.getElementById('edit_notes').value = get('data-notes');
            document.getElementById('editEmployeeForm').action = '/admin/hr/employee/' + get('data-id');

            // Populate allowances
            const allowancesContainer = document.getElementById('edit-allowances-container');
            if (allowancesContainer) {
                allowancesContainer.innerHTML = '';
                try {
                    const allowances = JSON.parse(get('data-allowances'));
                    if (Array.isArray(allowances)) {
                        allowances.forEach((al, idx) => {
                            const div = document.createElement('div');
                            div.className = 'row mb-2';
                            div.innerHTML = `
                                <div class="col-6">
                                    <input type="text" name="default_allowances[${idx}][label]" class="form-control" value="${al.label}" required>
                                </div>
                                <div class="col-4">
                                    <input type="number" name="default_allowances[${idx}][amount]" class="form-control" value="${al.amount}" step="0.01" required>
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-danger btn-remove-row"><i class="ti ti-trash"></i></button>
                                </div>
                            `;
                            allowancesContainer.appendChild(div);
                            div.querySelector('.btn-remove-row').addEventListener('click', function() { div.remove(); });
                        });
                    }
                } catch(e) {}
            }

            // Populate deductions
            const deductionsContainer = document.getElementById('edit-deductions-container');
            if (deductionsContainer) {
                deductionsContainer.innerHTML = '';
                try {
                    const deductions = JSON.parse(get('data-deductions'));
                    if (Array.isArray(deductions)) {
                        deductions.forEach((ded, idx) => {
                            const div = document.createElement('div');
                            div.className = 'row mb-2';
                            div.innerHTML = `
                                <div class="col-6">
                                    <input type="text" name="default_deductions[${idx}][label]" class="form-control" value="${ded.label}" required>
                                </div>
                                <div class="col-4">
                                    <input type="number" name="default_deductions[${idx}][amount]" class="form-control" value="${ded.amount}" step="0.01" required>
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-danger btn-remove-row"><i class="ti ti-trash"></i></button>
                                </div>
                            `;
                            deductionsContainer.appendChild(div);
                            div.querySelector('.btn-remove-row').addEventListener('click', function() { div.remove(); });
                        });
                    }
                } catch(e) {}
            }
        };
    });

    document.querySelectorAll('[data-bs-target="#deleteEmployeeModal"]').forEach(btn => {
        btn.onclick = function() {
            document.getElementById('delete_employee_name').textContent = this.getAttribute('data-name') || '';
            document.getElementById('deleteEmployeeForm').action = '/admin/hr/employee/' + this.getAttribute('data-id');
        };
    });
}

function initAttendance() {
    const editableCells = document.querySelectorAll('[contenteditable]');
    if (!editableCells.length) return;

    editableCells.forEach(cell => {
        if (cell.dataset.handlerAttached) return;
        cell.dataset.handlerAttached = 'true';

        cell.addEventListener('blur', function() {
            const employeeId = this.dataset.employee;
            const date = this.dataset.date;
            const periodId = this.dataset.period;
            const status = this.textContent.trim();

            if (!employeeId || !date || !periodId) return;

            const statusMap = { 'P': 'present', 'A': 'absent', 'S': 'sick', 'L': 'leave', 'H': 'holiday', '½': 'half_day' };
            const mappedStatus = statusMap[status];
            if (!mappedStatus) return;

            fetch('/admin/hr/attendance/record', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    period_id: periodId,
                    date: date,
                    status: mappedStatus
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Record updated');
                    // Update UI with badge
                    const badgeClasses = {
                        'present': 'bg-success text-white',
                        'absent': 'bg-danger text-white',
                        'sick': 'bg-warning text-white',
                        'leave': 'bg-info text-white',
                        'holiday': 'bg-secondary text-white',
                        'half_day': 'bg-primary text-white'
                    };
                    const labels = {
                        'present': 'P',
                        'absent': 'A',
                        'sick': 'S',
                        'leave': 'L',
                        'holiday': 'H',
                        'half_day': '½'
                    };
                    this.innerHTML = `<span class="badge ${badgeClasses[mappedStatus]}">${labels[mappedStatus]}</span>`;
                } else {
                    showToast(data.message || 'Error updating record', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error saving attendance', 'error');
            });
        });
    });
}

function initPayroll() {
    if (window.addAllowance) return;

    window.addAllowance = function(containerId, inputName = 'allowances') {
        // Support old format (itemId) or new format (containerId string)
        const id = typeof containerId === 'number' || !isNaN(containerId) ? `allowances-container${containerId}` : containerId;
        const container = document.getElementById(id);
        if (!container) return;
        const idx = container.querySelectorAll('.row').length;
        const div = document.createElement('div');
        div.className = 'row mb-2';
        div.innerHTML = `
            <div class="col-6">
                <input type="text" name="${inputName}[${idx}][label]" class="form-control" placeholder="Label" required>
            </div>
            <div class="col-4">
                <input type="number" name="${inputName}[${idx}][amount]" class="form-control" value="0" step="0.01" required>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger btn-remove-row"><i class="ti ti-trash"></i></button>
            </div>
        `;
        container.appendChild(div);
        div.querySelector('.btn-remove-row').addEventListener('click', function() {
            div.remove();
        });
    };

    window.addDeduction = function(containerId, inputName = 'deductions') {
        const id = typeof containerId === 'number' || !isNaN(containerId) ? `deductions-container${containerId}` : containerId;
        const container = document.getElementById(id);
        if (!container) return;
        const idx = container.querySelectorAll('.row').length;
        const div = document.createElement('div');
        div.className = 'row mb-2';
        div.innerHTML = `
            <div class="col-6">
                <input type="text" name="${inputName}[${idx}][label]" class="form-control" placeholder="Label" required>
            </div>
            <div class="col-4">
                <input type="number" name="${inputName}[${idx}][amount]" class="form-control" value="0" step="0.01" required>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger btn-remove-row"><i class="ti ti-trash"></i></button>
            </div>
        `;
        container.appendChild(div);
        div.querySelector('.btn-remove-row').addEventListener('click', function() {
            div.remove();
        });
    };
}

function showToast(message) {
    if (typeof window.showToast === 'function') {
        window.showToast(message);
    } else {
        console.log(message);
    }
}

// --- Export functions ---

window.exportEmployees = function (exportOption) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/hr/employee/export';
    form.style.display = 'none';

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrfToken.getAttribute('content');
        form.appendChild(input);
    }

    const optionInput = document.createElement('input');
    optionInput.type = 'hidden';
    optionInput.name = 'export_option';
    optionInput.value = exportOption;
    form.appendChild(optionInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
};

window.exportAttendancePeriods = function (exportOption) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/hr/attendance/export';
    form.style.display = 'none';

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrfToken.getAttribute('content');
        form.appendChild(input);
    }

    const optionInput = document.createElement('input');
    optionInput.type = 'hidden';
    optionInput.name = 'export_option';
    optionInput.value = exportOption;
    form.appendChild(optionInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
};

window.exportPayrollRuns = function (exportOption) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/hr/payroll/export';
    form.style.display = 'none';

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrfToken.getAttribute('content');
        form.appendChild(input);
    }

    const optionInput = document.createElement('input');
    optionInput.type = 'hidden';
    optionInput.name = 'export_option';
    optionInput.value = exportOption;
    form.appendChild(optionInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
};

window.exportAttendancePeriod = function (periodId, exportOption) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/admin/hr/attendance/${periodId}/export`;
    form.style.display = 'none';

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrfToken.getAttribute('content');
        form.appendChild(input);
    }

    const optionInput = document.createElement('input');
    optionInput.type = 'hidden';
    optionInput.name = 'export_option';
    optionInput.value = exportOption;
    form.appendChild(optionInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
};

window.exportPayrollRun = function (runId, exportOption) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/admin/hr/payroll/${runId}/export`;
    form.style.display = 'none';

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrfToken.getAttribute('content');
        form.appendChild(input);
    }

    const optionInput = document.createElement('input');
    optionInput.type = 'hidden';
    optionInput.name = 'export_option';
    optionInput.value = exportOption;
    form.appendChild(optionInput);

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => document.body.removeChild(form), 2000);
};