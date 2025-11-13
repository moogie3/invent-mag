<?php

namespace Tests\Unit\Services;

use App\Models\CurrencySetting;
use App\Models\Unit;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CurrencyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CurrencyService $currencyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currencyService = new CurrencyService();
        CurrencySetting::truncate();
        Unit::truncate();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(CurrencyService::class, $this->currencyService);
    }

    #[Test]
    public function it_can_get_currency_edit_data()
    {
        // Setup: Create a currency setting
        $currencySetting = CurrencySetting::factory()->create([
            'currency_symbol' => '$',
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'USD',
            'locale' => 'en-US',
        ]);

        // Setup: Create units
        Unit::factory()->count(5)->create();

        $entries = 3;
        $result = $this->currencyService->getCurrencyEditData($entries);

        // Assertions
        $this->assertArrayHasKey('setting', $result);
        $this->assertArrayHasKey('units', $result);
        $this->assertArrayHasKey('entries', $result);
        $this->assertArrayHasKey('totalunit', $result);
        $this->assertArrayHasKey('predefinedCurrencies', $result);

        $this->assertEquals($currencySetting->id, $result['setting']->id);
        $this->assertEquals('$', $result['setting']->currency_symbol);
        $this->assertCount($entries, $result['units']);
        $this->assertEquals(5, $result['totalunit']);
        $this->assertEquals($entries, $result['entries']);
        $this->assertIsArray($result['predefinedCurrencies']);
        $this->assertNotEmpty($result['predefinedCurrencies']);
    }

    #[Test]
    public function it_can_update_currency_settings()
    {
        // Setup: Create an initial currency setting
        $initialSetting = CurrencySetting::factory()->create([
            'currency_symbol' => '$',
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'USD',
            'locale' => 'en-US',
        ]);

        // Define updated data
        $updatedData = [
            'currency_symbol' => '€',
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'decimal_places' => 2,
            'position' => 'suffix',
            'currency_code' => 'EUR',
            'locale' => 'en-EU',
        ];

        // Call the service method
        $result = $this->currencyService->updateCurrency($updatedData);

        // Assertions
        $this->assertTrue($result['success']);
        $this->assertEquals('Currency settings updated successfully.', $result['message']);

        // Assert that the database has been updated
        $this->assertDatabaseHas('currency_settings', [
            'id' => $initialSetting->id,
            'currency_symbol' => '€',
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'position' => 'suffix',
            'currency_code' => 'EUR',
            'locale' => 'en-EU',
        ]);
    }
}