<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Customer;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
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

    public function test_it_can_display_the_customer_index_page()
    {
        Customer::factory()->count(5)->create();

        $response = $this->get(route('admin.customer'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.customer.index');
        $response->assertViewHas('customers');
    }

    public function test_it_can_store_a_new_customer()
    {
        $customerData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'address' => '123 Main St',
            'phone_number' => '555-1234',
            'payment_terms' => 'Net 30',
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ];

        $response = $this->post(route('admin.customer.store'), $customerData);

        $response->assertRedirect(route('admin.customer'));
        $response->assertSessionHas('success', 'Customer created');

        $this->assertDatabaseHas('customers', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $customer = Customer::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($customer->getRawOriginal('image'));
    }

    public function test_it_can_update_a_customer()
    {
        $customer = Customer::factory()->create();

        $updateData = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'address' => '456 Oak Ave',
            'phone_number' => '555-5678',
            'payment_terms' => 'Net 60',
        ];

        $response = $this->put(route('admin.customer.update', $customer->id), $updateData);

        $response->assertRedirect(route('admin.customer'));
        $response->assertSessionHas('success', 'Customer updated');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
        ]);
    }

    public function test_it_can_delete_a_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->delete(route('admin.customer.destroy', $customer->id));

        $response->assertRedirect(route('admin.customer'));
        $response->assertSessionHas('success', 'Customer deleted');

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}
