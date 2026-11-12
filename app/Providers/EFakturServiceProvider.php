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

            // Mekari HMAC credentials (developers.mekari.com): the tenant's
            // klikpajak_api_key column holds the Client ID (HMAC username),
            // klikpajak_secret the Client Secret (signing key).
            $useSandbox = $tenant->klikpajak_use_sandbox ?? true;
            $baseUrl = $useSandbox
                ? config('services.klikpajak.sandbox_url', 'https://sandbox-api.mekari.com/v2/klikpajak')
                : config('services.klikpajak.production_url', 'https://api.mekari.com/v2/klikpajak');

            return new KlikpajakService([
                'base_url' => $baseUrl,
                'client_id' => $tenant->klikpajak_api_key ?? '',
                'client_secret' => $tenant->klikpajak_secret ?? '',
            ]);
        });
    }
}
