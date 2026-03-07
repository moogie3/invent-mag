<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\SupplierSeeder;
use Database\Seeders\WarehouseSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PurchaseSeeder;
use Database\Seeders\SalesSeeder;
use Database\Seeders\PurchaseReturnSeeder;
use Database\Seeders\SalesReturnSeeder;
use Database\Seeders\CustomerInteractionSeeder;
use Database\Seeders\SupplierInteractionSeeder;
use Database\Seeders\TaxSeeder;
use Database\Seeders\StockAdjustmentSeeder;
use Database\Seeders\StockTransferSeeder;
use Database\Seeders\SalesPipelineSeeder;
use Database\Seeders\PipelineStageSeeder;
use Database\Seeders\SalesOpportunitySeeder;
use Database\Seeders\ManualJournalEntrySeeder;
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
    protected $description = 'Resets demo tenant data while preserving demo user accounts.';

    private array $demoUserEmails = [
        'starter@demo.com',
        'pro@demo.com',
        'enterprise@demo.com',
    ];

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
            
            // Run DatabaseSeeder to create demo tenants
            Artisan::call('db:seed', [
                '--class' => 'DatabaseSeeder'
            ]);
            $this->info(Artisan::output());
            
            $this->info("Demo tenants created successfully.");
            return Command::SUCCESS;
        }

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        
        $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
        
        foreach ($demoTenants as $tenant) {
            $this->info("Resetting demo tenant: {$tenant->name} (ID: {$tenant->id})");
            
            // Delete all records with this tenant_id from all tables EXCEPT users table
            foreach ($tables as $tableRow) {
                $tableName = array_values((array)$tableRow)[0];
                
                // Skip users table - handled separately below
                if ($tableName === 'users') {
                    continue;
                }
                
                if (\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'tenant_id')) {
                    $deleted = \Illuminate\Support\Facades\DB::table($tableName)->where('tenant_id', $tenant->id)->delete();
                    if ($deleted > 0) {
                        $this->info("  - Deleted {$deleted} records from {$tableName}");
                    }
                }
            }
            
            // Delete all users from demo tenant EXCEPT the demo users
            $deletedUsers = User::where('tenant_id', $tenant->id)
                ->whereNotIn('email', $this->demoUserEmails)
                ->delete();
            
            if ($deletedUsers > 0) {
                $this->info("  - Deleted {$deletedUsers} non-demo users");
            } else {
                $this->info("  - No non-demo users to delete");
            }
        }
        
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        try {
            $this->info("Re-seeding demo tenant data (excluding users)...");
            
            // Re-seed demo tenants with all data EXCEPT UserSeeder
            foreach ($demoTenants as $tenant) {
                $tenant->makeCurrent();
                
                $this->call([
                    AccountSeeder::class,
                    CategorySeeder::class,
                    UnitSeeder::class,
                    SupplierSeeder::class,
                    WarehouseSeeder::class,
                    ProductSeeder::class,
                    CustomerSeeder::class,
                    CurrencySeeder::class,
                    PurchaseSeeder::class,
                    SalesSeeder::class,
                    PurchaseReturnSeeder::class,
                    SalesReturnSeeder::class,
                    CustomerInteractionSeeder::class,
                    SupplierInteractionSeeder::class,
                    TaxSeeder::class,
                    StockAdjustmentSeeder::class,
                    StockTransferSeeder::class,
                    SalesPipelineSeeder::class,
                    PipelineStageSeeder::class,
                    SalesOpportunitySeeder::class,
                    ManualJournalEntrySeeder::class,
                ]);
                
                Tenant::forgetCurrent();
            }
            
            $this->info("Demo tenant reset successfully (users preserved).");
            Log::info("Demo tenant reset successfully via scheduled command.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to reset demo tenant: " . $e->getMessage());
            Log::error("Failed to reset demo tenant: " . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
