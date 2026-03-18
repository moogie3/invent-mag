@php
    $isAuthPage = request()->is(
        'admin/login',
        'admin/register',
        'forgot-password',
        'admin/login/post',
        'admin/register/post',
        'password/email',
        'admin/email/verify',
    );

    // Map known status keys to their translation keys for proper multilingual support
    $statusTranslations = [
        'verification-link-sent' => __('messages.verification_link_sent'),
    ];

    $sessionStatus = session('status');
    $translatedStatus = $statusTranslations[$sessionStatus] ?? $sessionStatus;
    
    // Use session('success') if set, otherwise use translated status
    $successMessage = session('success');
    if (empty($successMessage)) {
        $successMessage = $translatedStatus;
    }
@endphp

<div id="session-notification-data" data-success-message="{{ $successMessage }}"
    data-error-message="{{ session('error') ?? $errors->first() }}" data-is-auth-page="{{ json_encode($isAuthPage) }}" style="display: none;">
</div>
