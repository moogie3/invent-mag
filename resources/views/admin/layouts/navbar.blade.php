<header class="navbar navbar-expand-md d-print-none nav-container">
    <div class="{{ $containerClass ?? 'container-xl' }} d-flex align-items-center justify-content-between position-relative">
        <div class="d-flex flex-grow-1 nav-left align-items-center">
            <!-- Sidebar toggle will be in the sidebar itself -->
        </div>

        <!-- Centered Brand Title -->
        <h1 class="navbar-brand position-absolute top-50 start-50 translate-middle text-center" id="navbar-brand-title">
            <a class="nav-link" id="brand-trigger">
                <i class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG
            </a>
        </h1>

        <!-- Navigation Dropdown -->
        <nav class="nav-dropdown d-none d-md-flex" id="nav-dropdown">
            <ul class="d-flex gap-3">
                @foreach ($navigationItems as $item)
                    {{-- Check if the item has a special key, e.g., 'reports' or 'accounting' --}}
                    @if (isset($item['key']) && in_array($item['key'], ['reports', 'accounting']))
                        <li class="nav-item dropdown" id="{{ $item['key'] }}-nav-item">
                            <a class="nav-link dropdown-toggle" href="#navbar-{{ Str::slug($item['title']) }}"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button"
                                aria-expanded="false">
                                <i class="{{ $item['icon'] }}"></i>
                                <span class="nav-link-title">
                                    {{ __($item['title']) }}
                                </span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        {{-- Loop through children without permission check for special items --}}
                                        @foreach ($item['children'] as $child)
                                            <a class="dropdown-item" href="{{ route($child['route']) }}">
                                                <i class="{{ $child['icon'] ?? 'ti ti-point' }}"></i>
                                                <span>{{ __($child['title']) }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </li>
                    @else
                        {{-- For all other items, apply the original permission check --}}
                        @can($item['permission'] ?? null)
                            @if (isset($item['children']))
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#navbar-{{ Str::slug($item['title']) }}"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button"
                                        aria-expanded="false">
                                        <i class="{{ $item['icon'] }}"></i>
                                        <span class="nav-link-title">
                                            {{ __($item['title']) }}
                                        </span>
                                    </a>
                                    <div class="dropdown-menu">
                                        <div class="dropdown-menu-columns">
                                            <div class="dropdown-menu-column">
                                                @foreach ($item['children'] as $child)
                                                    @can($child['permission'] ?? null)
                                                        <a class="dropdown-item" href="{{ route($child['route']) }}">
                                                            {{ __($child['title']) }}
                                                        </a>
                                                    @endcan
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @else
                                <li><a href="{{ route($item['route']) }}"><i class="{{ $item['icon'] }}"></i><span
                                            class="nav-link-title">{{ __($item['title']) }}</span></a></li>
                            @endif
                        @endcan
                    @endif
                @endforeach
            </ul>
        </nav>

        <!-- Mobile Menu Overlay -->
        <div class="nav-overlay" id="nav-overlay"></div>



        <!-- Right Side Icons -->
        <div class="d-flex flex-grow-1 justify-content-end align-items-center" id="navbar-right-content">
            <!-- Theme Toggle Button -->
            <div class="nav-item me-2" id="theme-toggle-navbar-container">
                <a href="#" class="nav-link px-0" id="theme-toggle-navbar">
                    <i class="ti ti-sun fs-2 theme-icon-light"></i>
                    <i class="ti ti-moon fs-2 theme-icon-dark" style="display: none;"></i>
                </a>
            </div>

            <!-- Modified Notification Bell Section -->
            <div class="nav-item dropdown me-3">
                <a href="#" class="nav-link px-2 position-relative" data-bs-toggle="dropdown"
                    aria-expanded="false" id="notification-bell">
                    <i class="ti ti-bell fs-2"></i>

                    @if (isset($totalNotificationCount) && $totalNotificationCount > 0)
                        <span id="notification-dot"
                            class="position-absolute bg-danger border border-light rounded-circle">
                        </span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown p-0"
                    style="width: 400px; max-height: 520px;">

                    <!-- Notification Header -->
                    <div class="notification-header d-flex justify-content-between align-items-center px-3 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="mb-0 fw-bold">{{ __('plan.notif_header') }}</h3>
                        </div>
                        @if (isset($totalNotificationCount) && $totalNotificationCount > 0)
                            <span class="badge bg-primary rounded-pill px-2 py-1 notification-count-badge">{{ $totalNotificationCount }}</span>
                        @endif
                    </div>

                    <!-- Notification Tabs -->
                    <div class="notification-tabs">
                        <ul class="nav nav-tabs nav-fill" id="notificationTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-2 position-relative" id="financial-tab" data-bs-toggle="tab"
                                    data-bs-target="#financial" type="button" role="tab">
                                    <i class="ti ti-receipt me-1"></i>{{ __('plan.notif_tab_orders') }}
                                    @php
                                        $financialCount = isset($notifications) ? $notifications->filter(fn($n) => in_array($n['type'], ['purchase', 'sales']))->count() : 0;
                                    @endphp
                                    @if ($financialCount > 0)
                                        <span class="notification-tab-badge">{{ $financialCount }}</span>
                                    @endif
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-2 position-relative" id="inventory-tab" data-bs-toggle="tab"
                                    data-bs-target="#inventory" type="button" role="tab">
                                    <i class="ti ti-package me-1"></i>{{ __('plan.notif_tab_inventory') }}
                                    @php
                                        $inventoryCount = isset($notifications) ? $notifications->filter(fn($n) => $n['type'] === 'product')->count() : 0;
                                    @endphp
                                    @if ($inventoryCount > 0)
                                        <span class="notification-tab-badge">{{ $inventoryCount }}</span>
                                    @endif
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-2 position-relative" id="system-tab" data-bs-toggle="tab"
                                    data-bs-target="#system" type="button" role="tab">
                                    <i class="ti ti-adjustments me-1"></i>{{ __('plan.notif_tab_system') }}
                                    @if (isset($systemNotificationCount) && $systemNotificationCount > 0)
                                        <span class="notification-tab-badge">{{ $systemNotificationCount }}</span>
                                    @endif
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content p-0" style="max-height: 350px; overflow-y: auto;">
                            <!-- Financial Tab Content -->
                            <div class="tab-pane fade show active" id="financial" role="tabpanel">
                                <div class="notification-group py-2">
                                    <div class="notification-section-label px-3 py-1">
                                        <i class="ti ti-truck-delivery me-1"></i>{{ __('plan.notif_purchase_orders') }}
                                    </div>

                                    @if (isset($notifications) &&
                                            $notifications->filter(function ($n) {
                                                    return $n['type'] == 'purchase';
                                                })->count() > 0)
                                        @php
                                            $purchaseNotifications = $notifications->filter(function ($n) {
                                                return $n['type'] == 'purchase';
                                            });
                                            $purchaseCount = $purchaseNotifications->count();
                                        @endphp

                                        @foreach ($purchaseNotifications->take(3) as $notification)
                                            <a href="{{ $notification['route'] }}"
                                                class="dropdown-item d-flex p-2 notification-item">
                                                <div class="flex-shrink-0 me-2 mt-1">
                                                    <span
                                                        class="notification-icon-wrapper bg-{{ str_replace('text-', '', $notification['status_badge']) }}-lt">
                                                        <i class="{{ $notification['status_icon'] }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="mb-0 text-truncate fw-medium notification-title">
                                                        {{ $notification['title'] }}</p>
                                                    <div class="text-muted small text-truncate notification-desc">
                                                        {{ $notification['description'] }}</div>
                                                </div>
                                            </a>
                                        @endforeach

                                        @if ($purchaseCount > 3)
                                            <div class="notification-more-link text-center py-2">
                                                {{ __('plan.notif_more_purchase', ['count' => $purchaseCount - 3]) }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="notification-empty py-3">
                                            <i class="ti ti-checks text-success"></i>
                                            <span>{{ __('plan.notif_no_purchase') }}</span>
                                        </div>
                                    @endif

                                    <div class="notification-section-label px-3 py-1 mt-1">
                                        <i class="ti ti-receipt me-1"></i>{{ __('plan.notif_sales') }}
                                    </div>

                                    @if (isset($notifications) &&
                                            $notifications->filter(function ($n) {
                                                    return $n['type'] == 'sales';
                                                })->count() > 0)
                                        @php
                                            $salesNotifications = $notifications->filter(function ($n) {
                                                return $n['type'] == 'sales';
                                            });
                                            $salesCount = $salesNotifications->count();
                                        @endphp

                                        @foreach ($salesNotifications->take(3) as $notification)
                                            <a href="{{ $notification['route'] }}"
                                                class="dropdown-item d-flex p-2 notification-item">
                                                <div class="flex-shrink-0 me-2 mt-1">
                                                    <span
                                                        class="notification-icon-wrapper bg-{{ str_replace('text-', '', $notification['status_badge']) }}-lt">
                                                        <i class="{{ $notification['status_icon'] }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="mb-0 text-truncate fw-medium notification-title">
                                                        {{ $notification['title'] }}</p>
                                                    <div class="text-muted small text-truncate notification-desc">
                                                        {{ $notification['description'] }}</div>
                                                </div>
                                            </a>
                                        @endforeach

                                        @if ($salesCount > 3)
                                            <div class="notification-more-link text-center py-2">
                                                {{ __('plan.notif_more_sales', ['count' => $salesCount - 3]) }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="notification-empty py-3">
                                            <i class="ti ti-checks text-success"></i>
                                            <span>{{ __('plan.notif_no_sales') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Inventory Tab Content -->
                            <div class="tab-pane fade" id="inventory" role="tabpanel">
                                <div class="notification-group py-2">
                                    <div class="notification-section-label px-3 py-1">
                                        <i class="ti ti-alert-triangle me-1"></i>{{ __('plan.notif_low_stock') }}
                                    </div>

                                    @if (isset($notifications) &&
                                            $notifications->filter(function ($n) {
                                                    return $n['type'] == 'product' && $n['status'] == 'Low Stock';
                                                })->count() > 0)
                                        @php
                                            $lowStockNotifications = $notifications->filter(function ($n) {
                                                return $n['type'] == 'product' && $n['status'] == 'Low Stock';
                                            });
                                            $lowStockCount = $lowStockNotifications->count();
                                        @endphp

                                        @foreach ($lowStockNotifications->take(3) as $notification)
                                            <a href="{{ $notification['route'] }}"
                                                class="dropdown-item d-flex p-2 notification-item">
                                                <div class="flex-shrink-0 me-2 mt-1">
                                                    <span
                                                        class="notification-icon-wrapper bg-{{ str_replace('text-', '', $notification['status_badge']) }}-lt">
                                                        <i class="{{ $notification['status_icon'] }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="mb-0 text-truncate fw-medium notification-title">
                                                        {{ $notification['title'] }}</p>
                                                    <div class="text-muted small text-truncate notification-desc">
                                                        {{ $notification['description'] }}</div>
                                                </div>
                                            </a>
                                        @endforeach

                                        @if ($lowStockCount > 3)
                                            <div class="notification-more-link text-center py-2">
                                                {{ __('plan.notif_more_low_stock', ['count' => $lowStockCount - 3]) }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="notification-empty py-3">
                                            <i class="ti ti-checks text-success"></i>
                                            <span>{{ __('plan.notif_no_low_stock') }}</span>
                                        </div>
                                    @endif

                                    <div class="notification-section-label px-3 py-1 mt-1">
                                        <i class="ti ti-calendar-time me-1"></i>{{ __('plan.notif_expiring') }}
                                    </div>

                                    @if (isset($notifications) &&
                                            $notifications->filter(function ($n) {
                                                    return $n['type'] == 'product' && isset($n['days_remaining']) && $n['days_remaining'] <= 30;
                                                })->count() > 0)
                                        @php
                                            $expiringNotifications = $notifications->filter(function ($n) {
                                                return $n['type'] == 'product' && isset($n['days_remaining']) && $n['days_remaining'] <= 30;
                                            });
                                            $expiringCount = $expiringNotifications->count();
                                        @endphp

                                        @foreach ($expiringNotifications->take(3) as $notification)
                                            <a href="{{ $notification['route'] }}"
                                                class="dropdown-item d-flex p-2 notification-item">
                                                <div class="flex-shrink-0 me-2 mt-1">
                                                    <span
                                                        class="notification-icon-wrapper bg-{{ str_replace('text-', '', $notification['status_badge']) }}-lt">
                                                        <i class="{{ $notification['status_icon'] }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="mb-0 text-truncate fw-medium notification-title">
                                                        {{ $notification['title'] }}</p>
                                                    <div class="text-muted small text-truncate notification-desc">
                                                        {{ $notification['description'] }}</div>
                                                </div>
                                            </a>
                                        @endforeach

                                        @if ($expiringCount > 3)
                                            <div class="notification-more-link text-center py-2">
                                                {{ __('plan.notif_more_expiring', ['count' => $expiringCount - 3]) }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="notification-empty py-3">
                                            <i class="ti ti-checks text-success"></i>
                                            <span>{{ __('plan.notif_no_expiring') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- System Tab Content -->
                            <div class="tab-pane fade" id="system" role="tabpanel">
                                <div class="notification-group py-2">
                                    <div class="notification-section-label px-3 py-1">
                                        <i class="ti ti-info-circle me-1"></i>{{ __('plan.notif_system_updates') }}
                                    </div>

                                    @if (isset($systemNotifications) && $systemNotifications->count() > 0)
                                        @foreach ($systemNotifications as $sysNotif)
                                            <div class="system-notification-item d-flex align-items-start px-3 py-2">
                                                <div class="flex-shrink-0 me-3">
                                                    <span class="system-notif-icon system-notif-icon-{{ $sysNotif['color'] }}">
                                                        <i class="{{ $sysNotif['icon'] }}"></i>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="fw-semibold small notification-title">{{ $sysNotif['title'] }}</span>
                                                        @if ($sysNotif['urgency'] === 'critical')
                                                            <span class="system-notif-urgency-dot bg-danger"></span>
                                                        @elseif ($sysNotif['urgency'] === 'high')
                                                            <span class="system-notif-urgency-dot bg-warning"></span>
                                                        @endif
                                                    </div>
                                                    <p class="text-muted small mb-2 notification-desc lh-sm">{{ $sysNotif['description'] }}</p>
                                                    @if (isset($sysNotif['action_route']))
                                                        <a href="{{ $sysNotif['action_route'] }}"
                                                           class="btn btn-sm btn-{{ $sysNotif['color'] }} rounded-pill px-3 py-1 system-notif-action">
                                                            <i class="ti ti-arrow-right me-1" style="font-size: 0.7rem;"></i>
                                                            <span>{{ $sysNotif['action_label'] }}</span>
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="notification-empty py-4">
                                            <div class="notification-empty-icon mb-2">
                                                <i class="ti ti-circle-check"></i>
                                            </div>
                                            <span>{{ __('plan.notif_no_system') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer with View All Link -->
                    <div class="notification-footer p-2 text-center">
                        <a href="{{ route('admin.notifications') }}" class="notification-footer-link">
                            <i class="ti ti-external-link me-1"></i>{{ __('plan.notif_view_all') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="nav-item dropdown ms-2" id="avatar-dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                    @if (Auth::check())
                    <span class="avatar avatar-sm"
                        style="background-image: url('{{ Auth::user()->getRawOriginal('avatar') ? Auth::user()->avatar : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=random' }}');"></span>
                    <div class="d-none d-xl-block ps-2">
                        <div>{{ Auth::user()->name }}</div>
                        <div class="mt-1 small text-secondary">{{ Auth::user()->getRoleNames()->first() }}</div>
                    </div>
                @else
                        <span class="avatar avatar-sm"
                            style="background-image: url('{{ asset('storage/default-avatar.png') }}');"></span>
                        <div class="d-none d-xl-block ps-2">
                            <div>{{ __('messages.guest') }}</div>
                            <div class="mt-1 small text-secondary">{{ __('messages.not_logged_in') }}</div>
                        </div>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-arrow">
                    <a href="{{ route('admin.setting.profile.edit') }}" class="dropdown-item">
                        <i class="ti ti-settings me-2"></i>{{ __('messages.settings') }}
                    </a>
                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                    <a href="#" class="dropdown-item"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ti ti-logout me-2"></i>{{ __('messages.logout') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

@vite('resources/css/navbar.css')
@vite('resources/js/admin/layouts/navbar.js')
