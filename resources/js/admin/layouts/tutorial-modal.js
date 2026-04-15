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
    var stepSubTopics = document.getElementById('tutorial-step-subtopics');
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
    if (stepSubTopics) {
        stepSubTopics.classList.add('d-none');
        stepSubTopics.classList.remove('fade-out');
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
        var majorRoutes = ['admin.dashboard', 'admin.product', 'admin.product.create', 'admin.warehouse', 'admin.customer', 'admin.supplier', 'admin.sales', 'admin.po', 'admin.accounting.chart', 'admin.setting.index', 'admin.setting.unit', 'admin.setting.category', 'admin.setting.tax'];
        
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
    // The 'hide.bs.modal' event listener (wired in DOMContentLoaded) handles
    // the tutorial/skip fetch centrally — no need to duplicate it here.
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
    var stepSubTopics = document.getElementById('tutorial-step-subtopics');
    var stepContext = document.getElementById('tutorial-step-context');
    var modalTitle = document.getElementById('tutorial-modal-title');
    
    // Find which step is currently active to fade it out
    var activeStep = null;
    if (step1 && !step1.classList.contains('d-none')) activeStep = step1;
    else if (stepContext && !stepContext.classList.contains('d-none')) activeStep = stepContext;
    else if (stepSubTopics && !stepSubTopics.classList.contains('d-none')) activeStep = stepSubTopics;
    
    if (activeStep) {
        activeStep.classList.add('fade-out');
        setTimeout(function() {
            activeStep.classList.add('d-none');
            if (stepSubTopics) stepSubTopics.classList.add('d-none');
            
            // Prepare Topics grid
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
        if (stepSubTopics) stepSubTopics.classList.add('d-none');
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

    // Mark as completed behind the scenes so it doesn't auto-popup again.
    // The hide.bs.modal listener handles the actual fetch — just update the attribute here
    // so initAutoTutorial won't try to show the welcome screen again on this page.
    if (!isGlobalCompleted && modalElement) {
        modalElement.setAttribute('data-completed', 'true');
    }
};

// Sub-topics drill-down: show a category's page list
window.showSubTopics = function(topicKey) {
    var data = (window.tutorialSubTopics || {})[topicKey];
    if (!data) return;

    var stepTopics = document.getElementById('tutorial-step-topics');
    var stepSubTopics = document.getElementById('tutorial-step-subtopics');
    var titleEl = document.getElementById('subtopics-title');
    var subtitleEl = document.getElementById('subtopics-subtitle');
    var listEl = document.getElementById('subtopics-list');
    var modalTitle = document.getElementById('tutorial-modal-title');

    if (!stepSubTopics || !listEl) return;

    // Build the sub-items list HTML
    var colorClass = 'bg-' + data.color + '-lt';
    var iconColorClass = 'text-' + data.color;
    var html = '';
    data.items.forEach(function(item) {
        html += '<a href="' + item.url + '" class="subtopic-item" data-turbo="true" data-turbo-cache="false">' +
            '<span class="subtopic-icon ' + colorClass + ' ' + iconColorClass + '">' +
            '<i class="ti ' + item.icon + '"></i></span>' +
            '<span class="subtopic-label">' + item.label + '</span>' +
            '<i class="ti ti-chevron-right subtopic-arrow"></i>' +
            '</a>';
    });
    listEl.innerHTML = html;

    // When any sub-topic link is clicked, set a sessionStorage flag before Turbo
    // navigates away. onTurboLoad() reads this flag on the destination page and
    // auto-opens the contextual tutorial modal for that page.
    listEl.addEventListener('click', function handleSubtopicClick(e) {
        var item = e.target.closest('.subtopic-item');
        if (item) {
            sessionStorage.setItem('tutorial_from_subtopic', '1');
        }
        // One-time: remove itself so re-renders don't stack listeners
        listEl.removeEventListener('click', handleSubtopicClick);
    });

    if (titleEl) titleEl.textContent = data.label;
    if (subtitleEl) subtitleEl.textContent = data.subtitle;
    if (modalTitle) modalTitle.innerHTML = '<i class="ti ' + data.icon + ' me-2"></i>' + data.label;

    // Fade topics out, show subtopics
    if (stepTopics && !stepTopics.classList.contains('d-none')) {
        stepTopics.classList.add('fade-out');
        setTimeout(function() {
            stepTopics.classList.add('d-none');
            stepSubTopics.classList.remove('d-none');
            void stepSubTopics.offsetWidth;
            stepSubTopics.style.opacity = '1';
        }, 250);
    } else {
        stepSubTopics.classList.remove('d-none');
        stepSubTopics.style.opacity = '1';
    }

    // Footer: hide Next button, show Back button
    var btnNext = document.getElementById('btn-next-tutorial');
    var btnBack = document.getElementById('btn-back-topics');
    if (btnNext) btnNext.style.display = 'none';
    if (btnBack) btnBack.classList.remove('d-none');
};

// Back from sub-topics to the topics grid
window.backToTopics = function() {
    window.showTopics();
};

window.closeTutorialExplicitly = closeTutorialExplicitly;

// --- Turbo-aware route syncing ---
// The tutorial modal is rendered once in the layout (outside the Turbo frame).
// On every Turbo navigation the <turbo-frame> content is swapped, which includes
// a fresh #page-route-indicator element with the new route in data-route.
// We read that indicator on turbo:load and:
//  1. Update the modal's own data-route attribute
//  2. Fetch the new contextual step HTML from the server and swap it in
//     so the ? button always shows the correct guide for the current page.

function syncTutorialContextFromRoute(newRoute) {
    var modalElement = document.getElementById('tutorial-modal');
    if (!modalElement) return Promise.resolve();

    // Update the stored route so openTutorialModal reads the correct one
    modalElement.setAttribute('data-route', newRoute);

    // Fetch fresh context HTML for the new route
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) return Promise.resolve();

    return fetch('/admin/tutorial/context?route=' + encodeURIComponent(newRoute), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'text/html'
        }
    })
    .then(function(res) { return res.text(); })
    .then(function(html) {
        var stepContext = document.getElementById('tutorial-step-context');
        if (stepContext) {
            stepContext.innerHTML = html;
        }
    })
    .catch(function() {
        // Silently fail — old content stays
    });
}

