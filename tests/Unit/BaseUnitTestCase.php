<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseUnitTestCase extends TestCase
{
    use RefreshDatabase;

    // Unit tests should generally not rely on seeding the entire database.
    // Each test should create only the data it needs.
    // If a minimal seed is absolutely necessary for the application to function,
    // it should be explicitly called in individual test setup or a more specific base class.
    protected function setUp(): void
    {
        parent::setUp();
        // No global seeding for unit tests
    }
}
