<aside class="sidebar">
    <div class="sidebar-header">
        <button type="button" id="sidebar-toggle" class="navbar-brand">
            <div class="brand-icon">
                <i class="ti ti-brand-minecraft brand-icon"></i>
            </div>
            <span class="brand-text">Invent-MAG</span>
        </button>
    </div>

    <!-- User Details with actions on the right -->
    <div class="sidebar-user">
        <div class="user-info">
            <a href="{{ route('admin.setting.profile.edit') }}" class="user-avatar">
                @if (Auth::check())
                    <span class="avatar avatar-sm"
                        style="background-image: url('{{ Auth::user()->avatar && Storage::disk('public')->exists(Auth::user()->avatar) ? asset('storage/' . Auth::user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=random' }}');"></span>
                @else
                    <span class="avatar avatar-sm"
                        style="background-image: url('{{ asset('storage/default-avatar.png') }}');"></span>
                @endif
            </a>
            <div class="user-details">
                @if (Auth::check())
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    <span class="user-role">{{ Auth::user()->getRoleNames()->first() }}</span>
                @else
                    <span class="user-name">Guest</span>
                    <span class="user-role">Not logged in</span>
                @endif
            </div>
            <!-- Actions moved to the right side -->
            <div class="sidebar-actions">
                <!-- Theme Toggle Button -->
                <div class="nav-item me-2" id="theme-toggle-sidebar-container">
                    <a href="#" class="nav-link px-0" id="theme-toggle-sidebar">
                        <i class="ti ti-sun fs-2 theme-icon-light"></i>
                        <i class="ti ti-moon fs-2 theme-icon-dark" style="display: none;"></i>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.setting.notifications') }}" class="nav-link px-1 position-relative"
                        id="notification-bell-sidebar" data-turbolinks-action="replace">
                        <i class="ti ti-bell fs-2"></i>
                        @if (isset($notificationCount) && $notificationCount > 0)
                            <span id="notification-dot-sidebar"
                                class="position-absolute bg-danger border border-light rounded-circle"></span>
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="navbar-nav">
            @foreach (config('navigation.menu') as $item)
                @can($item['permission'] ?? null)
                    <li class="nav-item @if(isset($item['children'])) dropdown @endif">
                        <a class="nav-link @if(isset($item['children'])) dropdown-toggle @endif" href="{{ isset($item['route']) ? route($item['route']) : '#' }}" @if(isset($item['children'])) data-bs-toggle="collapse" data-bs-target="#submenu-{{ $loop->index }}" role="button" aria-expanded="false" aria-controls="submenu-{{ $loop->index }}" @endif>
                            @if(isset($item['icon']))
                            <div class="nav-link-icon">
                                <i class="{{ $item['icon'] }}"></i>
                            </div>
                            @endif
                            <span class="nav-link-title">{{ $item['title'] ?? '' }}</span>
                        </a>
                        @if (isset($item['children']))
                            <div class="collapse" id="submenu-{{ $loop->index }}">
                                <ul class="nav-submenu">
                                    @foreach ($item['children'] as $child)
                                        @can($child['permission'] ?? null)
                                            <li class="nav-item">
                                                <a class="nav-link" href="{{ isset($child['route']) ? route($child['route']) : '#' }}">
                                                    @if(isset($child['icon']))
                                                    <div class="nav-link-icon">
                                                        <i class="{{ $child['icon'] }}"></i>
                                                    </div>
                                                    @endif
                                                    <span class="nav-link-title">{{ $child['title'] ?? '' }}</span>
                                                </a>
                                            </li>
                                        @endcan
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </li>
                @endcan
            @endforeach
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-divider"></div>
        <ul class="navbar-nav">

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.setting.profile.edit') }}" data-bs-toggle="tooltip"
                    data-bs-placement="top">
                    <div class="nav-link-icon">
                        <i class="ti ti-settings"></i>
                    </div>
                    <span class="nav-link-title">Settings</span>
                </a>
            </li>

            <!-- New User Management Link -->
            @can('view-users')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.create') || request()->routeIs('admin.users.edit') ? 'active' : '' }}"
                        href="{{ route('admin.users.index') }}" data-bs-toggle="tooltip" data-bs-placement="top">
                        <div class="nav-link-icon">
                            <i class="ti ti-users"></i>
                        </div>
                        <span class="nav-link-title">User Management</span>
                    </a>
                </li>
            @endcan

            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="tooltip" data-bs-placement="top"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <div class="nav-link-icon">
                        <i class="ti ti-logout"></i>
                    </div>
                    <span class="nav-link-title">Logout</span>
                </a>
                <form id="logout-form" method="POST" action="{{ route('admin.logout') }}" style="display: none;">
                    @csrf
                </form>
            </li>
        </ul>
    </div>
</aside>
