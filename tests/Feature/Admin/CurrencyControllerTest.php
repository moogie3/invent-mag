<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\CurrencySetting;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

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
    }

    public function test_it_can_display_the_currency_edit_page()
    {
        $response = $this->get(route('admin.setting.currency.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.currency.currency-edit');
    }

    public function test_it_can_update_currency_settings()
    {
        $currencySetting = CurrencySetting::factory()->create();

        $updateData = [
            'currency_symbol' => '$',
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'USD',
            'locale' => 'en_US',
        ];

        $response = $this->post(route('admin.setting.currency.update'), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Currency settings updated successfully.');

        $this->assertDatabaseHas('currency_settings', $updateData);
    }
}