<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'billing:check-expired';
    protected $description = 'Transition active subscriptions to expired when billing period has passed';

    public function handle(): int
    {
        $this->info('Checking for expired subscriptions...');

        $tenants = Tenant::where('plan_status', 'active')
            ->whereNotNull('plan_id')
            ->whereNull('trial_ends_at')
            ->get();

        $expiredCount = 0;

        foreach ($tenants as $tenant) {
            if ($tenant->subscriptionExpired()) {
                $tenant->plan_status = 'expired';
                $tenant->save();

                $expiredCount++;
                $this->info("Subscription expired for tenant: {$tenant->name} (ID: {$tenant->id})");
                Log::info("Subscription auto-expired for tenant {$tenant->name} (ID: {$tenant->id}). Plan: {$tenant->plan?->name}");
            }
        }

        if ($expiredCount === 0) {
            $this->info('No expired subscriptions found.');
        } else {
            $this->info("Expired {$expiredCount} subscription(s).");
        }

        return Command::SUCCESS;
    }
}