function onTurboLoad() {
    // Read the route from the indicator that lives inside the turbo frame
    var indicator = document.getElementById('page-route-indicator');
    var freshRoute = indicator ? (indicator.getAttribute('data-route') || '') : '';

    // Check if we arrived here from a sub-topic link click before navigating
    var fromSubtopic = sessionStorage.getItem('tutorial_from_subtopic');
    if (fromSubtopic) {
        sessionStorage.removeItem('tutorial_from_subtopic');

        // If freshRoute is empty (e.g. settings pages outside turbo frame), fall back to
        // reading the current modal data-route which was set by the server render.
        if (!freshRoute) {
            var modalEl = document.getElementById('tutorial-modal');
            freshRoute = modalEl ? (modalEl.getAttribute('data-route') || '') : '';
        }

        // Sync context first (returns a Promise), then open the modal once the
        // correct contextual HTML is loaded into #tutorial-step-context.
        syncTutorialContextFromRoute(freshRoute).then(function() {
            // Use isManual=true so openTutorialModal shows the contextual step,
            // regardless of whether the global tutorial is completed or not.
            openTutorialModal(true, false);
        });

        // Skip initAutoTutorial — we're handling the open manually above
        return;
    }

    // Normal navigation: sync context and check auto-show as usual
    if (freshRoute) {
        syncTutorialContextFromRoute(freshRoute);
    }
    initAutoTutorial();
}

// Handle normal load
document.addEventListener('DOMContentLoaded', function() {
    initAutoTutorial();

    // Wire up backdrop-click (and any other native Bootstrap hide) to run the skip logic.
    // This fires when the user clicks outside the modal (backdrop) since we removed data-bs-backdrop="static".
    // We use 'hide.bs.modal' so the fetch runs before the modal disappears.
    var modalElement = document.getElementById('tutorial-modal');
    if (modalElement) {
        modalElement.addEventListener('hide.bs.modal', function () {
            var isGlobalCompleted = modalElement.getAttribute('data-completed') === 'true';
            if (!isGlobalCompleted) {
                var csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (csrfToken) {
                    fetch('/admin/tutorial/skip', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                        }
                    }).then(function() {
                        // Mark locally so subsequent hide events don't re-fire the fetch
                        modalElement.setAttribute('data-completed', 'true');
                    });
                }
            }
        });
    }
});
// Handle Turbo frame load — sync route first, then check auto-show
document.addEventListener('turbo:load', onTurboLoad);

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