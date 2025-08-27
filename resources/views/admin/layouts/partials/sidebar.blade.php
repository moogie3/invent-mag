<!-- Modern Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="navbar-brand">
            <div class="brand-icon">
                <i class="ti ti-brand-minecraft"></i>
            </div>
            <span class="brand-text">Invent-MAG</span>
        </a>
        <button class="sidebar-toggle" type="button" id="sidebar-toggle-internal">
            <i class="ti ti-menu-deep"></i>
        </button>
    </div>

    <!-- User Details moved to top after header -->
    <div class="sidebar-user">
        <div class="user-info">
            <div class="user-avatar">
                @if (Auth::check())
                    <span class="avatar avatar-sm"
                        style="background-image: url('{{ Auth::user()->avatar && Storage::disk('public')->exists(Auth::user()->avatar) ? asset('storage/' . Auth::user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=random' }}');"></span>
                @else
                    <span class="avatar avatar-sm"
                        style="background-image: url('{{ asset('storage/default-avatar.png') }}');"></span>
                @endif
            </div>
            <div class="user-details">
                @if (Auth::check())
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    <span class="user-role">{{ Auth::user()->getRoleNames()->first() }}</span>
                @else
                    <span class="user-name">Guest</span>
                    <span class="user-role">Not logged in</span>
                @endif
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
</aside>
