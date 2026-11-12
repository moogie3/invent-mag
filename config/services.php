<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY') ?: env('RESEND_API_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Mekari Klikpajak PJAP — e-Faktur Phase 2 (direct DJP submission).
    // Per-tenant HMAC credentials live on the tenants table (Client ID /
    // Client Secret from developers.mekari.com); these are the
    // environment-level endpoints (sandbox is default for safety).
    'klikpajak' => [
        'sandbox_url' => env('KLIKPAJAK_SANDBOX_URL', 'https://sandbox-api.mekari.com/v2/klikpajak'),
        'production_url' => env('KLIKPAJAK_PRODUCTION_URL', 'https://api.mekari.com/v2/klikpajak'),
    ],

];
