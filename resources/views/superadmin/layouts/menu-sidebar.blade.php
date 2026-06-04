<aside class="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('superadmin.dashboard') }}" class="sidebar-logo d-flex align-items-center gap-2 text-decoration-none">
            <i class="ti ti-brand-minecraft fs-2 text-primary"></i>
            <span class="fs-3 fw-bold">Invent-MAG</span>
        </a>
        <button class="sidebar-close d-lg-none" id="sidebar-close">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <div class="sidebar-content">
        <div class="sidebar-menu">
            <div class="sidebar-menu-category">MAIN</div>
            <a href="{{ route('superadmin.dashboard') }}" class="sidebar-menu-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                <i class="ti ti-layout-dashboard sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Dashboard</span>
            </a>

            <div class="sidebar-menu-category mt-3">MANAGEMENT</div>
            <a href="#" class="sidebar-menu-item">
                <i class="ti ti-building-community sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Tenants</span>
            </a>
            <a href="#" class="sidebar-menu-item">
                <i class="ti ti-packages sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Plans</span>
            </a>
            <a href="#" class="sidebar-menu-item">
                <i class="ti ti-receipt sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Subscriptions</span>
            </a>

            <div class="sidebar-menu-category mt-3">SYSTEM</div>
            <a href="#" class="sidebar-menu-item">
                <i class="ti ti-shield-lock sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Permissions</span>
            </a>
            <a href="#" class="sidebar-menu-item">
                <i class="ti ti-database sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Backups</span>
            </a>
            <a href="#" class="sidebar-menu-item">
                <i class="ti ti-file-text sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Audit Log</span>
            </a>

            <div class="sidebar-menu-category mt-3">CONFIGURATION</div>
            <a href="#" class="sidebar-menu-item">
                <i class="ti ti-settings sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">System Settings</span>
            </a>
            <a href="#" class="sidebar-menu-item">
                <i class="ti ti-flag sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Features</span>
            </a>
            <a href="#" class="sidebar-menu-item">
                <i class="ti ti-currency-dollar sidebar-menu-icon"></i>
                <span class="sidebar-menu-text">Billing</span>
            </a>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between px-3 py-2">
            <a href="#" class="nav-link px-0" id="theme-toggle-sidebar" title="Toggle theme">
                <i class="ti ti-sun fs-2 theme-icon-light"></i>
                <i class="ti ti-moon fs-2 theme-icon-dark"></i>
            </a>
            <form method="POST" action="{{ route('superadmin.logout') }}" class="m-0">
                @csrf
                <button type="submit" class="nav-link px-0 btn btn-link text-decoration-none" title="Logout">
                    <i class="ti ti-logout fs-2"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
