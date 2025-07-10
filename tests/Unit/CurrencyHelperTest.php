<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\CurrencyHelper;
use App\Models\CurrencySetting;
use Mockery;

class CurrencyHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear the cached settings in CurrencyHelper before each test
        CurrencyHelper::clearSettingsCache();
    }

    protected function tearDown(): void
    {
        Mockery::close(); // Close Mockery after each test
        parent::tearDown();
    }

    /**
     * A basic unit test example.
     */
    public function test_format_method_with_default_settings(): void
    {
        // Arrange: Set up any necessary data or conditions.
        // For this test, we'll mock the CurrencySetting model
        // to ensure we're using predictable default settings.
        // This prevents the test from relying on actual database data.
        $mock = Mockery::mock('alias:App\\Models\\CurrencySetting'); // Use alias for static methods
        $mock->shouldReceive('first')->andReturn((object) [
            'currency_symbol' => 'Rp',
            'decimal_places' => 0,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'position' => 'prefix'
        ]);

        $amount = 1234567;
        $expectedFormattedAmount = 'Rp 1.234.567'; // Based on default settings

        // Act: Call the method you want to test.
        $actualFormattedAmount = CurrencyHelper::format($amount);

        // Assert: Check if the actual output matches the expected output.
        $this->assertEquals($expectedFormattedAmount, $actualFormattedAmount);
    }

    /**
     * Test format method with custom currency settings.
     */
    public function test_format_method_with_custom_settings(): void
    {
        // Arrange: Mock CurrencySetting to return custom settings
        $mock = Mockery::mock('alias:App\\Models\\CurrencySetting'); // Use alias for static methods
        $mock->shouldReceive('first')->andReturn((object) [
            'currency_symbol' => '$',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'position' => 'prefix'
        ]);

        $amount = 1234567.89;
        $expectedFormattedAmount = '$ 1,234,567.89'; // Based on custom settings

        // Act
        $actualFormattedAmount = CurrencyHelper::format($amount);

        // Assert
        $this->assertEquals($expectedFormattedAmount, $actualFormattedAmount);
    }
}
