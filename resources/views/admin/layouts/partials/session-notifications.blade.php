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

<div id="session-notification-data" data-success-message="{{ session('success') }}"
    data-error-message="{{ $errors->first() }}" data-is-auth-page="{{ json_encode($isAuthPage) }}" style="display: none;">
</div>
