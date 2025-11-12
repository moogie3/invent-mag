<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\CurrencySetting;
use App\Services\CurrencyService;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CurrencyService $currencyService;
    protected array $validData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CurrencySeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');

        $this->actingAs($this->user);

        $this->currencyService = $this->app->make(CurrencyService::class);

        $this->validData = [
            'currency_symbol' => '$',
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'USD',
            'locale' => 'en_US',
        ];
    }

    // Test group for viewing currency settings
    public function test_admin_can_view_currency_settings_page()
    {
        $response = $this->get(route('admin.setting.currency.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.currency.currency-edit');
        $response->assertViewHasAll(['setting', 'entries', 'predefinedCurrencies']);
    }

    public function test_unauthorized_user_is_forbidden_from_currency_settings_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('admin.setting.currency.edit'));
        $response->assertStatus(403);
    }

    // Test group for updating currency settings
    public function test_admin_can_update_currency_settings()
    {
        $initialSetting = CurrencySetting::first();
        $updateData = [
            'currency_symbol' => 'Rp',
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'IDR',
            'locale' => 'id_ID',
        ];

        $response = $this->post(route('admin.setting.currency.update'), $updateData);

        $response->assertRedirect(route('admin.setting.currency.edit'));
        $response->assertSessionHas('success', 'Currency settings updated successfully.');

        $this->assertDatabaseHas('currency_settings', array_merge(['id' => $initialSetting->id], $updateData));
    }

    public function test_admin_can_update_currency_settings_via_ajax()
    {
        $initialSetting = CurrencySetting::first();

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.setting.currency.update'), $this->validData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Currency settings updated successfully.']);

        $this->assertDatabaseHas('currency_settings', array_merge(['id' => $initialSetting->id], $this->validData));
    }

    public function test_unauthorized_user_cannot_update_currency_settings()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('admin.setting.currency.update'), $this->validData);

        $response->assertStatus(403);
    }

    // Test group for validation
    public function test_currency_update_fails_with_invalid_data()
    {
        $invalidData = [
            'currency_symbol' => 'TOOLONG',
            'decimal_separator' => '..',
            'thousand_separator' => '..',
            'decimal_places' => 99,
            'position' => 'invalid_position',
            'currency_code' => 'INVALID',
            'locale' => 'invalid_locale_string_too_long',
        ];

        $response = $this->post(route('admin.setting.currency.update'), $invalidData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'currency_symbol',
            'decimal_separator',
            'thousand_separator',
            'decimal_places',
            'position',
            'currency_code',
            'locale',
        ]);
    }

    public function test_currency_update_fails_with_invalid_data_via_ajax()
    {
        $invalidData = array_merge($this->validData, ['currency_symbol' => 'TOOLONG']);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.setting.currency.update'), $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency_symbol']);
    }

    // Test group for service layer robustness using mocks
    public function test_it_handles_service_exception_on_update()
    {
        $currencyServiceMock = Mockery::mock(CurrencyService::class);
        $this->app->instance(CurrencyService::class, $currencyServiceMock);

        $currencyServiceMock->shouldReceive('updateCurrency')->andThrow(new \Exception('Service error'));

        $response = $this->post(route('admin.setting.currency.update'), $this->validData);

        $response->assertRedirect(route('admin.setting.currency.edit'));
        $response->assertSessionHas('error');
    }

    public function test_it_handles_service_exception_on_update_via_ajax()
    {
        $currencyServiceMock = Mockery::mock(CurrencyService::class);
        $this->app->instance(CurrencyService::class, $currencyServiceMock);

        $currencyServiceMock->shouldReceive('updateCurrency')->andThrow(new \Exception('Service error'));

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.setting.currency.update'), $this->validData);

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }
}
