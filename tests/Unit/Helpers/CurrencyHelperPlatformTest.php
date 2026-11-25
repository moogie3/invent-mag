<?php

namespace Tests\Unit\Helpers;

use App\Helpers\CurrencyHelper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyHelperPlatformTest extends TestCase
{
    protected function tearDown(): void
    {
        CurrencyHelper::clearPlatformSettingsCache();
        parent::tearDown();
    }

    #[Test]
    #[Group('helpers')]
    #[Group('currency-platform')]
    public function it_uses_default_platform_settings()
    {
        $formatted = CurrencyHelper::formatPlatform(12345.67);
        $default = CurrencyHelper::getDefaultPlatformSettings();

        $this->assertEquals('$ 12,345.67', $formatted);
        $this->assertEquals('$', $default->currency_symbol);
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
        CurrencyHelper::clearPlatformSettingsCache();

        $this->assertEquals('$ 0.00', CurrencyHelper::formatPlatform(0));
        $this->assertEquals('$ 0.00', CurrencyHelper::formatPlatform(null));
    }

    #[Test]
    #[Group('helpers')]
    #[Group('currency-platform')]
    public function it_handles_negative_amounts()
    {
        CurrencyHelper::clearPlatformSettingsCache();

        $this->assertEquals('-$ 12,345.67', CurrencyHelper::formatPlatform(-12345.67));
    }
}
