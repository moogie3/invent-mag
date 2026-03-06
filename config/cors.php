<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'register', 'login'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_map(function($item) {
        return trim($item, " \t\n\r\0\x0B\"'");
    }, explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:4321'))),

    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.vercel\.app$/',           // Vercel preview deployments
        '/^https:\/\/.*\.invent-mag\.web\.id$/',   // All *.invent-mag.web.id subdomains (tenant + API)
        '/^https:\/\/invent-mag\.web\.id$/',        // Apex domain
        '/^https?:\/\/.*\.nip\.io(:[0-9]+)?$/',    // Local nip.io testing
    ],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
