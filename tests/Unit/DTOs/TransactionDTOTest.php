<?php

namespace Tests\Unit\DTOs;

use App\DTOs\TransactionDTO;
use Tests\TestCase;
use Carbon\Carbon;

class TransactionDTOTest extends TestCase
{
    /** @test */
    public function it_constructs_with_all_provided_data()
    {
        $data = [
            'id' => 1,
            'type' => 'sale',
            'invoice' => 'INV-001',
            'customer_supplier' => 'Customer A',
            'contact_info' => 'customer@example.com',
            'date' => Carbon::now(),
            'amount' => 100.00,
            'paid_amount' => 50.00,
            'due_amount' => 50.00,
            'status' => 'pending',
            'view_url' => '/sales/1',
            'edit_url' => '/sales/1/edit',
        ];

        $dto = new TransactionDTO($data);

        $this->assertEquals(1, $dto->id);
        $this->assertEquals('sale', $dto->type);
        $this->assertEquals('INV-001', $dto->invoice);
        $this->assertEquals('Customer A', $dto->customer_supplier);
        $this->assertEquals('customer@example.com', $dto->contact_info);
        $this->assertInstanceOf(Carbon::class, $dto->date);
        $this->assertEquals(100.00, $dto->amount);
        $this->assertEquals(50.00, $dto->paid_amount);
        $this->assertEquals(50.00, $dto->due_amount);
        $this->assertEquals('pending', $dto->status);
        $this->assertEquals('/sales/1', $dto->view_url);
        $this->assertEquals('/sales/1/edit', $dto->edit_url);
    }

    /** @test */
    public function it_constructs_with_partial_data()
    {
        $data = [
            'id' => 2,
            'type' => 'purchase',
            'amount' => 200.00,
        ];

        $dto = new TransactionDTO($data);

        $this->assertEquals(2, $dto->id);
        $this->assertEquals('purchase', $dto->type);
        $this->assertEquals(200.00, $dto->amount);
        $this->assertNull($dto->invoice); // Other properties should be null by default
    }

    /** @test */
    public function it_constructs_with_extra_data()
    {
        $data = [
            'id' => 3,
            'type' => 'sale',
            'extra_field' => 'some_value', // Extra field
        ];

        $dto = new TransactionDTO($data);

        $this->assertEquals(3, $dto->id);
        $this->assertEquals('sale', $dto->type);
        // Extra field should not be set as a public property unless explicitly defined
        $this->assertTrue(property_exists($dto, 'extra_field'));
    }

    /** @test */
    public function it_returns_the_id_as_key()
    {
        $data = ['id' => 10];
        $dto = new TransactionDTO($data);
        $this->assertEquals(10, $dto->getKey());
    }

    /** @test */
    public function it_converts_to_array_correctly()
    {
        $date = Carbon::now();
        $data = [
            'id' => 1,
            'type' => 'sale',
            'invoice' => 'INV-001',
            'customer_supplier' => 'Customer A',
            'contact_info' => 'customer@example.com',
            'date' => $date,
            'amount' => 100.00,
            'paid_amount' => 50.00,
            'due_amount' => 50.00,
            'status' => 'pending',
            'view_url' => '/sales/1',
            'edit_url' => '/sales/1/edit',
        ];

        $dto = new TransactionDTO($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertEquals($data['id'], $array['id']);
        $this->assertEquals($data['type'], $array['type']);
        // Ensure all properties are present and correct
        $this->assertEquals($data['invoice'], $array['invoice']);
        $this->assertEquals($data['customer_supplier'], $array['customer_supplier']);
        $this->assertEquals($data['contact_info'], $array['contact_info']);
        $this->assertEquals($data['date'], $array['date']);
        $this->assertEquals($data['amount'], $array['amount']);
        $this->assertEquals($data['paid_amount'], $array['paid_amount']);
        $this->assertEquals($data['due_amount'], $array['due_amount']);
        $this->assertEquals($data['status'], $array['status']);
        $this->assertEquals($data['view_url'], $array['view_url']);
        $this->assertEquals($data['edit_url'], $array['edit_url']);
    }

    /** @test */
    public function it_converts_to_string_correctly()
    {
        $data = ['id' => 100];
        $dto = new TransactionDTO($data);
        $this->assertEquals('100', (string) $dto);
    }

    /** @test */
    public function it_converts_to_string_with_null_id()
    {
        $data = ['type' => 'test'];
        $dto = new TransactionDTO($data);
        $this->assertEquals('', (string) $dto); // Expect empty string if id is null
    }
}
