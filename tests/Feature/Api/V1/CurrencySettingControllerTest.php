<?php

namespace Tests\Feature\Api\V1;

use App\Models\CurrencySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CurrencySettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermissions;

    public function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'view-currency-settings',
            'edit-currency-settings',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create a user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo($permissions);

        // Create a user without permissions
        $this->userWithoutPermissions = User::factory()->create();
    }

    #[Test]
    public function test_unauthenticated_user_cannot_access_currency_settings()
    {
        $response = $this->getJson('/api/v1/currency-settings');
        $response->assertStatus(401);

        $response = $this->putJson('/api/v1/currency-settings/1', []); // Test update as well
        $response->assertStatus(401);
    }

    #[Test]
    public function test_unauthorized_user_cannot_view_currency_settings()
    {
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->getJson('/api/v1/currency-settings');
        $response->assertStatus(403);
    }

    #[Test]
    public function test_unauthorized_user_cannot_update_currency_settings()
    {
        $currencySetting = CurrencySetting::factory()->create();
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->putJson('/api/v1/currency-settings/' . $currencySetting->id, []);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_can_get_currency_settings()
    {
        $currencySetting = CurrencySetting::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/currency-settings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'currency_symbol',
                'decimal_separator',
                'thousand_separator',
                'decimal_places',
                'position',
                'currency_code',
                'locale',
            ]
        ]);
        $response->assertJsonFragment(['id' => $currencySetting->id]);
    }

    #[Test]
    public function test_update_fails_with_invalid_data()
    {
        $currencySetting = CurrencySetting::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/currency-settings/' . $currencySetting->id, [
            'currency_symbol' => '', // Required
            'decimal_places' => 'invalid', // Integer
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency_symbol', 'decimal_places']);
    }

    #[Test]
    public function test_can_update_currency_settings()
    {
        $currencySetting = CurrencySetting::factory()->create([
            'currency_symbol' => '$',
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'USD',
            'locale' => 'en_US',
        ]);

        $updateData = [
            'currency_symbol' => '€',
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'decimal_places' => 2,
            'position' => 'suffix',
            'currency_code' => 'EUR',
            'locale' => 'fr_FR',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/currency-settings/' . $currencySetting->id, $updateData);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'currency_symbol' => '€',
            'currency_code' => 'EUR',
            'locale' => 'fr_FR',
        ]);
        $this->assertDatabaseHas('currency_settings', [
            'id' => $currencySetting->id,
            'currency_symbol' => '€',
        ]);
    }
}