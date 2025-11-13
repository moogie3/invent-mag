<?php

namespace Tests\Unit\Services;

use App\Services\TransactionService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionService = new TransactionService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(TransactionService::class, $this->transactionService);
    }
}
