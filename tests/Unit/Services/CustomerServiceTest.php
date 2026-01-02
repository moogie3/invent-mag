<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Services\CustomerService;
use Tests\Unit\BaseUnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;

class CustomerServiceTest extends BaseUnitTestCase
{
    use RefreshDatabase, CreatesTenant;

    protected CustomerService $customerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->customerService = new CustomerService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(CustomerService::class, $this->customerService);
    }

    #[Test]
    public function it_can_get_customer_index_data()
    {
        Customer::factory()->count(5)->create();

        $entries = 3;
        $result = $this->customerService->getCustomerIndexData($entries);

        $this->assertArrayHasKey('customers', $result);
        $this->assertArrayHasKey('entries', $result);
        $this->assertArrayHasKey('totalcustomer', $result);

        $this->assertCount($entries, $result['customers']);
        $this->assertEquals(5, $result['totalcustomer']);
        $this->assertEquals($entries, $result['entries']);
    }

    #[Test]
    public function it_can_create_a_customer_successfully_without_image()
    {
        $data = [
            'name' => 'John Doe',
            'address' => '123 Main St',
            'phone_number' => '123-456-7890',
            'payment_terms' => 'Net 30',
            'email' => 'john.doe@example.com',
        ];
        $result = $this->customerService->createCustomer($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Customer created successfully.', $result['message']);
        $this->assertDatabaseHas('customers', ['name' => 'John Doe', 'email' => 'john.doe@example.com']);
    }

    #[Test]
    public function it_can_create_a_customer_successfully_with_image()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('avatar.jpg');

        $data = [
            'name' => 'Jane Doe',
            'address' => '456 Oak Ave',
            'phone_number' => '098-765-4321',
            'payment_terms' => 'Net 60',
            'email' => 'jane.doe@example.com',
            'image' => $file,
        ];
        $result = $this->customerService->createCustomer($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Customer created successfully.', $result['message']);
        $this->assertDatabaseHas('customers', ['name' => 'Jane Doe', 'email' => 'jane.doe@example.com']);
        Storage::disk('public')->assertExists('image/' . $result['customer']->getRawOriginal('image'));
    }

    #[Test]
    public function it_returns_error_if_customer_already_exists()
    {
        Customer::factory()->create(['name' => 'John Doe', 'email' => 'john.doe@example.com']);

        $data = [
            'name' => 'John Doe',
            'address' => '123 Main St',
            'phone_number' => '123-456-7890',
            'payment_terms' => 'Net 30',
            'email' => 'john.doe.new@example.com', // Different email, but same name
        ];
        $result = $this->customerService->createCustomer($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('This customer already exists.', $result['message']);
        $this->assertDatabaseCount('customers', 1);
    }

    #[Test]
    public function it_can_quick_create_a_customer_successfully()
    {
        $data = [
            'name' => 'Quick Customer',
            'address' => '789 Pine Ln',
            'phone_number' => '111-222-3333',
            'payment_terms' => 'Net 15',
            'email' => 'quick.customer@example.com',
        ];
        $result = $this->customerService->quickCreateCustomer($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Customer created successfully.', $result['message']);
        $this->assertNotNull($result['customer']);
        $this->assertDatabaseHas('customers', ['name' => 'Quick Customer']);
    }

    #[Test]
    public function it_can_update_a_customer_successfully_without_image_change()
    {
        Storage::fake('public');
        $oldFile = UploadedFile::fake()->image('old_avatar.jpg');
        $oldFileName = 'old_image.jpg';
        Storage::disk('public')->putFileAs('image', $oldFile, $oldFileName);

        $customer = Customer::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'image' => $oldFileName,
        ]);

        $updatedData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];
        $result = $this->customerService->updateCustomer($customer, $updatedData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Customer updated successfully.', $result['message']);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'New Name', 'email' => 'new@example.com']);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id, 'name' => 'Old Name']);
        Storage::disk('public')->assertExists('image/' . $oldFileName); // Old image should still exist
    }

    #[Test]
    public function it_can_update_a_customer_successfully_with_image_change()
    {
        Storage::fake('public');
        $oldFile = UploadedFile::fake()->image('old_avatar.jpg');
        $oldFileName = 'old_image.jpg';
        Storage::disk('public')->putFileAs('image', $oldFile, $oldFileName);

        $customer = Customer::factory()->create([
            'name' => 'Image Customer',
            'email' => 'image@example.com',
            'image' => $oldFileName,
        ]);

        $newFile = UploadedFile::fake()->image('new_avatar.png');
        $updatedData = [
            'name' => 'Updated Image Customer',
            'image' => $newFile,
        ];
        $result = $this->customerService->updateCustomer($customer, $updatedData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Customer updated successfully.', $result['message']);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated Image Customer']);
        Storage::disk('public')->assertMissing('image/' . $oldFileName); // Old image should be deleted
        Storage::disk('public')->assertExists('image/' . $result['customer']->getRawOriginal('image')); // New image should exist
    }

    #[Test]
    public function it_can_delete_a_customer_successfully_without_image()
    {
        $customer = Customer::factory()->create(['image' => null]);
        $result = $this->customerService->deleteCustomer($customer);

        $this->assertTrue($result['success']);
        $this->assertEquals('Customer deleted successfully.', $result['message']);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    #[Test]
    public function it_can_delete_a_customer_successfully_with_image()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('to_delete.jpg');
        $fileName = 'to_delete.jpg';
        Storage::disk('public')->putFileAs('image', $file, $fileName);

        $customer = Customer::factory()->create(['image' => $fileName]);
        $result = $this->customerService->deleteCustomer($customer);

        $this->assertTrue($result['success']);
        $this->assertEquals('Customer deleted successfully.', $result['message']);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        Storage::disk('public')->assertMissing('image/' . $fileName); // Image should be deleted
    }

    #[Test]
    public function it_can_get_customer_metrics()
    {
        Customer::factory()->count(7)->create();
        $metrics = $this->customerService->getCustomerMetrics();

        $this->assertArrayHasKey('totalcustomer', $metrics);
        $this->assertEquals(7, $metrics['totalcustomer']);
    }
}
