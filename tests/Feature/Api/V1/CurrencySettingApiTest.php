<?php

namespace Tests\Feature\Api\V1;

use App\Models\CurrencySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;

class CurrencySettingApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        $this->userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_currency_settings_api()
    {
        Auth::guard('web')->logout();
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/currency-settings')
            ->assertStatus(401);

        $this->withHeaders(['Accept' => 'application/json'])
            ->putJson('/api/v1/currency-settings/1', [])
            ->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_cannot_view_or_update_currency_settings()
    {
        $setting = CurrencySetting::factory()->create(['tenant_id' => $this->tenant->id]);

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
        CurrencySetting::factory()->create(['tenant_id' => $this->tenant->id]);

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
        $setting = CurrencySetting::factory()->create(['tenant_id' => $this->tenant->id]);

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