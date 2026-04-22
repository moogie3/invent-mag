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
    // Never pass through the literal string "null"
    $translatedStatus = ($sessionStatus && $sessionStatus !== 'null')
        ? ($statusTranslations[$sessionStatus] ?? $sessionStatus)
        : null;

    // Use session('success') if set and not "null", otherwise use translated status
    $rawSuccess = session('success');
    $successMessage = ($rawSuccess && $rawSuccess !== 'null') ? $rawSuccess : $translatedStatus;

    // Safely resolve error message — never output PHP null or "null" string
    $rawError = session('error');
    $errorMessage = ($rawError && $rawError !== 'null')
        ? $rawError
        : ($errors->any() ? $errors->first() : null);

    // Final safety — convert any remaining null to empty string for output
    $successMessage = $successMessage ?: '';
    $errorMessage = $errorMessage ?: '';
@endphp

<div id="session-notification-data"
    data-success-message="{{ $successMessage }}"
    data-error-message="{{ $errorMessage }}"
    data-is-auth-page="{{ json_encode($isAuthPage) }}"
    style="display: none;">
</div>
