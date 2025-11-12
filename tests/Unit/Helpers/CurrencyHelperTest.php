<?php

namespace Tests\Unit\Helpers;

use App\Helpers\CurrencyHelper;
use App\Models\CurrencySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class CurrencyHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure the cache is cleared before each test
        CurrencyHelper::clearSettingsCache();
        CurrencySetting::truncate();
    }

    protected function tearDown(): void
    {
        // Clear cache after each test as well
        CurrencyHelper::clearSettingsCache();
        parent::tearDown();
    }

    /**
     * @test
     * @group helpers
     * @group currency-helper
     */
    public function it_formats_amount_with_default_settings_when_db_is_empty()
    {
        $formatted = CurrencyHelper::format(12345.67);
        $defaultSettings = CurrencyHelper::getDefaultSettings();

        $expected = number_format(
            12345.67,
            $defaultSettings->decimal_places,
            $defaultSettings->decimal_separator,
            $defaultSettings->thousand_separator
        );

        $expectedWithPosition = $defaultSettings->position === 'prefix'
            ? $defaultSettings->currency_symbol . ' ' . $expected
            : $expected . ' ' . $defaultSettings->currency_symbol;

        $this->assertEquals($expectedWithPosition, $formatted);
    }

    /**
     * @test
     * @group helpers
     * @group currency-helper
     */
    public function it_formats_amount_with_database_settings()
    {
        CurrencySetting::factory()->create([
            'currency_symbol' => '$',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'position' => 'prefix',
        ]);

        // Clear cache after creating the database record
        CurrencyHelper::clearSettingsCache();

        $formatted = CurrencyHelper::format(12345.67);
        $this->assertEquals('$ 12,345.67', $formatted);
    }

    /**
     * @test
     * @group helpers
     * @group currency-helper
     */
    public function it_formats_amount_with_suffix_position()
    {
        CurrencySetting::factory()->create([
            'currency_symbol' => 'USD',
            'position' => 'suffix',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
        ]);

        // Clear cache after creating the database record
        CurrencyHelper::clearSettingsCache();

        $formatted = CurrencyHelper::format(500);
        $this->assertEquals('500.00 USD', $formatted);
    }

    /**
     * @test
     * @group helpers
     * @group currency-helper
     */
    public function it_gets_correct_currency_symbol()
    {
        CurrencySetting::factory()->create(['currency_symbol' => '€']);

        // Clear cache after creating the database record
        CurrencyHelper::clearSettingsCache();

        $this->assertEquals('€', CurrencyHelper::getCurrencySymbol());
    }

    /**
     * @test
     * @group helpers
     * @group currency-helper
     */
    public function it_gets_correct_currency_position()
    {
        CurrencySetting::factory()->create(['position' => 'suffix']);

        // Clear cache after creating the database record
        CurrencyHelper::clearSettingsCache();

        $this->assertEquals('suffix', CurrencyHelper::getCurrencyPosition());
    }

    /**
     * @test
     * @group helpers
     * @group currency-helper
     */
    public function it_uses_cached_settings_on_subsequent_calls()
    {
        // First call, should query the database and cache the settings
        CurrencySetting::factory()->create(['currency_symbol' => '£']);

        // Clear cache to force fresh load from DB
        CurrencyHelper::clearSettingsCache();

        $this->assertEquals('£', CurrencyHelper::getCurrencySymbol());

        // Change the setting in the database
        CurrencySetting::first()->update(['currency_symbol' => '$']);

        // Second call, should return the cached value '£'
        $this->assertEquals('£', CurrencyHelper::getCurrencySymbol());

        // Clear cache and test again, should now be '$'
        CurrencyHelper::clearSettingsCache();
        $this->assertEquals('$', CurrencyHelper::getCurrencySymbol());
    }

    /**
     * @test
     * @group helpers
     * @group currency-helper
     */
    public function it_can_use_test_settings()
    {
        $testSettings = (object) [
            'currency_symbol' => 'TEST',
            'decimal_places' => 4,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'position' => 'prefix',
        ];

        CurrencyHelper::setSettingsForTesting($testSettings);

        $this->assertEquals('TEST', CurrencyHelper::getCurrencySymbol());
        $this->assertEquals('TEST 10.000,0000', CurrencyHelper::format(10000));
    }

    /**
     * @test
     * @group helpers
     * @group currency-helper
     */
    public function it_formats_date_correctly()
    {
        $date = '2023-10-27 10:00:00';
        $this->assertEquals('27 Oct 2023', CurrencyHelper::formatDate($date));

        $carbonDate = Carbon::parse('2024-01-01');
        $this->assertEquals('01 Jan 2024', CurrencyHelper::formatDate($carbonDate));
    }

    /**
     * @test
     * @group helpers
     * @group currency-helper
     */
    public function it_handles_zero_and_null_amounts()
    {
        CurrencySetting::factory()->create([
            'currency_symbol' => '$',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'position' => 'prefix',
        ]);

        // Clear cache after creating the database record
        CurrencyHelper::clearSettingsCache();

        $this->assertEquals('$ 0.00', CurrencyHelper::format(0));
        $this->assertEquals('$ 0.00', CurrencyHelper::format(null));
    }

    /**
     * @test
     * @group helpers
     * @group currency-helper
     */
    public function it_formats_negative_amounts_correctly()
    {
        CurrencySetting::factory()->create([
            'currency_symbol' => '$',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'position' => 'prefix',
        ]);

        // Clear cache after creating the database record
        CurrencyHelper::clearSettingsCache();

        $this->assertEquals('-$ 12,345.67', CurrencyHelper::format(-12345.67));
        $this->assertEquals('$ 0.00', CurrencyHelper::format(-0.00));
    }
}