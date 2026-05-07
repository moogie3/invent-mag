import { onPageLoad } from './utils/onPageLoad.js';

onPageLoad(() => {
    initAttendance();
    initPayroll();
}, { id: 'hr' });

function initAttendance() {
    const container = document.querySelector('[data-hr-attendance]');
    if (!container) return;

    document.querySelectorAll('[contenteditable]').forEach(cell => {
        cell.addEventListener('blur', function() {
            const employeeId = this.dataset.employee;
            const date = this.dataset.date;
            const periodId = this.dataset.period;
            const status = this.textContent.trim();

            if (!employeeId || !date || !periodId) return;

            const validStatuses = ['P', 'A', 'S', 'L', 'H', '½'];
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

    document.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.row').remove();
        });
    });
}

function initPayroll() {
    const container = document.querySelector('[data-hr-payroll]');
    if (!container) return;

    window.addAllowance = function(itemId) {
        const container = document.getElementById(`allowances-container${itemId}`);
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
                <button type="button" class="btn btn-danger btn-remove"><i class="ti ti-trash"></i></button>
            </div>
        `;
        container.appendChild(div);
        div.querySelector('.btn-remove').addEventListener('click', function() {
            div.remove();
        });
    };

    window.addDeduction = function(itemId) {
        const container = document.getElementById(`deductions-container${itemId}`);
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
                <button type="button" class="btn btn-danger btn-remove"><i class="ti ti-trash"></i></button>
            </div>
        `;
        container.appendChild(div);
        div.querySelector('.btn-remove').addEventListener('click', function() {
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

document.addEventListener('turbo:before-cache', () => {
    window.addAllowance = undefined;
    window.addDeduction = undefined;
});