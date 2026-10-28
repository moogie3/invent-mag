// Track when the last Turbo navigation completed (performance.now() timestamp).
// Used by handleClickOutside to ignore the stale click that triggered the navigation —
// without this, the nav-link click bleeds into the new page and immediately closes the sidebar.
let lastTurboLoadTime = 0;

// ---- Session memory: open submenu panels + sidebar scroll ------------------
// Kept in this module, NOT in localStorage: a full reload (F5) must reset the
// sidebar to defaults, and module memory dies with the page - that is the
// intended semantics (reapply state from memory, not persistent state).
// Declared up HERE because the readyState boot path further down calls
// restoreSidebarMemory() during module evaluation - a `let` declared below
// that call would still be in its temporal dead zone there (the runtime error
// reads "can't access lexical declaration before initialization"; minified
// builds show only the mangled name).
//
// The open set is SNAPSHOT from the live DOM on turbo:before-cache instead of
// accumulated from events: every open path (plain click, rail-expand click,
// active-page chain) lands on the same class - `.collapse.show` on the
// div#submenu-N wrappers, which is exactly what Bootstrap toggles (the inner
// ul.nav-submenu never receives .show) - and whatever a rail collapse has
// already closed is naturally not remembered. The restore runs BEFORE
// initSidebar() so the sidebar's boot work sees its final state. Navbar-only
// mode has no .sidebar -> every hook no-ops.
let rememberedOpenSubmenus = new Set();
let rememberedSidebarScroll = 0;

// Every sidebar reveal — page load, submenu open — finishes within this window.
// The per-item step adapts to the item count instead of being fixed, so the menu
// never takes longer to appear just because it has more entries in it.
const REVEAL_WINDOW_MS = 320;

// The fast-close / smooth-open classes exist only to pick the transition timing
// for a single slide. Left behind, they fight the next toggle's cascade (the
// later-in-source smooth-open rule beats fast-close at equal specificity, so
// every close after an open ran the slow 0.3s open easing), and navbar.css keys
// two parts of the bar off them: the frosted layer is off while the bar moves,
// and the bar's content is hidden (the swish) for exactly as long as they exist.
// Drop both classes once the motion is over. The getAnimations() guard first
// defers to a newer toggle whose fresh slide owns the classes - it scheduled its
// own cleanup - but gives up after a few passes, because content is invisible
// while these classes are on and must not stay that way.
function schedulePhaseCleanup() {
    const sidebarEl = document.querySelector(".sidebar");
    const mainEl = document.querySelector(".main-content");
    if (!sidebarEl || !mainEl) return;
    let passes = 0;
    const attempt = () => {
        if (mainEl.getAnimations().length > 0 && ++passes < 4) {
            setTimeout(attempt, 400);
            return;
        }
        mainEl.classList.remove("main-content-fast-close", "main-content-smooth-open");
        sidebarEl.classList.remove("sidebar-fast-close", "sidebar-smooth-open");
    };
    setTimeout(attempt, 400);
}

document.addEventListener("usersettingsloaded", function () {
    restoreSidebarMemory();
    initSidebar();
    syncActiveState();
});

// Also re-init after Turbo navigation
document.addEventListener("turbo:load", function () {
    lastTurboLoadTime = performance.now();
    restoreSidebarMemory();
    initSidebar();
    syncActiveState();
});

// Fallback for contexts without Turbo (e.g. superadmin)
if (document.readyState === "complete" || document.readyState === "interactive") {
    restoreSidebarMemory();
    initSidebar();
    syncActiveState();
} else {
    document.addEventListener("DOMContentLoaded", function () {
        restoreSidebarMemory();
        initSidebar();
        syncActiveState();
    });
}

function syncActiveState() {
    const currentPath = window.location.pathname;
    const sidebar = document.querySelector(".sidebar");
    if (!sidebar) return;

    sidebar.querySelectorAll(".nav-link").forEach(link => {
        const href = link.getAttribute("href");
        if (href && href !== "#" && href !== currentPath + "/" && href !== currentPath) {
            link.classList.remove("active");
        }
    });

    setTimeout(() => {
        sidebar.querySelectorAll(".nav-link").forEach(link => {
            const href = link.getAttribute("href");
            if (href && href !== "#") {
                const linkPath = new URL(href, window.location.origin).pathname;
                if (linkPath === currentPath || currentPath.startsWith(linkPath + "/")) {
                    link.classList.add("active");
                }
            }
        });
    }, 50);
}

