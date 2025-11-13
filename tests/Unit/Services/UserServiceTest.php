<?php

namespace Tests\Unit\Services;

use App\Services\UserService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(UserService::class, $this->userService);
    }
}
