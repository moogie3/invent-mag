<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Billing Settings
    |--------------------------------------------------------------------------
    |
    | Platform-wide billing configuration.
    |
    | fallback_rate : USD → IDR rate used when no billing_settings row exists yet.
    |                 The live rate is managed by the super admin (BillingSetting model).
    | tax_rate      : PPN (VAT) rate applied to subscription charges (Indonesia).
    |
    */

    'fallback_rate' => 16000,

    'tax_rate' => 0.12,

];