// ---- Active-page chain: which panels show WHERE you are --------------------
// Derived from the URL with the same match rule as syncActiveState (exact hit
// or path prefix, so /admin/product/edit/9 keeps Product open). Used for every
// purpose that must never be remembered: restoring the chain on load, the
// collapsed rail's icon group (only the active section earns rail rows), and
// deciding which panels a rail collapse may close (everything EXCEPT the
// chain). A function declaration so it is hoisted - the readyState boot path
// calls restoreSidebarMemory() during module evaluation.
function getActiveChainPanels(sidebar) {
    const currentPath = window.location.pathname;
    const panels = new Set();
    sidebar.querySelectorAll(".nav-link").forEach((link) => {
        const href = link.getAttribute("href");
        if (!href || href === "#") return;
        let linkPath;
        try {
            linkPath = new URL(href, window.location.origin).pathname;
        } catch (err) {
            return;
        }
        if (linkPath !== currentPath && !currentPath.startsWith(linkPath + "/")) return;
        let panel = link.closest(".collapse");
        while (panel && sidebar.contains(panel)) {
            panels.add(panel);
            panel = panel.parentElement ? panel.parentElement.closest(".collapse") : null;
        }
    });
    return panels;
}

// Bring the panels to the chain's state: close every open panel EXCEPT the
// chain, and OPEN the chain itself if it happens to be closed. Called from
// all three paths that enter the rail (manual toggle, click-outside
// auto-close, sub-768 resize): a non-chain group left open in the 70px rail
// would show as icons the URL never stood behind - the rail renders the
// active group only. And the chain can be CLOSED at collapse time: on an
// active page the section trigger is a normal toggle, so clicking
// Accounting while ON Accounting closes its dropdown; collapsing right
// after then produced a rail with no group, no marker, no "you are here"
// at all (rows 0, active link 0x0 - reproduced before this fix). The chain
// is derived from the URL every time, so opening it here is the same truth
// restore applies on load - the rail always shows where you are.
function closePanelsExceptChain(sidebar) {
    const chain = getActiveChainPanels(sidebar);
    // .collapsing too, not just .show: a click on a rail section makes
    // Bootstrap's data-api (delegated on document, ahead of our own link
    // handler) START opening that submenu at once, and a re-collapse inside
    // that ~350ms window met a panel that was neither open nor closed.
    // The old .show-only query skipped it, its pending transition completed
    // afterwards, and a ghost group appeared in the rail (reproduced: the
    // clicked group listed next to the chain). Force-resolving the pending
    // wait (a synthetic transitionend marks Bootstrap's completion callback
    // as consumed) lets hide() close it immediately instead. The selector
    // is bare .collapsing on purpose: mid-transition this bundled Bootstrap
    // REPLACES the collapse class with collapsing, so .collapse.collapsing
    // matches nothing (measured: className is exactly "collapsing").
    sidebar.querySelectorAll(".collapse.show, .collapsing").forEach((panel) => {
        if (chain.has(panel)) return;
        if (panel.classList.contains("collapsing")) {
            panel.dispatchEvent(new Event("transitionend"));
        }
        const instance = window.bootstrap && bootstrap.Collapse.getInstance(panel);
        if (instance) {
            instance.hide();
        } else {
            // No instance means no animation to wait for; every open path
            // creates one, so this is only a safety net - the next restore
            // resyncs aria-expanded either way.
            panel.classList.remove("show");
        }
    });
    chain.forEach((panel) => {
        if (panel.classList.contains("show")) return;
        panel.classList.add("show");
        // Same instance rule as restore: a .show without a Collapse instance
        // would outlive the rail (the next hide() finds nothing to hide) and
        // the trigger's next click would have nothing to toggle against.
        // toggle:false keeps the constructor from flipping it closed again.
        if (window.bootstrap && bootstrap.Collapse) {
            bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false });
        }
    });
    // ARIA must reflect the truth we just forced: the chain's triggers are
    // expanded now, non-chain ones collapsed (hide() already fixed theirs).
    sidebar.querySelectorAll("[data-bs-toggle='collapse']").forEach((trigger) => {
        const target = trigger.getAttribute("data-bs-target") || trigger.getAttribute("href");
        if (!target || target[0] !== "#") return;
        const panel = sidebar.querySelector(target);
        if (panel) trigger.setAttribute("aria-expanded", panel.classList.contains("show") ? "true" : "false");
    });
}

