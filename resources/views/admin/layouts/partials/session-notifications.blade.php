@php
    $isAuthPage = request()->is(
        'admin/login',
        'admin/register',
        'admin/forgot-password',
        'admin/reset-password/*',
        'admin/email/verify',
        'admin/email/verified',
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

    // Safely resolve error message — never output PHP null as the string "null"
    $errorMessage = session('error') ?? ($errors->any() ? $errors->first() : null);
@endphp

<div id="session-notification-data"
    data-success-message="{{ $successMessage ?? '' }}"
    data-error-message="{{ $errorMessage ?? '' }}"
    data-is-auth-page="{{ json_encode($isAuthPage) }}"
    style="display: none;">
</div>
