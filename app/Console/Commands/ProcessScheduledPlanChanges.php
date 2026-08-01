<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledPlanChanges extends Command
{
    protected $signature = 'billing:process-scheduled-changes';

    protected $description = 'Activate scheduled plan downgrades at the start of each billing period';

    public function handle(): int
    {
        $this->info('Processing scheduled plan changes...');

        $tenants = Tenant::whereNotNull('pending_plan_effective_at')
            ->where('pending_plan_effective_at', '<=', now())
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No scheduled plan changes to process.');

            return Command::SUCCESS;
        }

        $this->info("Found {$tenants->count()} scheduled plan change(s) to process.");

        foreach ($tenants as $tenant) {
            $this->processTenant($tenant);
        }

        $this->info('Scheduled plan changes processed successfully.');

        return Command::SUCCESS;
    }

    protected function processTenant(Tenant $tenant): void
    {
        $oldPlan = $tenant->getCurrentPlan();
        $newPlan = $tenant->pendingPlan;

        if (! $newPlan) {
            Log::warning("Scheduled plan change for tenant {$tenant->name} has no pending plan. Clearing.");
            $tenant->pending_plan_id = null;
            $tenant->pending_plan_effective_at = null;
            $tenant->save();

            return;
        }

        $tenant->applyScheduledDowngrade();

        $this->info("Applied downgrade for {$tenant->name}: {$oldPlan?->name} -> {$newPlan->name}");

        Log::info("Scheduled downgrade applied via cron for tenant {$tenant->name}: {$oldPlan?->name} -> {$newPlan->name}");
    }
}
