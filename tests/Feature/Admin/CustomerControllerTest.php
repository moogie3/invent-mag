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

    public function test_get_metrics_returns_json()
    {
        $expectedMetrics = [
            'totalCustomers' => 10,
            'activeCustomers' => 8,
            'newCustomersThisMonth' => 2,
        ];

        // Mock the CustomerService
        $mockService = \Mockery::mock(\App\Services\CustomerService::class);
        $mockService->shouldReceive('getCustomerMetrics')
                    ->once()
                    ->andReturn($expectedMetrics);
        $this->app->instance(\App\Services\CustomerService::class, $mockService);

        $response = $this->get(route('admin.customer.metrics'));

        $response->assertStatus(200);
        $response->assertJson($expectedMetrics);
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

    public function test_store_customer_handles_service_level_error()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $customerData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'address' => '123 Main St',
            'phone_number' => '555-1234',
            'payment_terms' => 'Net 30',
        ];
        $errorMessage = 'Service creation failed.';

        // Mock the CustomerService to return a failure
        $mockService = \Mockery::mock(\App\Services\CustomerService::class);
        $mockService->shouldReceive('createCustomer')
                    ->once()
                    ->andReturn(['success' => false, 'message' => $errorMessage]);
        $mockService->shouldReceive('getCustomerIndexData') // Add expectation for index method call
                    ->andReturn([
                        'customers' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                        'entries' => 10,
                        'totalcustomer' => 0,
                    ]);
        $this->app->instance(\App\Services\CustomerService::class, $mockService);

        $this->get(route('admin.customer')); // Set previous URL for back() redirect
        $response = $this->post(route('admin.customer.store'), $customerData);

        $response->assertSessionHasErrors(['name' => $errorMessage]);
        $response->assertRedirect(route('admin.customer'));
    }

    public function test_store_customer_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'name' => '', // Required
            'email' => 'not-an-email', // Invalid email
            'phone_number' => '', // Required
            'address' => '', // Required
            'image' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'), // Invalid file type
        ];

        $response = $this->post(route('admin.customer.store'), $invalidData);

        $response->assertSessionHasErrors(['name', 'email', 'phone_number', 'address', 'image']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_quick_create_customer_successfully()
    {
        Storage::fake('public');

        $customerData = [
            'name' => 'Quick Customer',
            'email' => 'quick@example.com',
            'address' => '789 Quick St',
            'phone_number' => '111-2222',
            'payment_terms' => 'Net 15',
            'image' => UploadedFile::fake()->image('quick_avatar.jpg'),
        ];

        $mockCustomer = Customer::factory()->make($customerData);

        // Mock the CustomerService
        $mockService = \Mockery::mock(\App\Services\CustomerService::class);
        $mockService->shouldReceive('quickCreateCustomer')
                    ->once()
                    ->andReturn(['success' => true, 'customer' => $mockCustomer]);
        $this->app->instance(\App\Services\CustomerService::class, $mockService);

        $response = $this->post(route('admin.customer.quickCreate'), $customerData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Customer created successfully',
            'customer' => $mockCustomer->toArray(),
        ]);
    }

    public function test_quick_create_customer_with_invalid_data_returns_validation_errors()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $invalidData = [
            'name' => '', // Required
            'email' => 'not-an-email', // Invalid email
            'phone_number' => '', // Required
            'address' => '', // Required
            'payment_terms' => '', // Required
            'image' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'), // Invalid file type
        ];

        $response = $this->post(route('admin.customer.quickCreate'), $invalidData, ['Accept' => 'application/json']);

        $response->assertStatus(422); // Unprocessable Entity for AJAX validation errors
        $response->assertJsonValidationErrors(['name', 'email', 'phone_number', 'address', 'payment_terms', 'image']);
    }

    public function test_quick_create_customer_handles_service_level_error()
    {
        $customerData = [
            'name' => 'Quick Customer',
            'email' => 'quick@example.com',
            'address' => '789 Quick St',
            'phone_number' => '111-2222',
            'payment_terms' => 'Net 15',
        ];
        $errorMessage = 'Service quick create failed.';

        // Mock the CustomerService to return a failure
        $mockService = \Mockery::mock(\App\Services\CustomerService::class);
        $mockService->shouldReceive('quickCreateCustomer')
                    ->once()
                    ->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\CustomerService::class, $mockService);

        $response = $this->post(route('admin.customer.quickCreate'), $customerData, ['Accept' => 'application/json']);

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJson([
            'success' => false,
            'message' => $errorMessage,
        ]);
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

    public function test_update_customer_with_invalid_data_returns_validation_errors()
    {
        $customer = Customer::factory()->create();
        $invalidData = [
            'name' => '', // Required
            'email' => 'not-an-email', // Invalid email
            'phone_number' => '', // Required
            'address' => '', // Required
        ];

        $response = $this->put(route('admin.customer.update', $customer->id), $invalidData);

        $response->assertSessionHasErrors(['name', 'email', 'phone_number', 'address']);
        $response->assertStatus(302);
    }

    public function test_update_customer_handles_service_level_error()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $customer = Customer::factory()->create();
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'address' => '456 Updated Ave',
            'phone_number' => '555-5678',
            'payment_terms' => 'Net 60',
        ];
        $errorMessage = 'Service update failed.';

        // Mock the CustomerService to return a failure
        $mockService = \Mockery::mock(\App\Services\CustomerService::class);
        $mockService->shouldReceive('updateCustomer')
                    ->once()
                    ->andReturn(['success' => false, 'message' => $errorMessage]);
        $mockService->shouldReceive('getCustomerIndexData') // Add expectation for index method call
                    ->andReturn([
                        'customers' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                        'entries' => 10,
                        'totalcustomer' => 0,
                    ]);
        $this->app->instance(\App\Services\CustomerService::class, $mockService);

        $this->get(route('admin.customer')); // Set previous URL for back() redirect
        $response = $this->put(route('admin.customer.update', $customer->id), $updateData);

        $response->assertSessionHas('error', $errorMessage);
        $response->assertRedirect(route('admin.customer'));
    }

    public function test_update_handles_non_existent_customer()
    {
        $nonExistentId = 9999;
        $updateData = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'address' => '456 Oak Ave',
            'phone_number' => '555-5678',
            'payment_terms' => 'Net 30', // Added to pass validation
        ];

        $response = $this->put(route('admin.customer.update', $nonExistentId), $updateData);

        $response->assertNotFound();
    }

    public function test_it_can_delete_a_customer()
    {
        $customer = Customer::factory()->create();

        $response = $this->delete(route('admin.customer.destroy', $customer->id));

        $response->assertRedirect(route('admin.customer'));
        $response->assertSessionHas('success', 'Customer deleted');

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_delete_customer_handles_service_level_error()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $customer = Customer::factory()->create();
        $errorMessage = 'Service deletion failed.';

        // Mock the CustomerService to return a failure
        $mockService = \Mockery::mock(\App\Services\CustomerService::class);
        $mockService->shouldReceive('deleteCustomer')
                    ->once()
                    ->andReturn(['success' => false, 'message' => $errorMessage]);
        $this->app->instance(\App\Services\CustomerService::class, $mockService);

        $response = $this->delete(route('admin.customer.destroy', $customer->id));

        $response->assertSessionHas('error', $errorMessage);
        $response->assertRedirect(route('admin.customer'));
        $this->assertDatabaseHas('customers', ['id' => $customer->id]); // Should not be deleted
    }

    public function test_delete_handles_non_existent_customer()
    {
        $nonExistentId = 9999;

        $response = $this->delete(route('admin.customer.destroy', $nonExistentId));

        $response->assertNotFound();
    }
}
