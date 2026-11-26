@php
    $currencySettings = App\Helpers\CurrencyHelper::getSettings();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="robots" content="noindex, nofollow, noarchive" />
    <meta name="theme-color" content="#206bc4" />
    <link rel="manifest" href="{{ asset('manifest.json') }}" />
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" href="{{ asset('pwa/apple-touch-icon.png') }}" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="Invent-MAG" />
    {{--
        Inter matches the web panel (admin/layouts/base.blade.php) so dense UI
        — forms, lists, receipt rows — reads identically on both surfaces.
        Manrope carries display type only: page titles, KPI figures, the login
        wordmark. Two families, one request, display=swap so a cold or offline
        start never blocks first paint (the SW caches these after first visit).
    --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap"
        rel="stylesheet" />
    <title>Invent-MAG | @yield('title', 'App')</title>
    @vite(['resources/css/mobile.css', 'resources/js/mobile.js'])
</head>

<body data-page="@yield('page', 'dashboard')" data-currency-settings='{{ json_encode([
    'locale' => $currencySettings->locale,
    'currency_code' => $currencySettings->currency_code,
    'currency_symbol' => $currencySettings->currency_symbol,
    'decimal_places' => $currencySettings->decimal_places,
    'decimal_separator' => $currencySettings->decimal_separator,
    'thousand_separator' => $currencySettings->thousand_separator,
    'position' => $currencySettings->position,
]) }}'
    data-msg-session-expired="{{ __('messages.mobile_session_expired') }}"
    data-msg-invalid-credentials="{{ __('messages.mobile_invalid_credentials') }}"
    data-msg-network="{{ __('messages.mobile_network_error') }}"
    data-msg-no-transactions="{{ __('messages.mobile_no_transactions') }}">
    @hasSection('bare')
        @yield('content')
    @else
        <header class="topbar">
            <div class="topbar-title">@yield('title', 'Invent-MAG')</div>
            <div class="topbar-actions">@yield('actions')</div>
        </header>

        <main class="main">
            @yield('content')
        </main>

        <nav class="tabbar">
            <a href="{{ route('mobile.dashboard') }}"
                class="{{ request()->routeIs('mobile.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="9" rx="1" />
                    <rect x="14" y="3" width="7" height="5" rx="1" />
                    <rect x="14" y="12" width="7" height="9" rx="1" />
                    <rect x="3" y="16" width="7" height="5" rx="1" />
                </svg>
                {{ __('messages.dashboard') }}
            </a>
            <a href="{{ route('mobile.pos') }}" class="{{ request()->routeIs('mobile.pos') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 5h2l1.6 9.2a2 2 0 0 0 2 1.8h7.9a2 2 0 0 0 2-1.6L20 8H6" />
                    <circle cx="10" cy="20" r="1.4" />
                    <circle cx="17" cy="20" r="1.4" />
                </svg>
                {{ __('messages.pos') }}
            </a>
            <a href="{{ route('mobile.products') }}" class="{{ request()->routeIs('mobile.products') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                    <path d="M3.3 7 12 12l8.7-5" />
                    <path d="M12 22V12" />
                </svg>
                {{ __('messages.products') }}
            </a>
        </nav>
    @endif

    <div id="toast-host" aria-live="polite"></div>
</body>

</html>
