// Tutorial Modal Initialization and Logic
// This script is automatically loaded on all pages

function getTutorialModalInstance() {
    var modalElement = document.getElementById('tutorial-modal');
    if (!modalElement) return null;
    // Use getOrCreateInstance to prevent Bootstrap 5 conflicts that cause the defaultPrevented error
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        return bootstrap.Modal.getOrCreateInstance(modalElement);
    }
    return null;
}

function openTutorialModal(isManual = false, forceTopics = false) {
    var modalInstance = getTutorialModalInstance();
    if (!modalInstance) return;

    var step1 = document.getElementById('tutorial-step-1');
    var stepTopics = document.getElementById('tutorial-step-topics');
    var stepContext = document.getElementById('tutorial-step-context');
    
    var btnNext = document.getElementById('btn-next-tutorial');
    var btnBack = document.getElementById('btn-back-topics');
    var modalTitle = document.getElementById('tutorial-modal-title');

    // Reset display cleanly without transitions to avoid visual bugs on first open
    if (step1) {
        step1.classList.add('d-none');
        step1.classList.remove('fade-out');
    }
    if (stepTopics) {
        stepTopics.classList.add('d-none');
        stepTopics.classList.remove('fade-out');
    }
    if (stepContext) {
        stepContext.classList.add('d-none');
        stepContext.classList.remove('fade-out');
    }

    if (forceTopics && stepTopics) {
        // Force open the Topics grid directly
        stepTopics.classList.remove('d-none');
        stepTopics.style.opacity = '1';
        if (btnNext) btnNext.style.display = 'none';
        if (btnBack) btnBack.classList.add('d-none');
        if (modalTitle) modalTitle.innerHTML = '<i class="ti ti-grid-dots me-2"></i> All Topics';
    } else if (isManual && stepContext) {
        // Opened manually from pretitle -> show contextual help for current page
        stepContext.classList.remove('d-none');
        stepContext.style.opacity = '1';
        if (btnNext) btnNext.style.display = 'none';
        if (btnBack) btnBack.classList.remove('d-none');
        if (modalTitle) modalTitle.innerHTML = '<i class="ti ti-help me-2"></i> Page Tutorial';
    } else if (step1) {
        // Auto opened -> show welcome overview
        step1.classList.remove('d-none');
        step1.style.opacity = '1';
        if (btnNext) btnNext.style.display = 'block';
        if (btnBack) btnBack.classList.add('d-none');
        if (modalTitle) modalTitle.innerHTML = '<i class="ti ti-rocket me-2"></i> Welcome to Invent-MAG';
    }

    modalInstance.show();
}

function initAutoTutorial() {
    var modalElement = document.getElementById('tutorial-modal');
    if (!modalElement) return; // Means user has completed it or not authenticated
    
    // The data attribute is injected from blade
    var isGlobalCompleted = modalElement.getAttribute('data-completed') === 'true';
    
    if (!isGlobalCompleted) {
        // If global tutorial not completed, show welcome screen
        setTimeout(function() {
            openTutorialModal(false);
        }, 500);
    } else {
        // Check for contextual auto-show based on local storage
        var currentRoute = modalElement.getAttribute('data-route');
        if(!currentRoute) return;

        // Only auto-show for main modules, not every single subpage
        var majorRoutes = ['admin.product', 'admin.product.create', 'admin.warehouse', 'admin.customer', 'admin.supplier', 'admin.sales', 'admin.po', 'admin.accounting.chart', 'admin.setting.index', 'admin.setting.unit', 'admin.setting.category', 'admin.setting.tax'];
        
        // Allow exact match or if current route starts with one of the major routes (like edit, view)
        var isMajorRoute = majorRoutes.some(r => currentRoute === r || (currentRoute.startsWith(r) && r !== 'admin.setting.index'));
        
        // Force true for settings index
        if (currentRoute === 'admin.setting.index') isMajorRoute = true;

        if (isMajorRoute) {
            var seenPages = JSON.parse(localStorage.getItem('invent_mag_seen_tutorials') || '{}');
            // Use the base route prefix to group them (e.g. admin.product covers index, edit, view)
            var baseRouteMatch = majorRoutes.find(r => currentRoute.startsWith(r));
            if (baseRouteMatch && !seenPages[baseRouteMatch]) {
                seenPages[baseRouteMatch] = true;
                localStorage.setItem('invent_mag_seen_tutorials', JSON.stringify(seenPages));
                
                // Delay slightly longer to let the page fully render
                setTimeout(function() {
                    openTutorialModal(true); // Open as contextual
                }, 800);
            }
        }
    }
}

