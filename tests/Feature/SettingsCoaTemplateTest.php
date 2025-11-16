<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

class SettingsCoaTemplateTest extends TestCase
{
    use RefreshDatabase; // Resets the database for each test

    protected function setUp(): void
    {
        parent::setUp();

        // Create a superuser role and assign it to the user
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superuser']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        // Ensure template files exist for testing
        $universalCoaContent = '[{"name": "Cash", "code": "1000", "type": "asset", "parent_code": null, "is_active": true}]';
        File::put(database_path('data/coa_templates/universal.json'), $universalCoaContent);

        $indonesianCoaContent = '[{"name": "Kas", "code": "1000", "type": "asset", "parent_code": null, "is_active": true}]';
        File::put(database_path('data/coa_templates/indonesian.json'), $indonesianCoaContent);
    }

    protected function tearDown(): void
    {
        // Clean up created template files
        File::delete(database_path('data/coa_templates/universal.json'));
        File::delete(database_path('data/coa_templates/indonesian.json'));
        parent::tearDown();
    }

    /** @test */
    public function a_user_can_apply_the_universal_coa_template()
    {
        // Ensure some accounts exist before applying template to test truncation
        Account::factory()->create(['name' => 'Old Account']);
        $this->assertCount(1, Account::all());

        $response = $this->post(route('admin.setting.apply-coa-template'), [
            'template' => 'universal',
        ]);

        $response->assertRedirect(route('admin.setting.index'));
        $response->assertSessionHas('success', 'Chart of Accounts template applied successfully.');

        $this->assertCount(1, Account::all()); // Only the universal account should exist
        $this->assertDatabaseHas('accounts', ['name' => 'Cash', 'code' => '1000']);
        $this->assertDatabaseMissing('accounts', ['name' => 'Old Account']);
    }

    /** @test */
    public function a_user_can_apply_the_indonesian_coa_template()
    {
        // Ensure some accounts exist before applying template to test truncation
        Account::factory()->create(['name' => 'Another Old Account']);
        $this->assertCount(1, Account::all());

        $response = $this->post(route('admin.setting.apply-coa-template'), [
            'template' => 'indonesian',
        ]);

        $response->assertRedirect(route('admin.setting.index'));
        $response->assertSessionHas('success', 'Chart of Accounts template applied successfully.');

        $this->assertCount(1, Account::all()); // Only the indonesian account should exist
        $this->assertDatabaseHas('accounts', ['name' => 'Kas', 'code' => '1000']);
        $this->assertDatabaseMissing('accounts', ['name' => 'Another Old Account']);
    }

    /** @test */
    public function applying_a_non_existent_coa_template_shows_an_error()
    {
        $initialAccountCount = Account::count();

        $response = $this->post(route('admin.setting.apply-coa-template'), [
            'template' => 'non_existent',
        ]);

        $response->assertRedirect(); // Should redirect back
        $response->assertSessionHas('error', 'Template not found.');

        // Assert that no accounts were created or deleted
        $this->assertCount($initialAccountCount, Account::all());
    }
}