// Bring the active row into view when entering the rail. The rail can be
// taller than the screen (a full section list plus the chain runs past the
// fold on short displays), and .sidebar hides its scrollbar - a cut-off
// group gives no hint that rows continue below. scrollIntoView with
// block:'nearest' moves ONLY when the row is fully out of view, so a row
// that is already visible leaves the remembered scroll position alone.
function scrollActiveRowIntoView(sidebar) {
    const active = sidebar.querySelector('.nav-link.active[href]:not([href="#"])');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

// Rail tooltip names, resolved at hover (tooltips are created once per link,
// but the active page changes on every navigation): a submenu row reads
// "Accounting › General Ledger" - its section, then itself - so the 70px
// rail's icon row is self-describing; the section row reports where you
// actually are while its group is open ("Accounting › General Ledger" on the
// Accounting section, just "Purchasing" while its children are elsewhere).
function railTooltipText(link) {
    const titleElement = link.querySelector(".nav-link-title");
    const title = titleElement ? titleElement.textContent.trim() : "";

    const ownPanel = link.closest(".collapse");
    if (ownPanel) {
        const trigger = ownPanel.parentElement
            ? ownPanel.parentElement.querySelector(":scope > .nav-link")
            : null;
        const triggerTitle = trigger ? trigger.querySelector(".nav-link-title") : null;
        const section = triggerTitle ? triggerTitle.textContent.trim() : "";
        return section ? section + " › " + title : title;
    }

    const target = link.getAttribute("data-bs-target");
    const panel = target ? document.querySelector(target) : null;
    const activeChild = panel ? panel.querySelector(".nav-link.active .nav-link-title") : null;
    return activeChild ? title + " › " + activeChild.textContent.trim() : title;
}

// ---- Session memory: open submenu panels + sidebar scroll
// (state declared at the top of the file - the readyState boot path calls the
// restore below during module evaluation, so its `let`s must precede it.)
function restoreSidebarMemory() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    // Rail vs expanded decides WHAT may be reapplied. The chain below applies
    // in BOTH modes - the collapsed rail renders it as its icon group - while
    // remembered, user-opened extras only reopen in the expanded sidebar
    // (unlabeled rows in the 70px rail would claim to be the active group).
    // `!== 'false'` matches getSidebarState()'s null-default below (an unset
    // key means collapsed), so restore and init can never disagree about the
    // mode on first boot.
    const railCollapsed = sidebar.classList.contains('collapsed') ||
        localStorage.getItem('sidebarCollapsed') !== 'false';

    if (!railCollapsed) {
        // Remembered opens, by panel id (div#submenu-N, stable across Turbo
        // body swaps).
        rememberedOpenSubmenus.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.classList.contains('collapse') && sidebar.contains(el)) {
                el.classList.add('show');
            }
        });
    }

    // The active page's own chain: the selected link must be visible even
    // when navigation never went through the menu (shortcuts, links in the
    // content). Derived from the URL - so F5 forgets every remembered extra
    // but never where you are, and this is the group the collapsed rail
    // shows as icons.
    getActiveChainPanels(sidebar).forEach(panel => {
        panel.classList.add('show');
    });

    // A restored .show needs a Collapse instance behind it: the rail
    // collapse hides open panels through
    // bootstrap.Collapse.getInstance() - a null there would leave the
    // submenu open inside the icon rail - and the trigger's next click
    // needs one to toggle against. toggle:false stops the constructor
    // from flipping an already-open panel closed. Runs for BOTH modes now,
    // because the rail-visible chain needs its instance too.
    sidebar.querySelectorAll('.collapse.show').forEach(panel => {
        if (window.bootstrap && bootstrap.Collapse) {
            bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false });
        }
    });

    // Triggers: the server renders every aria-expanded="false"; reflect the
    // restored truth instead.
    sidebar.querySelectorAll('[data-bs-toggle="collapse"]').forEach(trigger => {
        const target = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');
        if (!target || target[0] !== '#') return;
        const panel = sidebar.querySelector(target);
        if (panel) trigger.setAttribute('aria-expanded', panel.classList.contains('show') ? 'true' : 'false');
    });

    // Scroll AFTER the same-tick initSidebar() has applied the rail state:
    // the rail changes the scroll box's height, and a pre-init set would be
    // clamped against the pre-init box. rAF runs after that synchronous work.
    requestAnimationFrame(() => {
        sidebar.scrollTop = rememberedSidebarScroll;
        // Rail only: the remembered position can leave the newly-opened
        // chain below the fold. 'nearest' keeps the memory whenever the
        // active row is already on screen (the common case) and corrects
        // only a genuinely hidden one.
        if (railCollapsed) scrollActiveRowIntoView(sidebar);
    });
}


