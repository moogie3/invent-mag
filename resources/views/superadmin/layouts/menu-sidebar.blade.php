<aside class="sidebar">
    {{-- Brand Header --}}
    <div class="sidebar-header">
        <a href="{{ route('superadmin.dashboard') }}" class="sidebar-brand" data-turbo="true">
            <span class="sidebar-brand-icon">
                <i class="ti ti-brand-minecraft"></i>
            </span>
            <span class="sidebar-brand-name">Invent-MAG</span>
        </a>
    </div>

    {{-- User Profile --}}
    <div class="sidebar-user">
        <div class="user-info">
            <span class="user-avatar">
                @if (Auth::guard('super_admin')->check())
                    <span class="avatar avatar-sm avatar-rounded"
                        style="background-image: url('https://ui-avatars.com/api/?name={{ urlencode(Auth::guard('super_admin')->user()->name) }}&background=1a56db&color=fff');"></span>
                @else
                    <span class="avatar avatar-sm avatar-rounded"
                        style="background-image: url('{{ asset('storage/default-avatar.png') }}');"></span>
                @endif
            </span>
            <div class="user-details">
                @if (Auth::guard('super_admin')->check())
                    <span class="user-name">{{ Auth::guard('super_admin')->user()->name }}</span>
                    <span class="user-role">
                        <span class="sa-role-badge">Super Admin</span>
                    </span>
                @else
                    <span class="user-name">Guest</span>
                    <span class="user-role">Not logged in</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="sidebar-nav">
        <ul class="navbar-nav">

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}" href="{{ route('superadmin.dashboard') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-blue">
                            <i class="ti ti-layout-dashboard"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Dashboard</span>
                </a>
            </li>

            {{-- MANAGEMENT --}}
            <li class="nav-item nav-category">
                <span class="nav-category-label">Management</span>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.tenants.*') ? 'active' : '' }}" href="{{ route('superadmin.tenants.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-cyan">
                            <i class="ti ti-building-community"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Tenants</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.plans.*') ? 'active' : '' }}" href="{{ route('superadmin.plans.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-indigo">
                            <i class="ti ti-packages"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Plans</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.subscriptions.*') ? 'active' : '' }}" href="{{ route('superadmin.subscriptions.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-green">
                            <i class="ti ti-receipt"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Subscriptions</span>
                </a>
            </li>

            {{-- PAYMENTS --}}
            <li class="nav-item nav-category">
                <span class="nav-category-label">Payments</span>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.payment-overview.*') ? 'active' : '' }}" href="{{ route('superadmin.payment-overview.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-blue">
                            <i class="ti ti-report-money"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Payment Overview</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.transactions.*') ? 'active' : '' }}" href="{{ route('superadmin.transactions.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-cyan">
                            <i class="ti ti-arrows-exchange"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Transactions</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.pending-payments.*') ? 'active' : '' }}" href="{{ route('superadmin.pending-payments.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-orange">
                            <i class="ti ti-clock"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Pending Payments</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.invoices.*') ? 'active' : '' }}" href="{{ route('superadmin.invoices.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-purple">
                            <i class="ti ti-file-invoice"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Invoices</span>
                </a>
            </li>

            {{-- SYSTEM --}}
            <li class="nav-item nav-category">
                <span class="nav-category-label">System</span>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.permissions.*') ? 'active' : '' }}" href="{{ route('superadmin.permissions.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-orange">
                            <i class="ti ti-shield-lock"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Permissions</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.backups.*') ? 'active' : '' }}" href="{{ route('superadmin.backups.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-teal">
                            <i class="ti ti-database"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Backups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.audit-log.*') ? 'active' : '' }}" href="{{ route('superadmin.audit-log.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-purple">
                            <i class="ti ti-file-text"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Audit Log</span>
                </a>
            </li>

            {{-- CONFIGURATION --}}
            <li class="nav-item nav-category">
                <span class="nav-category-label">Configuration</span>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.settings.*') ? 'active' : '' }}" href="{{ route('superadmin.settings.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-secondary">
                            <i class="ti ti-settings"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">System Settings</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.features.*') ? 'active' : '' }}" href="{{ route('superadmin.features.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-yellow">
                            <i class="ti ti-flag"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Features</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.billing.*') ? 'active' : '' }}" href="{{ route('superadmin.billing.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-lime">
                            <i class="ti ti-currency-dollar"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Billing</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.cms.*') ? 'active' : '' }}" href="{{ route('superadmin.cms.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon">
                        <span class="nav-icon-badge nav-icon-teal">
                            <i class="ti ti-world"></i>
                        </span>
                    </span>
                    <span class="nav-link-title">Landing Page</span>
                </a>
            </li>

        </ul>
    </nav>

    {{-- Footer: How It Works + Theme Toggle + Logout --}}
    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between w-100 px-1">
            <div class="d-flex align-items-center gap-1">
                <div class="nav-item" id="theme-toggle-sidebar-container">
                    <a href="#" class="sidebar-footer-btn" id="theme-toggle-sidebar" title="Toggle theme">
                        <i class="ti ti-sun theme-icon-light"></i>
                        <i class="ti ti-moon theme-icon-dark"></i>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="sidebar-footer-btn" id="how-it-works-btn" title="How It Works" data-bs-toggle="modal" data-bs-target="#how-it-works-modal">
                        <i class="ti ti-help-circle"></i>
                    </a>
                </div>
            </div>
            <a class="sidebar-footer-btn sidebar-footer-btn-danger" href="#" onclick="event.preventDefault(); document.getElementById('sa-logout-form').submit();" title="Logout">
                <i class="ti ti-logout"></i>
            </a>
            <form id="sa-logout-form" method="POST" action="{{ route('superadmin.logout') }}" style="display: none;">
                @csrf
            </form>
        </div>
    </div>
</aside>
