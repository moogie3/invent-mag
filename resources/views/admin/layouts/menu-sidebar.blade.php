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
                        style="background-image: url('{{ Auth::user()->getRawOriginal('avatar') ? Auth::user()->avatar : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=random' }}');"></span>
                @else
                    <span class="avatar avatar-sm"
                        style="background-image: url('{{ asset('storage/default-avatar.png') }}');"></span>
                @endif
            </a>
            <div class="user-details">
                @if (Auth::check())
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    <span class="user-role">{{ Auth::user()->getRoleNames()->first() }}</span>
                    @php
                        $tenant = app('currentTenant');
                        $currentPlan = $tenant?->plan;
                    @endphp
                    @if($currentPlan)
                        <span class="badge bg-{{ $currentPlan->slug === 'enterprise' ? 'purple' : ($currentPlan->slug === 'professional' ? 'blue' : 'teal') }} text-white mt-1" style="font-size: 0.65rem;">{{ $currentPlan->slug === 'professional' ? 'Pro' : $currentPlan->name }}</span>
                    @endif
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
                        <i class="ti ti-moon fs-2 theme-icon-dark"></i>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.notifications') }}" class="nav-link px-1 position-relative"
                        id="notification-bell-sidebar" data-turbolinks-action="replace">
                        <i class="ti ti-bell fs-2"></i>
                        @if (isset($totalNotificationCount) && $totalNotificationCount > 0)
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
            @foreach ($navigationItems as $item)
                @can($item['permission'] ?? null)
                    <li class="nav-item @if(isset($item['children'])) dropdown @endif">
                        <a class="nav-link {{ isset($item['route']) && request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ isset($item['route']) ? route($item['route']) : '#' }}" data-turbo="true" data-turbo-cache="false" @if(isset($item['children'])) data-bs-toggle="collapse" data-bs-target="#submenu-{{ $loop->index }}" role="button" aria-expanded="false" aria-controls="submenu-{{ $loop->index }}" @endif>
                            @if(isset($item['icon']))
                            <div class="nav-link-icon">
                                <i class="{{ $item['icon'] }}"></i>
                            </div>
                            @endif
                            <span class="nav-link-title">{{ __($item['title'] ?? '') }}</span>
                            @if(isset($item['children']))
                            <div class="nav-link-arrow">
                                <i class="ti ti-chevron-down"></i>
                            </div>
                            @endif
                        </a>
                        @if (isset($item['children']))
                            <div class="collapse" id="submenu-{{ $loop->index }}">
                                <ul class="nav-submenu">
                                    @foreach ($item['children'] as $child)
                                        @can($child['permission'] ?? null)
                                            <li class="nav-item">
                                                <a class="nav-link {{ isset($child['route']) && request()->routeIs($child['route']) ? 'active' : '' }}" href="{{ isset($child['route']) ? route($child['route']) : '#' }}" data-turbo="true" data-turbo-cache="false">
                                                    @if(isset($child['icon']))
                                                    <div class="nav-link-icon">
                                                        <i class="{{ $child['icon'] }}"></i>
                                                    </div>
                                                    @endif
                                                    <span class="nav-link-title">{{ __($child['title'] ?? '') }}</span>
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
                <a class="nav-link trigger-tutorial-modal-general" href="#">
                    <div class="nav-link-icon">
                        <i class="ti ti-help"></i>
                    </div>
                    <span class="nav-link-title">{{ __('messages.tutorial') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.setting.profile.edit') }}" data-turbo="true" data-turbo-cache="false" data-bs-toggle="tooltip"
                    data-bs-placement="top">
                    <div class="nav-link-icon">
                        <i class="ti ti-settings"></i>
                    </div>
                    <span class="nav-link-title">{{ __('messages.settings') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#" data-turbo-cache="false" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <div class="nav-link-icon">
                        <i class="ti ti-logout"></i>
                    </div>
                    <span class="nav-link-title">{{ __('messages.logout') }}</span>
                </a>
                <form id="logout-form" method="POST" action="{{ route('admin.logout') }}" style="display: none;">
                    @csrf
                </form>
            </li>
        </ul>
    </div>
</aside>
