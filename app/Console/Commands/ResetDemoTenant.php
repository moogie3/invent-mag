<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ResetDemoTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:reset-demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipes and re-seeds all demo tenants to give users a fresh 24-hour playground.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $domain = config('app.domain', 'localhost');
        $demoDomains = [
            'demo-starter.' . $domain,
            'demo-pro.' . $domain,
            'demo-enterprise.' . $domain,
            'demo.' . $domain // legacy demo
        ];

        $demoTenants = Tenant::whereIn('domain', $demoDomains)->get();

        if ($demoTenants->isEmpty()) {
            $this->info("No existing demo tenants found. Creating new ones...");
        } else {
            \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
            
            $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
            
            foreach ($demoTenants as $tenant) {
                $this->info("Found existing demo tenant: {$tenant->name} (ID: {$tenant->id})");
                $this->info("Deleting tenant data to wipe all associated data...");
                
                // Delete all records with this tenant_id from all tables
                foreach ($tables as $tableRow) {
                    $tableName = array_values((array)$tableRow)[0];
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'tenant_id')) {
                        \Illuminate\Support\Facades\DB::table($tableName)->where('tenant_id', $tenant->id)->delete();
                    }
                }
                
                $tenant->delete();
            }
            
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        }

        try {
            $this->info("Running DatabaseSeeder to recreate demo workspaces...");
            
            // Run global seeders and recreate tenants
            Artisan::call('db:seed', [
                '--class' => 'DatabaseSeeder'
            ]);

            $this->info(Artisan::output());
            
            $this->info("Demo tenant reset successfully.");
            Log::info("Demo tenant reset successfully via scheduled command.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to reset demo tenant: " . $e->getMessage());
            Log::error("Failed to reset demo tenant: " . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
