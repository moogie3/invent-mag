@extends('mobile.layouts.app')

@section('title', __('messages.dashboard'))
@section('page', 'dashboard')

@php
    $hour = (int) now()->format('G');
    $greetingKey = $hour < 12
        ? 'mobile_greeting_morning'
        : ($hour < 18 ? 'mobile_greeting_afternoon' : 'mobile_greeting_evening');
@endphp

@section('content')
    <div id="install-banner" class="install-banner" hidden>
        <span>{{ __('messages.mobile_install_hint') }}</span>
        <button type="button" class="btn btn-sm btn-primary" id="install-button">
            {{ __('messages.mobile_install') }}
        </button>
    </div>

    <header class="dash-head">
        <h1 class="dash-greeting">{{ __($greetingKey) }}</h1>
        <p class="dash-date">{{ now()->translatedFormat('l, j F Y') }}</p>
    </header>

    <div class="seg" role="group" aria-label="{{ __('messages.date_range') }}">
        <button type="button" class="seg-btn" data-range="today">{{ __('messages.mobile_range_today') }}</button>
        <button type="button" class="seg-btn" data-range="this_week">{{ __('messages.mobile_range_week') }}</button>
        <button type="button" class="seg-btn is-active" data-range="this_month"
            aria-pressed="true">{{ __('messages.mobile_range_month') }}</button>
        <button type="button" class="seg-btn" data-range="this_year">{{ __('messages.mobile_range_year') }}</button>
    </div>

    {{-- Skeleton, not a spinner: the layout is already known, so the page
         reads as loading rather than as an empty screen with a rotating dot. --}}
    <div id="dashboard-loading" class="skeleton-wrap" role="status"
        aria-label="{{ __('messages.mobile_loading') }}" aria-live="polite">
        <div class="sk-grid">
            <div class="skeleton sk-cell"></div>
            <div class="skeleton sk-cell"></div>
            <div class="skeleton sk-cell"></div>
            <div class="skeleton sk-cell"></div>
        </div>
        <div class="skeleton sk-block"></div>
        <div class="skeleton sk-short"></div>
    </div>

    <div id="dashboard-error" class="card" hidden>
        <p class="error-text" id="dashboard-error-text"></p>
    </div>

    <div id="dashboard-body" hidden>
        <section class="dash-section">
            <h2 class="section-title">{{ __('messages.mobile_overview') }}</h2>
            <div class="kpi-grid" id="kpi-grid"></div>
        </section>

        <section class="dash-section">
            <h2 class="section-title">{{ __('messages.summary') }}</h2>
            <div class="card card-flush">
                <div class="stat-list" id="stat-list"></div>
            </div>
        </section>

        <section class="dash-section">
            <h2 class="section-title">{{ __('messages.recent_transactions') }}</h2>
            <div class="card card-flush">
                <div id="recent-sales"></div>
            </div>
        </section>
    </div>
@endsection
