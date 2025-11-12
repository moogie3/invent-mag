<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\CurrencySetting;
use App\Services\CurrencyService;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected $currencyServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);

        $this->seed(CurrencySeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');

        $this->actingAs($this->user);

        // Mock the CurrencyService
        $this->currencyServiceMock = Mockery::mock(CurrencyService::class);
        $this->app->instance(CurrencyService::class, $this->currencyServiceMock);
    }

    public function test_it_can_display_the_currency_edit_page()
    {
        $currencySetting = CurrencySetting::factory()->create();
        $currencyData = [
            'setting' => $currencySetting,
            'entries' => 10,
            'units' => new LengthAwarePaginator(collect([]), 0, 10),
            'totalunit' => 0,
            'predefinedCurrencies' => [
                ['name' => 'United States Dollar', 'code' => 'USD', 'locale' => 'en-US', 'symbol' => '$'],
            ],
        ];

        $this->currencyServiceMock->shouldReceive('getCurrencyEditData')
            ->once()
            ->andReturn($currencyData);

        $response = $this->get(route('admin.setting.currency.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.currency.currency-edit');
        $response->assertViewHas('setting', $currencyData['setting']);
        $response->assertViewHas('entries', $currencyData['entries']);
        $response->assertViewHas('predefinedCurrencies', $currencyData['predefinedCurrencies']);
    }

    public function test_it_can_update_currency_settings()
    {
        $updateData = [
            'currency_symbol' => '$',
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'USD',
            'locale' => 'en_US',
        ];

        $this->currencyServiceMock->shouldReceive('updateCurrency')
            ->once()
            ->with(Mockery::subset($updateData))
            ->andReturn(['success' => true]);

        $response = $this->post(route('admin.setting.currency.update'), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Currency settings updated successfully.');

        // We no longer assert database has since the service is mocked
        // $this->assertDatabaseHas('currency_settings', $updateData);
    }

    public function test_update_currency_settings_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'currency_symbol' => 'TOOLONG', // Max 5
            'decimal_separator' => '..', // Max 1
            'thousand_separator' => '..', // Max 1
            'decimal_places' => 5, // Max 4
            'position' => 'invalid', // Not in: prefix,suffix
            'currency_code' => 'TOOLONG', // Max 3
            'locale' => 'TOOLONGTOOLONG', // Max 10
        ];

        $this->currencyServiceMock->shouldNotReceive('updateCurrency');

        $response = $this->post(route('admin.setting.currency.update'), $invalidData);

        $response->assertSessionHasErrors([
            'currency_symbol',
            'decimal_separator',
            'thousand_separator',
            'decimal_places',
            'position',
            'currency_code',
            'locale',
        ]);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_update_currency_settings_handles_service_level_error()
    {
        $updateData = [
            'currency_symbol' => '
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'USD',
            'locale' => 'en_US',
        ];

        $this->currencyServiceMock->shouldReceive('updateCurrency')
            ->once()
            ->with(Mockery::subset($updateData))
            ->andReturn(['success' => false, 'message' => 'Currency update failed.']);

        $response = $this->post(route('admin.setting.currency.update'), $updateData);

        $response->assertSessionHasErrors(['error' => 'Currency update failed.']); // Assuming generic error key
        $response->assertStatus(302);
    }

    public function test_it_can_update_currency_settings_via_ajax()
    {
        $updateData = [
            'currency_symbol' => '
},
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'decimal_places' => 2,
            'position' => 'suffix',
            'currency_code' => 'EUR',
            'locale' => 'de_DE',
        ];

        $this->currencyServiceMock->shouldReceive('updateCurrency')
            ->once()
            ->with(Mockery::subset($updateData))
            ->andReturn(['success' => true]);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.setting.currency.update'), $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Currency settings updated successfully.']);
    }
},
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'USD',
            'locale' => 'en_US',
        ];

        $this->currencyServiceMock->shouldReceive('updateCurrency')
            ->once()
            ->with(Mockery::subset($updateData))
            ->andReturn(['success' => false, 'message' => 'Currency update failed.']);

        $response = $this->post(route('admin.setting.currency.update'), $updateData);

        $response->assertSessionHasErrors(['error' => 'Currency update failed.']); // Assuming generic error key
        $response->assertStatus(302);
    }

    public function test_it_can_update_currency_settings_via_ajax()
    {
        $updateData = [
            'currency_symbol' => '
},
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'decimal_places' => 2,
            'position' => 'suffix',
            'currency_code' => 'EUR',
            'locale' => 'de_DE',
        ];

        $this->currencyServiceMock->shouldReceive('updateCurrency')
            ->once()
            ->with(Mockery::subset($updateData))
            ->andReturn(['success' => true]);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('POST', route('admin.setting.currency.update'), $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Currency settings updated successfully.']);
    }
}