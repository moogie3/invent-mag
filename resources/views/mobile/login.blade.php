@extends('mobile.layouts.app')

@section('title', __('messages.login'))
@section('page', 'login')
@section('bare', true)

@section('content')
    <div class="login-wrap">
        <div class="login-panel">
            <header class="login-brand">
                <img src="{{ asset('pwa/icon-192.png') }}" alt="" width="54" height="54" />
                <div class="login-wordmark">
                    <h1>Invent-MAG</h1>
                    <p>{{ __('messages.mobile_login_subtitle') }}</p>
                </div>
            </header>

            <form id="login-form" class="login-form" novalidate>
                <div class="field">
                    <label for="login-email">{{ __('messages.email') }}</label>
                    <input class="input" type="email" id="login-email" name="email"
                        placeholder="{{ __('messages.mobile_email_placeholder') }}" autocomplete="username"
                        inputmode="email" spellcheck="false" autocapitalize="none" required />
                </div>

                <div class="field">
                    <label for="login-password">{{ __('messages.password') }}</label>
                    <input class="input" type="password" id="login-password" name="password"
                        autocomplete="current-password" required />
                </div>

                <div class="alert" id="login-error" role="alert" hidden>
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v6" />
                        <path d="M12 16.5v.01" />
                    </svg>
                    <span id="login-error-text"></span>
                </div>

                <button class="btn btn-primary btn-block btn-lg" type="submit" id="login-submit">
                    <span class="btn-spinner" aria-hidden="true"></span>
                    <span class="btn-label">{{ __('messages.login_button') }}</span>
                </button>
            </form>

            <p class="login-foot">{{ __('messages.mobile_login_help') }}</p>
        </div>
    </div>
@endsection
