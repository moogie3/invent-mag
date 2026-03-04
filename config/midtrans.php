<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Midtrans Snap payment gateway integration.
    | Get your keys from https://dashboard.midtrans.com
    |
    */

    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),
    
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
];
