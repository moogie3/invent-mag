<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();
        
        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Skipping activity log seeding.');
            return;
        }

        $this->command->info('Creating sample activity logs for ' . $tenants->count() . ' tenants...');
        
        foreach ($tenants as $tenant) {
            $this->seedLogsForTenant($tenant);
        }
        
        $this->command->info('Activity log seeding complete.');
    }
    
    protected function seedLogsForTenant(Tenant $tenant): void
    {
        $tenantId = $tenant->id;
        
        // Get some users from the database for the logs
        $users = User::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->limit(3)
            ->get();
        
        if ($users->isEmpty()) {
            $this->command->warn("No users found for tenant {$tenant->name}. Skipping.");
            return;
        }

        $moduleActions = [
            'Product' => ['create', 'update', 'delete', 'view'],
            'Sales' => ['create', 'update', 'delete', 'payment', 'view'],
            'Sales Return' => ['create', 'update', 'delete', 'view'],
            'Purchase Order' => ['create', 'update', 'delete', 'approve', 'receive', 'view'],
            'Purchase Return' => ['create', 'update', 'delete', 'view'],
            'Customer' => ['create', 'update', 'delete', 'view'],
            'Supplier' => ['create', 'update', 'delete', 'view'],
            'Warehouse' => ['create', 'update', 'delete', 'view'],
            'Category' => ['create', 'update', 'delete', 'view'],
            'Unit' => ['create', 'update', 'delete', 'view'],
            'Tax' => ['create', 'update', 'delete', 'view'],
            'User' => ['create', 'update', 'delete', 'view'],
            'Manual Journal Entry' => ['create', 'update', 'delete', 'post', 'void', 'reverse', 'view'],
            'POS' => ['create', 'update', 'payment', 'view'],
        ];
        
        $descriptions = [
            'Product' => [
                'create' => 'Created product: Sample Product A',
                'update' => 'Updated product: Sample Product A',
                'delete' => 'Deleted product: Sample Product B',
                'view' => 'Viewed product details: Sample Product A',
            ],
            'Sales' => [
                'create' => 'Created sales order: INV-2026-001',
                'update' => 'Updated sales order: INV-2026-001',
                'delete' => 'Deleted sales order: INV-2026-002',
                'payment' => 'Added payment to sales: INV-2026-001',
                'view' => 'Viewed sales order: INV-2026-001',
            ],
            'Sales Return' => [
                'create' => 'Created sales return: SR-2026-001',
                'update' => 'Updated sales return: SR-2026-001',
                'delete' => 'Deleted sales return: SR-2026-002',
                'view' => 'Viewed sales return: SR-2026-001',
            ],
            'Purchase Order' => [
                'create' => 'Created purchase order: PO-2026-001',
                'update' => 'Updated purchase order: PO-2026-001',
                'delete' => 'Deleted purchase order: PO-2026-002',
                'approve' => 'Approved purchase order: PO-2026-001',
                'receive' => 'Received purchase order: PO-2026-001',
                'view' => 'Viewed purchase order: PO-2026-001',
            ],
            'Purchase Return' => [
                'create' => 'Created purchase return: PR-2026-001',
                'update' => 'Updated purchase return: PR-2026-001',
                'delete' => 'Deleted purchase return: PR-2026-002',
                'view' => 'Viewed purchase return: PR-2026-001',
            ],
            'Customer' => [
                'create' => 'Created customer: PT Maju Jaya',
                'update' => 'Updated customer: PT Maju Jaya',
                'delete' => 'Deleted customer: CV Berkah',
                'view' => 'Viewed customer: PT Maju Jaya',
            ],
            'Supplier' => [
                'create' => 'Created supplier: Toko Elektronik ABC',
                'update' => 'Updated supplier: Toko Elektronik ABC',
                'delete' => 'Deleted supplier: Distributor XYZ',
                'view' => 'Viewed supplier: Toko Elektronik ABC',
            ],
            'Warehouse' => [
                'create' => 'Created warehouse: Main Warehouse',
                'update' => 'Updated warehouse: Main Warehouse',
                'delete' => 'Deleted warehouse: Secondary Warehouse',
                'view' => 'Viewed warehouse: Main Warehouse',
            ],
            'Category' => [
                'create' => 'Created category: Electronics',
                'update' => 'Updated category: Electronics',
                'delete' => 'Deleted category: Accessories',
                'view' => 'Viewed category: Electronics',
            ],
            'Unit' => [
                'create' => 'Created unit: Pcs',
                'update' => 'Updated unit: Pcs',
                'delete' => 'Deleted unit: Box',
                'view' => 'Viewed unit: Pcs',
            ],
            'Tax' => [
                'create' => 'Created tax: VAT 10%',
                'update' => 'Updated tax: VAT 10%',
                'delete' => 'Deleted tax: VAT 5%',
                'view' => 'Viewed tax: VAT 10%',
            ],
            'User' => [
                'create' => 'Created user: John Doe',
                'update' => 'Updated user: John Doe',
                'delete' => 'Deleted user: Jane Smith',
                'view' => 'Viewed user: John Doe',
            ],
            'Manual Journal Entry' => [
                'create' => 'Created journal entry: JE-2026-001',
                'update' => 'Updated journal entry: JE-2026-001',
                'delete' => 'Deleted journal entry: JE-2026-002',
                'post' => 'Posted journal entry: JE-2026-001',
                'void' => 'Voided journal entry: JE-2026-002',
                'reverse' => 'Reversed journal entry: JE-2026-003',
                'view' => 'Viewed journal entry: JE-2026-001',
            ],
            'POS' => [
                'create' => 'Created POS sale: POS-2026-001',
                'update' => 'Updated POS sale: POS-2026-001',
                'payment' => 'Processed payment for POS: POS-2026-001',
                'view' => 'Viewed POS sale: POS-2026-001',
            ],
        ];

        $this->command->info("Seeding activity logs for tenant: {$tenant->name}");

        // Create 50 sample activity logs over the past 30 days
        for ($i = 0; $i < 50; $i++) {
            $user = $users->random();
            $moduleKeys = array_keys($moduleActions);
            $module = collect($moduleKeys)->random();
            $actionsForModule = $moduleActions[$module];
            $action = collect($actionsForModule)->random();
            $daysAgo = rand(0, 30);
            $hoursAgo = rand(0, 23);

            $description = $descriptions[$module][$action] ?? "{$action} {$module}";

            // Normalize module name to a valid class name (remove spaces)
            $entityType = "App\\Models\\" . str_replace(' ', '', $module);

            ActivityLog::create([
                'tenant_id'    => $tenantId,
                'user_id'      => $user->id,
                'user_name'    => $user->name,
                'user_role'    => $user->getRoleNames()->first() ?? 'staff',
                'module'       => $module,
                'action'       => $action,
                'entity_type'  => $entityType,
                'entity_id'    => rand(1, 100),
                'entity_label' => "Sample {$module} " . rand(1, 100),
                'description'  => $description,
                'old_values'   => $action === 'update' ? json_encode(['name' => 'Old Value', 'price' => 1000]) : null,
                'new_values'   => in_array($action, ['create', 'update']) ? json_encode(['name' => 'New Value', 'price' => 1500]) : null,
                'ip_address'   => '192.168.1.' . rand(1, 255),
                'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at'   => now()->subDays($daysAgo)->subHours($hoursAgo),
            ]);
        }

        $this->command->info("Created 50 sample activity logs for {$tenant->name}.");
    }
}
