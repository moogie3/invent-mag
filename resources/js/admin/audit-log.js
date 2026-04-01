document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/admin/audit-log')) {
        return;
    }

    const translations = {
        timestamp: '{{ __("messages.timestamp") }}',
        user: '{{ __("messages.user") }}',
        module: '{{ __("messages.module") }}',
        action: '{{ __("messages.action") }}',
        description: '{{ __("messages.description") }}',
        ip_address: '{{ __("messages.ip_address") }}',
        user_agent: '{{ __("messages.user_agent") }}',
        before: '{{ __("messages.before") }}',
        after: '{{ __("messages.after") }}',
        error_loading_details: '{{ __("messages.error_loading_details") }}',
        close: '{{ __("messages.close") }}'
    };

    const actionBadgeClasses = {
        'create': 'bg-success text-white',
        'update': 'bg-blue text-white',
        'delete': 'bg-danger text-white',
        'login': 'bg-teal text-white',
        'logout': 'bg-secondary text-white',
        'export': 'bg-purple text-white',
        'import': 'bg-indigo text-white',
        'payment': 'bg-yellow text-dark',
        'adjust': 'bg-orange text-white',
        'transfer': 'bg-orange text-white',
        'post': 'bg-cyan text-white',
        'approve': 'bg-cyan text-white',
        'void': 'bg-pink text-white',
        'reject': 'bg-pink text-white',
        'reverse': 'bg-lime text-dark'
    };

    document.querySelectorAll('.btn-view-detail').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var logId = this.dataset.logId;
            var modal = new bootstrap.Modal(document.getElementById('modal-detail'));
            var body = document.getElementById('modal-detail-body');

            body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
            modal.show();

            fetch('/admin/audit-log/' + logId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (r) {
                if (!r.ok) throw new Error('Failed to load');
                return r.json();
            })
            .then(function (data) {
                body.innerHTML = buildDetailHtml(data);
            })
            .catch(function () {
                body.innerHTML = '<div class="alert alert-danger mb-0">' + translations.error_loading_details + '</div>';
            });
        });
    });

    function buildDetailHtml(data) {
        var actionBadgeClass = actionBadgeClasses[data.action] || 'bg-secondary text-white';

        var html = '<div class="table-responsive">';
        html += '<table class="table table-bordered table-sm mb-0">';
        html += '<tr><td class="fw-bold" style="width:150px;">' + translations.timestamp + '</td><td>' + escHtml(data.created_at) + '</td></tr>';
        html += '<tr><td class="fw-bold">' + translations.user + '</td><td>' + escHtml(data.user_name) + (data.user_role ? ' <span class="badge bg-secondary-lt">' + escHtml(data.user_role) + '</span>' : '') + '</td></tr>';
        html += '<tr><td class="fw-bold">' + translations.module + '</td><td><span class="badge bg-azure-lt">' + escHtml(data.module) + '</span></td></tr>';
        html += '<tr><td class="fw-bold">' + translations.action + '</td><td><span class="badge ' + actionBadgeClass + '">' + escHtml(ucFirst(data.action)) + '</span></td></tr>';
        html += '<tr><td class="fw-bold">' + translations.description + '</td><td>' + escHtml(data.description) + '</td></tr>';
        html += '<tr><td class="fw-bold">' + translations.ip_address + '</td><td>' + escHtml(data.ip_address || '-') + '</td></tr>';
        html += '<tr><td class="fw-bold">' + translations.user_agent + '</td><td class="text-wrap" style="font-size:0.75rem;">' + escHtml(data.user_agent || '-') + '</td></tr>';
        html += '</table></div>';

        if (data.old_values && Object.keys(data.old_values).length > 0) {
            html += '<div class="mt-3"><h6 class="fw-bold">' + translations.before + '</h6>';
            html += '<pre class="bg-red-lt p-3 rounded mb-0" style="max-height:200px;overflow:auto;font-size:0.8rem;">' + escHtml(JSON.stringify(data.old_values, null, 2)) + '</pre></div>';
        }
        if (data.new_values && Object.keys(data.new_values).length > 0) {
            html += '<div class="mt-3"><h6 class="fw-bold">' + translations.after + '</h6>';
            html += '<pre class="bg-green-lt p-3 rounded mb-0" style="max-height:200px;overflow:auto;font-size:0.8rem;">' + escHtml(JSON.stringify(data.new_values, null, 2)) + '</pre></div>';
        }

        return html;
    }

    function ucFirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function escHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
