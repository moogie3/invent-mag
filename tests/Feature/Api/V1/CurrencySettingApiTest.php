<?php

namespace Tests\Feature\Api\V1;

use App\Models\CurrencySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CurrencySettingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('view-currency-settings');
        Permission::findOrCreate('edit-currency-settings');

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'view-currency-settings',
            'edit-currency-settings',
        ]);

        $this->userWithoutPermission = User::factory()->create();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_currency_settings_api()
    {
        $this->getJson('/api/v1/currency-settings')
            ->assertStatus(401);

        $this->putJson('/api/v1/currency-settings/1', [])
            ->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_cannot_view_or_update_currency_settings()
    {
        $setting = CurrencySetting::factory()->create();

        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/currency-settings')
            ->assertStatus(403);

        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/currency-settings/{$setting->id}", [])
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_get_currency_settings()
    {
        CurrencySetting::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/currency-settings')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'currency_symbol',
                    'currency_code',
                    'locale',
                ],
            ]);
    }

    #[Test]
    public function authenticated_user_can_update_currency_settings()
    {
        $setting = CurrencySetting::factory()->create();

        $payload = [
            'currency_symbol' => '€',
            'currency_code' => 'EUR',
            'locale' => 'fr_FR',
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'decimal_places' => 2,
            'position' => 'prefix',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/currency-settings/{$setting->id}", $payload)
            ->assertStatus(200);

        $this->assertDatabaseHas('currency_settings', [
            'id' => $setting->id,
            'currency_symbol' => '€',
        ]);
    }
}
