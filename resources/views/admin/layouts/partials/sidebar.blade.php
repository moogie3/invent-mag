<aside class="sidebar">
    <div class="sidebar-header">
        <button type="button" id="sidebar-toggle" class="navbar-brand">
            <div class="brand-icon">
                <i class="ti ti-brand-minecraft brand-icon"></i>
            </div>
            <span class="brand-text">Invent-MAG</span>
        </button>
        <!-- Lock button will be added here by JavaScript -->
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
                <a href="?theme=dark" class="nav-link px-1 hide-theme-dark" data-bs-toggle="tooltip"
                    data-bs-placement="top">
                    <i class="ti ti-moon fs-2"></i>
                </a>
                <a href="?theme=light" class="nav-link px-1 hide-theme-light" data-bs-toggle="tooltip"
                    data-bs-placement="top">
                    <i class="ti ti-sun fs-2"></i>
                </a>

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
                @can($item['permission'])
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route($item['route']) }}">
                            <div class="nav-link-icon">
                                <i class="{{ $item['icon'] }}"></i>
                            </div>
                            <span class="nav-link-title">{{ $item['title'] }}</span>
                            @if (isset($item['children']))
                                <div class="nav-link-arrow">
                                    <i class="ti ti-chevron-right"></i>
                                </div>
                            @endif
                        </a>
                        @if (isset($item['children']))
                            <ul class="nav-submenu">
                                @foreach ($item['children'] as $child)
                                    @can($child['permission'])
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route($child['route']) }}">
                                                <div class="nav-link-icon">
                                                    <i class="{{ $child['icon'] }}"></i>
                                                </div>
                                                <span class="nav-link-title">{{ $child['title'] }}</span>
                                            </a>
                                        </li>
                                    @endcan
                                @endforeach
                            </ul>
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
