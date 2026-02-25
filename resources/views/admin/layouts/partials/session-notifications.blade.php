@php
    $isAuthPage = request()->is(
        'admin/login',
        'admin/register',
        'forgot-password',
        'admin/login/post',
        'admin/register/post',
        'password/email',
    );
@endphp

<div id="session-notification-data" data-success-message="{{ session('success') ?? session('status') }}"
    data-error-message="{{ session('error') ?? $errors->first() }}" data-is-auth-page="{{ json_encode($isAuthPage) }}" style="display: none;">
</div>
