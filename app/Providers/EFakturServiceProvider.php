<?php

namespace App\Providers;

use App\Contracts\PjapInterface;
use App\Services\Pjap\KlikpajakService;
use Illuminate\Support\ServiceProvider;

class EFakturServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind PjapInterface per-tenant: each tenant has its own Klikpajak
        // credentials. Resolved lazily inside job handle() AFTER the tenant
        // has been made current, so queued submissions work without HTTP
        // context (same pattern as DownloadShopeeCoverImages).
        $this->app->bind(PjapInterface::class, function ($app) {
            $tenant = $app->make('currentTenant');

            $useSandbox = $tenant->klikpajak_use_sandbox ?? true;
            $baseUrl    = $useSandbox
                ? config('services.klikpajak.sandbox_url', 'https://api-sandbox.klikpajak.id')
                : config('services.klikpajak.production_url', 'https://api.klikpajak.id');

            return new KlikpajakService([
                'base_url'   => $baseUrl,
                'api_key'    => $tenant->klikpajak_api_key ?? '',
                'api_secret' => $tenant->klikpajak_secret ?? '',
                'npwp'       => $tenant->npwp ?? '',
            ]);
        });
    }
}
