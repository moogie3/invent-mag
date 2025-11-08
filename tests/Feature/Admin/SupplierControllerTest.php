<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Supplier;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);

        $this->seed(CurrencySeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');

        $this->actingAs($this->user);

        Warehouse::factory()->create(['is_main' => true]);
    }

    public function test_it_can_display_the_supplier_index_page()
    {
        Supplier::factory()->count(5)->create();

        $response = $this->get(route('admin.supplier'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.supplier.index');
        $response->assertViewHas('suppliers');
    }

    public function test_it_can_store_a_new_supplier()
    {
        $supplierData = [
            'code' => 'SUP001',
            'name' => 'Acme Corp',
            'address' => '123 Industrial Rd',
            'phone_number' => '555-1111',
            'location' => 'IN',
            'payment_terms' => 'Net 30',
            'email' => 'contact@acmecorp.com',
            'image' => UploadedFile::fake()->image('logo.jpg'),
        ];

        $response = $this->post(route('admin.supplier.store'), $supplierData);

        $response->assertRedirect(route('admin.supplier'));
        $response->assertSessionHas('success', 'Supplier created');

        $this->assertDatabaseHas('suppliers', [
            'code' => 'SUP001',
            'name' => 'Acme Corp',
        ]);

        $supplier = Supplier::where('code', 'SUP001')->first();
        $this->assertNotNull($supplier->getRawOriginal('image'));
    }

    public function test_it_can_update_a_supplier()
    {
        $supplier = Supplier::factory()->create();

        $updateData = [
            'code' => $supplier->code, // Code is required, keep original
            'name' => 'Updated Corp',
            'address' => '456 New St',
            'phone_number' => '555-2222',
            'location' => 'OUT',
            'payment_terms' => 'Net 60',
            'email' => 'updated@acmecorp.com',
        ];

        $response = $this->put(route('admin.supplier.update', $supplier->id), $updateData);

        $response->assertRedirect(route('admin.supplier'));
        $response->assertSessionHas('success', 'Supplier updated');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Corp',
            'email' => 'updated@acmecorp.com',
        ]);
    }

    public function test_it_can_delete_a_supplier()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->delete(route('admin.supplier.destroy', $supplier->id));

        $response->assertRedirect(route('admin.supplier'));
        $response->assertSessionHas('success', 'Supplier deleted');

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
