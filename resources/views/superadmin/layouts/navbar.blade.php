<header class="navbar navbar-expand-md d-print-none nav-container">
    <div class="container-fluid">
        <!-- Mobile Menu Toggle -->
        <button class="navbar-toggler d-lg-none" type="button" id="mobile-menu-toggle">
            <span class="navbar-toggler-icon"></span>
        </button>

        <h1 class="navbar-brand navbar-brand-autodark d-none-navbar">
            <a href="{{ route('superadmin.dashboard') }}">
                <i class="ti ti-crown fs-2 text-primary me-2"></i> Super Admin
            </a>
        </h1>

        <!-- Right Side Icons -->
        <div class="d-flex flex-grow-1 justify-content-end align-items-center" id="navbar-right-content">
            <!-- Theme Toggle Button -->
            <div class="nav-item me-2" id="theme-toggle-navbar-container">
                <a href="#" class="nav-link px-0" id="theme-toggle-navbar">
                    <i class="ti ti-sun fs-2 theme-icon-light"></i>
                    <i class="ti ti-moon fs-2 theme-icon-dark"></i>
                </a>
            </div>

            <!-- Profile Dropdown -->
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                    <span class="avatar avatar-sm bg-primary-lt">
                        SA
                    </span>
                    <div class="d-none d-xl-block ps-2">
                        <div>Super Admin</div>
                        <div class="mt-1 small text-muted">System Administrator</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="#" class="dropdown-item">Settings</a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('superadmin.logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
