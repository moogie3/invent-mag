<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EFakturServiceProvider;
use App\Providers\NavigationServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    NavigationServiceProvider::class,
    EFakturServiceProvider::class,
];
