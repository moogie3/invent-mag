// Extracted inline scripts from script.blade.php to ensure strict CSP and clean Blade templates

// 1. Tabler Bootstrap exposure
(function() {
    var checkCount = 0;
    var maxChecks = 10;
    
    function exposeBootstrap() {
        checkCount++;
        if (window.tabler && window.tabler.bootstrap) {
            window.bootstrap = window.tabler.bootstrap;
            return true;
        }
        if (checkCount < maxChecks) {
            setTimeout(exposeBootstrap, 50);
        }
        return false;
    }
    exposeBootstrap();
})();

document.addEventListener('turbo:load', function() {
    if (window.tabler && window.tabler.bootstrap && !window.bootstrap) {
        window.bootstrap = window.tabler.bootstrap;
    }
});

// 2. Global fallback functions for journal entries
(function() {
    var funcs = [
        'updateTotals', 'validateAndSubmit', 'handleAccountChange', 
        'handleInput', 'addTransactionRow', 'removeTransactionRow',
        'searchAccounts', 'confirmAction', 'reverseEntry', 'voidEntry'
    ];
    funcs.forEach(function(name) {
        if (typeof window[name] !== 'function') {
            window[name] = function() {};
        }
    });
})();

// 3. Lazy Loaders for SortableJS and ApexCharts
document.addEventListener('turbo:load', function () {
    if (window.location.pathname.includes('/admin/sales-pipeline')) {
        if (typeof Sortable === 'undefined') {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js';
            document.head.appendChild(s);
        }
    }
    
    if (window.location.pathname.includes('/admin/dashboard') && typeof ApexCharts === 'undefined') {
        var s2 = document.createElement('script');
        // Since we cannot use asset() in JS, we use an absolute path assuming standard public structure
        // or a CDN fallback. We'll use the relative path from the domain root.
        s2.src = '/tabler/dist/libs/apexcharts/dist/apexcharts.min.js';
        document.head.appendChild(s2);
    }
});

// 4. Global functions
window.setDeleteFormAction = function(action) {
    var form = document.getElementById('deleteForm');
    if (form) {
        form.setAttribute('action', action);
    }
};