document.addEventListener('turbo:before-cache', () => {
    const sidebar = document.querySelector(".sidebar");
    if (sidebar) {
        sidebar.dataset.turboInitialized = "";
        // Snapshot what is open + how far down we are so the next page can
        // reapply both. Class-based on the div#submenu-N panels Bootstrap
        // actually toggles: it captures every open path and excludes whatever
        // a rail collapse has already closed. The active chain is excluded
        // too - it is derived from the NEXT page's URL at restore, so riding
        // along as memory would keep yesterday's section open next to
        // today's. Empty on a full load - F5 resets the memory by
        // construction.
        const chain = getActiveChainPanels(sidebar);
        rememberedOpenSubmenus = new Set(
            Array.from(sidebar.querySelectorAll('.collapse.show'))
                .filter(panel => panel.id && !chain.has(panel))
                .map(panel => panel.id)
        );
        rememberedSidebarScroll = sidebar.scrollTop;
    }
});

function initSidebar() {
    const sidebar = document.querySelector(".sidebar");
    const sidebarToggle = document.getElementById("sidebar-toggle");
    const mainContent = document.querySelector(".main-content");
    const body = document.body;

    if (!sidebar || !mainContent) {
        return;
    }

    // Check if already initialized
    if (sidebar.dataset.turboInitialized === "true") {
        return;
    }
    sidebar.dataset.turboInitialized = "true";

    // Create lock button
    const createLockButton = function () {
        const lockButton = document.createElement("button");
        lockButton.className = "sidebar-lock-btn";
        lockButton.innerHTML = document.body.classList.contains('sidebar-locked')
            ? '<i class="ti ti-lock"></i>'
            : '<i class="ti ti-lock-open"></i>';
        lockButton.title = document.body.classList.contains('sidebar-locked')
            ? "Unlock sidebar (allows auto-close)"
            : "Lock sidebar (prevents auto-close)";

        // Add initial locked/unlocked class
        if (document.body.classList.contains('sidebar-locked')) {
            lockButton.classList.add("locked");
        } else {
            lockButton.classList.add("unlocked");
        }

        const sidebarHeader = sidebar.querySelector(".sidebar-header");
        if (sidebarHeader) {
            sidebarHeader.style.position = "relative";
            sidebarHeader.appendChild(lockButton);
        }

        return lockButton;
    };

    const lockButton = createLockButton();

    // Toggle lock functionality
    const toggleLock = function () {
        document.body.classList.toggle("sidebar-locked");

        const icon = lockButton.querySelector("i");

        if (document.body.classList.contains('sidebar-locked')) {
            icon.className = "ti ti-lock";
            lockButton.classList.add("locked");
            lockButton.classList.remove("unlocked");
            lockButton.title = "Unlock sidebar (allows auto-close)";
        } else {
            icon.className = "ti ti-lock-open";
            lockButton.classList.add("unlocked");
            lockButton.classList.remove("locked");
            lockButton.title = "Lock sidebar (prevents auto-close)";
        }
    };

    // Lock button event
    lockButton.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleLock();
    });

    const tooltipManager = {
        tooltips: new Map(),

        create(element, text) {
            if (this.tooltips.has(element)) return;

            const tooltip = document.createElement("div");
            tooltip.className = "sidebar-tooltip";

            document.body.appendChild(tooltip);
            this.tooltips.set(element, tooltip);

            const showTooltip = (e) => {
                if (!sidebar.classList.contains("collapsed")) return;

                // Text may be a getter: rail names depend on the ACTIVE page,
                // which changes per navigation while the tooltip element is
                // created once. Re-read before measuring - the bubble is
                // nowrap, so its height must match the fresh text.
                tooltip.textContent = typeof text === "function" ? text() : text;
                const rect = element.getBoundingClientRect();
                tooltip.style.left = rect.right + 10 + "px";
                tooltip.style.top =
                    rect.top +
                    rect.height / 2 -
                    tooltip.offsetHeight / 2 +
                    "px";
                tooltip.classList.add("show");
            };

            const hideTooltip = () => {
                tooltip.classList.remove("show");
            };

            element.addEventListener("mouseenter", showTooltip);
            element.addEventListener("mouseleave", hideTooltip);
        },

        removeAll() {
            this.tooltips.forEach((tooltip, element) => {
                tooltip.remove();
            });
            this.tooltips.clear();
        },
    };

    // Add enhanced tooltips with staggered animation
    const addTooltips = function () {
        // Only add tooltips if the user setting is enabled
        if (window.userSettings && !window.userSettings.show_tooltips) {
            return; // Do not add tooltips if disabled
        }

        const navLinks = sidebar.querySelectorAll(".nav-link");
        navLinks.forEach((link, index) => {
            const titleElement = link.querySelector(".nav-link-title");
            if (titleElement) {
                // Add staggered fade-in animation for collapsed state
                link.style.animationDelay = `${index * 50}ms`;
                // A getter, not the text as-is: the rail name ("Accounting ›
                // General Ledger") depends on which page is active, and
                // syncActiveState keeps changing that after this runs.
                tooltipManager.create(link, () => railTooltipText(link));
            }
        });

        // Also add tooltips to sidebar footer buttons using their title attribute
        const footerBtns = sidebar.querySelectorAll(".sidebar-footer-btn");
        footerBtns.forEach((btn) => {
            const title = btn.getAttribute("title");
            if (title) {
                tooltipManager.create(btn, title);
            }
        });
    };

    // Enhanced sidebar toggle with fast closing, smooth opening
    const toggleSidebar = function () {
        const isCollapsed = sidebar.classList.contains("collapsed");
        const willCollapse = !isCollapsed;

        const navTexts = sidebar.querySelectorAll(".nav-link-title");

        if (willCollapse) {
            // Close every open submenu EXCEPT the active page's chain: the
            // chain stays .show and becomes the rail's icon group; every
            // other open panel would appear as unlabeled icons the URL never
            // stood behind (and, before this rule, as invisible rows).
            closePanelsExceptChain(sidebar);

            // FAST closing - immediate collapse with quick transition.
            // The smooth-open classes must come OFF here: they share
            // specificity with the fast-close ones and win on source order,
            // so leaving them on made every close run the 0.3s open easing.
            sidebar.classList.add("sidebar-fast-close");
            sidebar.classList.remove("sidebar-smooth-open");
            mainContent.classList.add("main-content-fast-close");
            mainContent.classList.remove("main-content-smooth-open");

            // Quickly hide text and collapse
            navTexts.forEach((text) => {
                text.classList.add("nav-text-fade-out");
            });

            // Immediate collapse
            setTimeout(() => {
                sidebar.classList.add("collapsed");
                mainContent.classList.add("sidebar-collapsed");
                body.classList.remove("sidebar-open");
                // Only add tooltips if the setting is enabled (default: enabled when unset)
                if (!window.userSettings || window.userSettings.show_tooltips) {
                    addTooltips();
                }
                // Chain rows can sit below the fold once the rail is 70px -
                // reveal the active row if (and only if) it ended up hidden.
                scrollActiveRowIntoView(sidebar);
            }, 50);
        } else {
            // SMOOTH opening - keep the nice opening animation
            sidebar.classList.remove("sidebar-fast-close");
            sidebar.classList.add("sidebar-smooth-open");
            mainContent.classList.remove("main-content-fast-close");
            mainContent.classList.add("main-content-smooth-open");

            sidebar.classList.remove("collapsed");
            mainContent.classList.remove("sidebar-collapsed");
            body.classList.add("sidebar-open");

            // Staggered fade-in animation for text, held to the same window as
            // the rest of the reveal: a flat 30ms per label left the bottom of
            // the menu still sliding in 1.3s after the icons had settled.
            setTimeout(() => {
                const textStep = navTexts.length > 1 ? REVEAL_WINDOW_MS / (navTexts.length - 1) : 0;
                navTexts.forEach((text, index) => {
                    text.classList.remove("nav-text-fade-out");
                    text.classList.add("nav-text-slide-in");

                    setTimeout(() => {
                        text.classList.add("visible");
                    }, index * textStep);
                });
            }, 100);

            tooltipManager.removeAll();

            // Ensure all submenu items are visible when sidebar opens
            sidebar.querySelectorAll('.nav-submenu .nav-item').forEach(item => {
                item.classList.remove('visible');
                item.style.transitionDelay = '';
            });
        }

        schedulePhaseCleanup();
    };

    // Get persistent sidebar state
    const getSidebarState = function () {
        const savedState = localStorage.getItem("sidebarCollapsed");
        return savedState === null ? true : savedState === "true"; // Default to collapsed if no saved state
    };

    // Save sidebar state
    const saveSidebarState = function (isCollapsed) {
        localStorage.setItem("sidebarCollapsed", isCollapsed.toString());
    };

    // Initialize with persistent sidebar state
    const initializeSidebar = function () {
        // A Turbo snapshot cached mid-toggle restores with the phase classes
        // still on, and a plain load schedules no cleanup for them - navbar.css
        // hides the bar's content while they exist. They can only be leftovers
        // here: this runs before any toggle in this page's lifetime.
        sidebar.classList.remove("sidebar-fast-close", "sidebar-smooth-open");
        mainContent.classList.remove("main-content-fast-close", "main-content-smooth-open");

        const shouldBeCollapsed = getSidebarState();

        // Set initial state based on saved preference
        if (shouldBeCollapsed) {
            sidebar.classList.add("collapsed");
            mainContent.classList.add("sidebar-collapsed");
            body.classList.remove("sidebar-open");
        } else {
            sidebar.classList.remove("collapsed");
            mainContent.classList.remove("sidebar-collapsed");
            body.classList.add("sidebar-open");
        }

        // Cascade the links into view, then take the whole menu up together.
        // A flat 80ms per link gave this 43-link sidebar a 3.3s tail, which is
        // why the bottom of the menu looked late to arrive on every load.
        const navItems = sidebar.querySelectorAll(".nav-link");
        const revealStep = navItems.length > 1 ? REVEAL_WINDOW_MS / (navItems.length - 1) : 0;

        navItems.forEach((item, index) => {
            item.classList.add("nav-item-animate");

            setTimeout(() => {
                item.classList.add("visible");
            }, index * revealStep);
        });

        // Add tooltips if sidebar is collapsed AND the setting is enabled (default: enabled when unset)
        if (shouldBeCollapsed && (!window.userSettings || window.userSettings.show_tooltips)) {
            setTimeout(addTooltips, REVEAL_WINDOW_MS + 100);
        }

        // Apply the same bounded cascade to children of submenus already open,
        // so parents and children line up in one 320ms sweep rather than a
        // second 100ms-per-item ramp stacked on top of the first.
        sidebar.querySelectorAll('.nav-submenu.show').forEach(openSubmenu => {
            const submenuItems = openSubmenu.querySelectorAll('.nav-item');
            const subStep = submenuItems.length > 1 ? REVEAL_WINDOW_MS / (submenuItems.length - 1) : 0;
            submenuItems.forEach((item, idx) => {
                item.style.transitionDelay = `${idx * subStep}ms`;
                item.classList.add('visible');
            });

            // The inline delay is what staggers this sweep, but nothing ever
            // cleared it, so it rode along on every later transition the same
            // <li> performed — collapsing the sidebar made open-submenu items
            // wait up to 320ms before they started moving. Drop it once the
            // reveal has had time to finish.
            setTimeout(() => {
                submenuItems.forEach((item) => {
                    item.style.transitionDelay = '';
                });
            }, REVEAL_WINDOW_MS + 50);
        });
    };

    // Updated toggle function to save state
    const toggleSidebarWithPersistence = function () {
        const isCollapsed = sidebar.classList.contains("collapsed");
        const willCollapse = !isCollapsed;

        // Save the new state
        saveSidebarState(willCollapse);

        // Call the existing toggle function
        toggleSidebar();
    };

    // Initialize everything
    initializeSidebar();

    // Handle clicks on parent menu items when sidebar is collapsed
    const navLinksWithChildren = sidebar.querySelectorAll('.nav-item.dropdown > .nav-link[data-bs-toggle="collapse"]');
    navLinksWithChildren.forEach(link => {
        link.addEventListener('click', function(e) {
            if (sidebar.classList.contains('collapsed')) {
                // Prevent default collapse behavior initially
                e.preventDefault();
                e.stopPropagation();

                // Expand the sidebar
                saveSidebarState(false); // Set state to expanded
                toggleSidebar();

                // After a short delay to allow sidebar to open, trigger the collapse
                setTimeout(() => {
                    // The sidebar may have been collapsed again inside this
                    // 500ms window (the user's "close while the submenu is
                    // still opening" case): opening the target NOW would
                    // plant an unlabeled ghost group in the icon rail, since
                    // closePanelsExceptChain already ran on that collapse.
                    // The chain rule owns whatever shows in the rail. Both
                    // signals, not just "collapsed": that class lands 50ms
                    // AFTER the click, and a pending timer firing inside
                    // that gap saw "not collapsed" and opened anyway
                    // (reproduced: ghost group in the rail). sidebar-fast-close
                    // is added SYNCHRONOUSLY by the willCollapse branch, so it
                    // closes the gap; the expand path removes it synchronously
                    // too, so a legitimate pending open still runs.
                    if (sidebar.classList.contains('collapsed')
                        || sidebar.classList.contains('sidebar-fast-close')) return;
                    // Manually trigger Bootstrap's collapse toggle
                    const targetId = link.getAttribute('data-bs-target');
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        const bsCollapse = new bootstrap.Collapse(targetElement, {
                            toggle: false // Do not toggle immediately
                        });

                        // Listen for when the submenu is fully shown by Bootstrap
                        targetElement.addEventListener('shown.bs.collapse', function handler() {
                            // Bounded cascade: a flat 100ms per child put the tail of a
                            // submenu nearly a second behind its own first entry.
                            const submenuItems = targetElement.querySelectorAll('.nav-item');
                            const subStep = submenuItems.length > 1
                                ? REVEAL_WINDOW_MS / (submenuItems.length - 1)
                                : 0;
                            submenuItems.forEach((item, idx) => {
                                item.style.transitionDelay = `${idx * subStep}ms`;
                                item.classList.add('visible');
                            });
                            targetElement.removeEventListener('shown.bs.collapse', handler);

                            // Same cleanup as the load-time cascade above: the inline delay only
                            // exists to stagger this reveal, and left behind it delays every later
                            // transition the same <li> performs - collapsing the sidebar made these
                            // items wait out a stale delay before they would start moving.
                            setTimeout(() => {
                                submenuItems.forEach((item) => {
                                    item.style.transitionDelay = '';
                                });
                            }, REVEAL_WINDOW_MS + 50);
                        });

                        bsCollapse.show(); // Explicitly show the submenu
                    }
                }, 500); // Adjust delay to match sidebar transition duration
            }
        });
    });

    // Toggle button event with persistence (use delegation for robust binding)
    document.removeEventListener('click', window.sidebarToggleHandler);
    window.sidebarToggleHandler = function (e) {
        const toggleBtn = e.target.closest("#sidebar-toggle");
        if (toggleBtn) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebarWithPersistence();
        }
    };
    document.addEventListener('click', window.sidebarToggleHandler);

    // Add global listeners only once per page session
    if (!window.sidebarGlobalListenersAdded) {
        // Add click outside listener
        document.addEventListener("click", function (e) {
            // Ignore clicks that happened at or before the most recent turbo:load completed.
            if (e.timeStamp <= lastTurboLoadTime + 150) return;

            const currentSidebar = document.querySelector(".sidebar");
            const currentToggle = document.getElementById("sidebar-toggle");
            
            if (!currentSidebar) return;

            const isOpen = !currentSidebar.classList.contains("collapsed");

            if (
                isOpen &&
                !document.body.classList.contains("sidebar-locked") &&
                !currentSidebar.contains(e.target) &&
                !(currentToggle && currentToggle.contains(e.target))
            ) {
                localStorage.setItem("sidebarCollapsed", "true");
                
                // Fast closing (and drop the open-phase classes, or the
                // later-in-source smooth-open transition wins the cascade)
                currentSidebar.classList.add("sidebar-fast-close");
                currentSidebar.classList.remove("sidebar-smooth-open");
                const currentMain = document.querySelector(".main-content");
                if (currentMain) {
                    currentMain.classList.add("main-content-fast-close");
                    currentMain.classList.remove("main-content-smooth-open");
                }
                
                currentSidebar.querySelectorAll(".nav-link-title").forEach((text) => text.classList.add("nav-text-fade-out"));

                // Same rail rule as the manual toggle: only the active group
                // stays open. This path never closed panels before - a group
                // open here survived the collapse as invisible rows.
                closePanelsExceptChain(currentSidebar);

                setTimeout(() => {
                    currentSidebar.classList.add("collapsed");
                    if(currentMain) currentMain.classList.add("sidebar-collapsed");
                    document.body.classList.remove("sidebar-open");
                    // Tooltips are rail-only, and this path was the one
                    // collapse that never attached them - hover in the rail
                    // stayed silent after an outside click.
                    if (!window.userSettings || window.userSettings.show_tooltips) {
                        addTooltips();
                    }
                    // Same fold rule as the manual toggle: reveal the active
                    // row if the collapsed rail pushed it out of view.
                    scrollActiveRowIntoView(currentSidebar);
                }, 50);

                schedulePhaseCleanup();
            }
        });

        // Enhanced responsive behavior with persistence
        let resizeTimeout;
        window.addEventListener("resize", function () {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const currentSidebar = document.querySelector(".sidebar");
                if (currentSidebar && window.innerWidth < 768) {
                    if (!currentSidebar.classList.contains("collapsed")) {
                        localStorage.setItem("sidebarCollapsed", "true");
                        closePanelsExceptChain(currentSidebar);
                        currentSidebar.classList.add("collapsed");
                        document.body.classList.remove("sidebar-open");
                        const currentMain = document.querySelector(".main-content");
                        if(currentMain) currentMain.classList.add("sidebar-collapsed");
                        // Narrow screens are the WORST case for the fold -
                        // reveal the active row if the rail just hid it.
                        scrollActiveRowIntoView(currentSidebar);
                    }
                }
            }, 150);
        });

        // Add keyboard support with persistence
        document.addEventListener("keydown", function (e) {
            // Toggle sidebar with Ctrl/Cmd + B
            if ((e.ctrlKey || e.metaKey) && e.key === "b") {
                const currentToggle = document.getElementById("sidebar-toggle");
                if (currentToggle) {
                    e.preventDefault();
                    currentToggle.click();
                }
            }
        });
        
        window.sidebarGlobalListenersAdded = true;
    }
}
