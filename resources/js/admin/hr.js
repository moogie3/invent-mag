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
                }
            });
        });
    });
}

function initPayroll() {
    if (window.addAllowance) return;

    window.addAllowance = function(itemId) {
        const container = document.getElementById(`allowances-container${itemId}`);
        if (!container) return;
        const idx = container.querySelectorAll('.row').length;
        const div = document.createElement('div');
        div.className = 'row mb-2';
        div.innerHTML = `
            <div class="col-6">
                <input type="text" name="allowances[${idx}][label]" class="form-control" placeholder="Label">
            </div>
            <div class="col-4">
                <input type="number" name="allowances[${idx}][amount]" class="form-control" value="0" step="0.01">
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

    window.addDeduction = function(itemId) {
        const container = document.getElementById(`deductions-container${itemId}`);
        if (!container) return;
        const idx = container.querySelectorAll('.row').length;
        const div = document.createElement('div');
        div.className = 'row mb-2';
        div.innerHTML = `
            <div class="col-6">
                <input type="text" name="deductions[${idx}][label]" class="form-control" placeholder="Label">
            </div>
            <div class="col-4">
                <input type="number" name="deductions[${idx}][amount]" class="form-control" value="0" step="0.01">
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