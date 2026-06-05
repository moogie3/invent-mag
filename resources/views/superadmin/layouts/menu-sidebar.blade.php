<aside class="sidebar">
    <div class="sidebar-header">
        <button type="button" id="sidebar-toggle" class="navbar-brand">
            <div class="brand-icon">
                <i class="ti ti-brand-minecraft brand-icon"></i>
            </div>
            <span class="brand-text">Invent-MAG</span>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="user-info">
            <span class="user-avatar">
                @if (Auth::guard('super_admin')->check())
                    <span class="avatar avatar-sm"
                        style="background-image: url('https://ui-avatars.com/api/?name={{ urlencode(Auth::guard('super_admin')->user()->name) }}&background=random');"></span>
                @else
                    <span class="avatar avatar-sm"
                        style="background-image: url('{{ asset('storage/default-avatar.png') }}');"></span>
                @endif
            </span>
            <div class="user-details">
                @if (Auth::guard('super_admin')->check())
                    <span class="user-name">{{ Auth::guard('super_admin')->user()->name }}</span>
                    <span class="user-role">System Administrator</span>
                @else
                    <span class="user-name">Guest</span>
                    <span class="user-role">Not logged in</span>
                @endif
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}" href="{{ route('superadmin.dashboard') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon"><i class="ti ti-layout-dashboard"></i></span>
                    <span class="nav-link-title">Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <div class="nav-item-divider" style="font-size: 0.65rem; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.75rem 0.75rem 0.35rem; margin-top: 0.5rem; white-space: nowrap;">Management</div>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.tenants.*') ? 'active' : '' }}" href="{{ route('superadmin.tenants.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon"><i class="ti ti-building-community"></i></span>
                    <span class="nav-link-title">Tenants</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.plans.*') ? 'active' : '' }}" href="{{ route('superadmin.plans.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon"><i class="ti ti-packages"></i></span>
                    <span class="nav-link-title">Plans</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.subscriptions.*') ? 'active' : '' }}" href="{{ route('superadmin.subscriptions.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon"><i class="ti ti-receipt"></i></span>
                    <span class="nav-link-title">Subscriptions</span>
                </a>
            </li>

            <li class="nav-item">
                <div class="nav-item-divider" style="font-size: 0.65rem; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.75rem 0.75rem 0.35rem; margin-top: 0.5rem; white-space: nowrap;">System</div>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.permissions.*') ? 'active' : '' }}" href="{{ route('superadmin.permissions.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon"><i class="ti ti-shield-lock"></i></span>
                    <span class="nav-link-title">Permissions</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.backups.*') ? 'active' : '' }}" href="{{ route('superadmin.backups.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon"><i class="ti ti-database"></i></span>
                    <span class="nav-link-title">Backups</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.audit-log.*') ? 'active' : '' }}" href="{{ route('superadmin.audit-log.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon"><i class="ti ti-file-text"></i></span>
                    <span class="nav-link-title">Audit Log</span>
                </a>
            </li>

            <li class="nav-item">
                <div class="nav-item-divider" style="font-size: 0.65rem; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.75rem 0.75rem 0.35rem; margin-top: 0.5rem; white-space: nowrap;">Configuration</div>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.settings.*') ? 'active' : '' }}" href="{{ route('superadmin.settings.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon"><i class="ti ti-settings"></i></span>
                    <span class="nav-link-title">System Settings</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.features.*') ? 'active' : '' }}" href="{{ route('superadmin.features.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon"><i class="ti ti-flag"></i></span>
                    <span class="nav-link-title">Features</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('superadmin.billing.*') ? 'active' : '' }}" href="{{ route('superadmin.billing.index') }}" data-turbo="true" data-turbo-cache="false">
                    <span class="nav-link-icon"><i class="ti ti-currency-dollar"></i></span>
                    <span class="nav-link-title">Billing</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-evenly w-100">
            <div class="nav-item" id="theme-toggle-sidebar-container">
                <a href="#" class="nav-link px-0" id="theme-toggle-sidebar" title="Toggle theme">
                    <i class="ti ti-sun fs-2 theme-icon-light"></i>
                    <i class="ti ti-moon fs-2 theme-icon-dark"></i>
                </a>
            </div>
            <a class="nav-link px-0" href="#" onclick="event.preventDefault(); document.getElementById('sa-logout-form').submit();" title="Logout">
                <i class="ti ti-logout fs-2"></i>
            </a>
            <form id="sa-logout-form" method="POST" action="{{ route('superadmin.logout') }}" style="display: none;">
                @csrf
            </form>
        </div>
    </div>
</aside>