function closeTutorialExplicitly() {
    var modalElement = document.getElementById('tutorial-modal');
    var isGlobalCompleted = modalElement ? modalElement.getAttribute('data-completed') === 'true' : true;
    
    if (!isGlobalCompleted) {
        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            fetch('/admin/tutorial/skip', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                }
            });
        }
    }
    
    var modalInstance = getTutorialModalInstance();
    if (modalInstance) {
        modalInstance.hide();
    } else {
        // Failsafe manual removal
        if (modalElement) {
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
        }
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        var backdrop = document.querySelector('.modal-backdrop');
        if(backdrop) backdrop.remove();
    }
}

// Make sure to expose to window so inline onclicks can find it if needed
window.showTopics = function() {
    var step1 = document.getElementById('tutorial-step-1');
    var stepTopics = document.getElementById('tutorial-step-topics');
    var stepContext = document.getElementById('tutorial-step-context');
    var modalTitle = document.getElementById('tutorial-modal-title');
    
    // Find which step is currently active to fade it out
    var activeStep = null;
    if (step1 && !step1.classList.contains('d-none')) activeStep = step1;
    else if (stepContext && !stepContext.classList.contains('d-none')) activeStep = stepContext;
    
    if (activeStep) {
        activeStep.classList.add('fade-out');
        setTimeout(function() {
            activeStep.classList.add('d-none');
            
            // Prepare Step 2
            if (stepTopics) {
                stepTopics.classList.remove('d-none');
                void stepTopics.offsetWidth; // Reflow
                stepTopics.style.opacity = '1';
            }
            
            var btnNext = document.getElementById('btn-next-tutorial');
            var btnBack = document.getElementById('btn-back-topics');
            
            if (btnNext) btnNext.style.display = 'none';
            if (btnBack) btnBack.classList.add('d-none');
            if (modalTitle) modalTitle.innerHTML = '<i class="ti ti-grid-dots me-2"></i> All Topics';
        }, 300);
    } else {
        // Fallback if none active
        if (stepTopics) {
            stepTopics.classList.remove('d-none');
            stepTopics.style.opacity = '1';
        }
        
        var btnNext = document.getElementById('btn-next-tutorial');
        var btnBack = document.getElementById('btn-back-topics');
        
        if (btnNext) btnNext.style.display = 'none';
        if (btnBack) btnBack.classList.add('d-none');
        if (modalTitle) modalTitle.innerHTML = '<i class="ti ti-grid-dots me-2"></i> All Topics';
    }
    
    var modalElement = document.getElementById('tutorial-modal');
    var isGlobalCompleted = modalElement ? modalElement.getAttribute('data-completed') === 'true' : true;

    // Mark as completed behind the scenes so it doesn't auto-popup again
    if (!isGlobalCompleted) {
        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            fetch('/admin/tutorial/skip', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                }
            });
        }
    }
};

window.closeTutorialExplicitly = closeTutorialExplicitly;

// Handle normal load
document.addEventListener('DOMContentLoaded', initAutoTutorial);
// Handle Turbo frame load
document.addEventListener('turbo:load', initAutoTutorial);

// Global click listener for the tutorial buttons
// This avoids inline onclick handlers which cause Bootstrap event conflicts in Tabler
document.addEventListener('click', function(e) {
    var triggerGeneral = e.target.closest('.trigger-tutorial-modal-general');
    if (triggerGeneral) {
        e.preventDefault();
        openTutorialModal(false, false);
        return;
    }

    var trigger = e.target.closest('.trigger-tutorial-modal');
    if (trigger) {
        e.preventDefault();
        // If they click the button explicitly configured to open the topics grid
        if (trigger.classList.contains('trigger-topics-grid')) {
            openTutorialModal(true, true);
        } else {
            openTutorialModal(true, false);
        }
    }
});