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

// 5. Demo and Trial Banner dismissal handlers
(function() {
    function initTrialBanners() {
        // Demo banner
        const demoBanner = document.getElementById('demo-banner');
        if (demoBanner) {
            if (sessionStorage.getItem('demo_banner_dismissed_session') !== 'true') {
                demoBanner.classList.remove('d-none');
            }

            const demoSessionBtn = document.getElementById('demo-btn-dismiss-session');
            if (demoSessionBtn && !demoSessionBtn.dataset.listenerAttached) {
                demoSessionBtn.dataset.listenerAttached = 'true';
                demoSessionBtn.addEventListener('click', function() {
                    sessionStorage.setItem('demo_banner_dismissed_session', 'true');
                    if (typeof bootstrap !== 'undefined') {
                        const alert = new bootstrap.Alert(demoBanner);
                        alert.close();
                    } else {
                        demoBanner.remove();
                    }
                });
            }
        }

        // Trial banner
        const trialBanner = document.getElementById('trial-banner');
        if (trialBanner) {
            if (sessionStorage.getItem('trial_banner_dismissed_session') !== 'true') {
                trialBanner.classList.remove('d-none');
            }

            const trialSessionBtn = document.getElementById('btn-dismiss-session');
            if (trialSessionBtn && !trialSessionBtn.dataset.listenerAttached) {
                trialSessionBtn.dataset.listenerAttached = 'true';
                trialSessionBtn.addEventListener('click', function() {
                    sessionStorage.setItem('trial_banner_dismissed_session', 'true');
                    if (typeof bootstrap !== 'undefined') {
                        const alert = new bootstrap.Alert(trialBanner);
                        alert.close();
                    } else {
                        trialBanner.remove();
                    }
                });
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTrialBanners);
    } else {
        initTrialBanners();
    }
    document.addEventListener('turbo:load', initTrialBanners);
})();
