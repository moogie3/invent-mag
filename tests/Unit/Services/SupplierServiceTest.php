<?php

namespace Tests\Unit\Services;

use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SupplierServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SupplierService $supplierService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplierService = new SupplierService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(SupplierService::class, $this->supplierService);
    }

    #[Test]
    public function it_can_get_supplier_index_data()
    {
        Supplier::factory()->count(5)->create();
        $data = $this->supplierService->getSupplierIndexData(10);

        $this->assertArrayHasKey('suppliers', $data);
        $this->assertArrayHasKey('entries', $data);
        $this->assertArrayHasKey('totalsupplier', $data);
        $this->assertArrayHasKey('inCount', $data);
        $this->assertArrayHasKey('outCount', $data);
    }

    #[Test]
    public function it_can_create_a_supplier()
    {
        $data = [
            'name' => 'New Supplier',
            'email' => 'supplier@example.com',
            'phone_number' => '1234567890',
            'address' => '123 Main St',
            'location' => 'IN',
            'code' => 'S-001',
            'payment_terms' => 'Net 30',
        ];

        $result = $this->supplierService->createSupplier($data);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('suppliers', ['name' => 'New Supplier']);
    }

    #[Test]
    public function it_can_update_a_supplier()
    {
        $supplier = Supplier::factory()->create();
        $data = [
            'name' => 'Updated Supplier',
            'email' => 'updated@example.com',
        ];

        $this->supplierService->updateSupplier($supplier, $data);

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Updated Supplier']);
    }

    #[Test]
    public function it_can_delete_a_supplier()
    {
        $supplier = Supplier::factory()->create();
        $this->supplierService->deleteSupplier($supplier);

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    #[Test]
    public function it_can_create_a_supplier_with_image()
    {
        Storage::fake('public');
        $data = [
            'name' => 'New Supplier with Image',
            'email' => 'supplier-image@example.com',
            'phone_number' => '1234567890',
            'address' => '123 Main St',
            'location' => 'IN',
            'code' => 'S-002',
            'payment_terms' => 'Net 30',
            'image' => UploadedFile::fake()->image('supplier.jpg'),
        ];

        $result = $this->supplierService->createSupplier($data);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('suppliers', ['name' => 'New Supplier with Image']);
        $supplier = Supplier::where('name', 'New Supplier with Image')->first();
        Storage::disk('public')->assertExists('image/' . $supplier->getRawOriginal('image'));
    }

    #[Test]
    public function it_can_update_a_supplier_with_image()
    {
        Storage::fake('public');
        $supplier = Supplier::factory()->create();
        $data = [
            'name' => 'Updated Supplier with Image',
            'email' => 'updated-image@example.com',
            'image' => UploadedFile::fake()->image('new_supplier.jpg'),
        ];

        $this->supplierService->updateSupplier($supplier, $data);

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Updated Supplier with Image']);
        $supplier = $supplier->fresh();
        Storage::disk('public')->assertExists('image/' . $supplier->getRawOriginal('image'));
    }

    #[Test]
    public function it_deletes_old_image_on_update()
    {
        Storage::fake('public');
        $oldImage = UploadedFile::fake()->image('old.jpg');
        $oldImageName = $oldImage->name;
        $supplier = Supplier::factory()->create(['image' => $oldImageName]);
        Storage::disk('public')->putFileAs('image', $oldImage, $oldImageName);


        $data = [
            'name' => 'Updated Supplier Name',
            'image' => UploadedFile::fake()->image('new.jpg'),
        ];

        $this->supplierService->updateSupplier($supplier, $data);

        Storage::disk('public')->assertMissing('image/' . $oldImageName);
    }

    #[Test]
    public function it_can_delete_a_supplier_with_image()
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('supplier.jpg');
        $imageName = $image->name;
        $supplier = Supplier::factory()->create(['image' => $imageName]);
        Storage::disk('public')->putFileAs('image', $image, $imageName);

        $this->supplierService->deleteSupplier($supplier);

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
        Storage::disk('public')->assertMissing('image/' . $imageName);
    }

    #[Test]
    public function it_can_get_supplier_metrics()
    {
        Supplier::factory()->count(3)->create(['location' => 'IN']);
        Supplier::factory()->count(2)->create(['location' => 'OUT']);

        $metrics = $this->supplierService->getSupplierMetrics();

        $this->assertEquals(5, $metrics['totalsupplier']);
        $this->assertEquals(3, $metrics['inCount']);
        $this->assertEquals(2, $metrics['outCount']);
    }
}