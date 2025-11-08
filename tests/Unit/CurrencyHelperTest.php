<?php

namespace Tests\Unit;

use stdClass;
use Tests\TestCase;
use App\Helpers\CurrencyHelper;

class CurrencyHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear the cached settings in CurrencyHelper before each test
        CurrencyHelper::clearSettingsCache();
    }

    /**
     * A basic unit test example.
     */
    public function test_format_method_with_default_settings(): void
    {
        // Arrange: Set up predictable settings for the test.
        $settings = new stdClass();
        $settings->currency_symbol = 'Rp';
        $settings->decimal_places = 0;
        $settings->decimal_separator = ',';
        $settings->thousand_separator = '.';
        $settings->position = 'prefix';
        CurrencyHelper::setSettingsForTesting($settings);

        $amount = 1234567;
        $expectedFormattedAmount = 'Rp 1.234.567'; // Based on settings above

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
        // Arrange: Set up predictable custom settings for the test.
        $settings = new stdClass();
        $settings->currency_symbol = '$';
        $settings->decimal_places = 2;
        $settings->decimal_separator = '.';
        $settings->thousand_separator = ',';
        $settings->position = 'prefix';
        $settings->locale = 'en-US';
        CurrencyHelper::setSettingsForTesting($settings);

        $amount = 1234567.89;
        $expectedFormattedAmount = '$ 1,234,567.89'; // Based on settings above

        // Act
        $actualFormattedAmount = CurrencyHelper::format($amount);

        // Assert
        $this->assertEquals($expectedFormattedAmount, $actualFormattedAmount);
    }
}