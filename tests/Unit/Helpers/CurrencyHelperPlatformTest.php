<?php

namespace Tests\Unit\Helpers;

use App\Helpers\CurrencyHelper;
use App\Models\PlatformCurrencySetting;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyHelperPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear any records seeded by the migration so each test starts clean
        PlatformCurrencySetting::query()->delete();
        CurrencyHelper::clearPlatformSettingsCache();
    }

    protected function tearDown(): void
    {
        CurrencyHelper::clearPlatformSettingsCache();
        parent::tearDown();
    }

    #[Test]
    #[Group('helpers')]
    #[Group('currency-platform')]
    public function it_uses_default_platform_settings_when_db_is_empty()
    {
        $formatted = CurrencyHelper::formatPlatform(12345.67);
        $default = CurrencyHelper::getDefaultPlatformSettings();

        $expected = '$ 12,345.67';

        $this->assertEquals($expected, $formatted);
    }

    #[Test]
    #[Group('helpers')]
    #[Group('currency-platform')]
    public function it_formats_amount_with_platform_db_settings()
    {
        PlatformCurrencySetting::create([
            'currency_symbol' => '€',
            'decimal_places' => 2,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'position' => 'prefix',
            'currency_code' => 'EUR',
        ]);

        CurrencyHelper::clearPlatformSettingsCache();

        $formatted = CurrencyHelper::formatPlatform(12345.67);
        $this->assertEquals('€ 12.345,67', $formatted);
    }

    #[Test]
    #[Group('helpers')]
    #[Group('currency-platform')]
    public function it_formats_with_suffix_position()
    {
        PlatformCurrencySetting::create([
            'currency_symbol' => 'USD',
            'position' => 'suffix',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'currency_code' => 'USD',
        ]);

        CurrencyHelper::clearPlatformSettingsCache();

        $formatted = CurrencyHelper::formatPlatform(500);
        $this->assertEquals('500.00 USD', $formatted);
    }

    #[Test]
    #[Group('helpers')]
    #[Group('currency-platform')]
    public function it_uses_cached_platform_settings_on_subsequent_calls()
    {
        PlatformCurrencySetting::create([
            'currency_symbol' => '£',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'position' => 'prefix',
            'currency_code' => 'GBP',
        ]);

        CurrencyHelper::clearPlatformSettingsCache();

        $this->assertEquals('£ 100.00', CurrencyHelper::formatPlatform(100));

        PlatformCurrencySetting::first()->update(['currency_symbol' => '$']);
        $this->assertEquals('£ 100.00', CurrencyHelper::formatPlatform(100));

        CurrencyHelper::clearPlatformSettingsCache();
        $this->assertEquals('$ 100.00', CurrencyHelper::formatPlatform(100));
    }

    #[Test]
    #[Group('helpers')]
    #[Group('currency-platform')]
    public function it_can_use_test_platform_settings()
    {
        $testSettings = (object) [
            'currency_symbol' => 'TEST',
            'decimal_places' => 4,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'position' => 'prefix',
            'currency_code' => 'IDR',
        ];

        CurrencyHelper::setPlatformSettingsForTesting($testSettings);

        $this->assertEquals('TEST 10.000,0000', CurrencyHelper::formatPlatform(10000));
    }

    #[Test]
    #[Group('helpers')]
    #[Group('currency-platform')]
    public function it_handles_zero_and_null_amounts()
    {
        PlatformCurrencySetting::create([
            'currency_symbol' => '$',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'position' => 'prefix',
            'currency_code' => 'USD',
        ]);

        CurrencyHelper::clearPlatformSettingsCache();

        $this->assertEquals('$ 0.00', CurrencyHelper::formatPlatform(0));
        $this->assertEquals('$ 0.00', CurrencyHelper::formatPlatform(null));
    }

    #[Test]
    #[Group('helpers')]
    #[Group('currency-platform')]
    public function it_handles_negative_amounts()
    {
        PlatformCurrencySetting::create([
            'currency_symbol' => '$',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'position' => 'prefix',
            'currency_code' => 'USD',
        ]);

        CurrencyHelper::clearPlatformSettingsCache();

        $this->assertEquals('-$ 12,345.67', CurrencyHelper::formatPlatform(-12345.67));
    }

    #[Test]
    #[Group('helpers')]
    #[Group('currency-platform')]
    public function it_is_independent_from_tenant_currency_settings()
    {
        PlatformCurrencySetting::create([
            'currency_symbol' => '€',
            'decimal_places' => 2,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'position' => 'prefix',
            'currency_code' => 'EUR',
        ]);

        CurrencyHelper::clearPlatformSettingsCache();

        $platformFormatted = CurrencyHelper::formatPlatform(1000);
        $this->assertEquals('€ 1.000,00', $platformFormatted);
    }
}
