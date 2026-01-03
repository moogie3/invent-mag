<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;

class ProfileControllerTest extends TestCase
{
    use WithFaker, CreatesTenant, RefreshDatabase;

    protected $profileServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->user->assignRole('superuser');

        // Mock the ProfileService
        $this->profileServiceMock = Mockery::mock(ProfileService::class);
        $this->app->instance(ProfileService::class, $this->profileServiceMock);

        // Fake the storage disk
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_edit_displays_profile_edit_page()
    {
        $response = $this->actingAs($this->user)->get(route('admin.setting.profile.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.profile.profile-edit');
    }

    public function test_update_updates_profile_successfully()
    {
        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'timezone' => 'UTC',
        ];

        $this->profileServiceMock->shouldReceive('updateUser')
            ->once()
            ->andReturn(['success' => true]);

        $response = $this->actingAs($this->user)->put(route('admin.setting.profile.update'), $updateData);

        $response->assertRedirect(route('admin.setting.profile.edit'));
        $response->assertSessionHas('success', 'Profile updated successfully!');
    }

    
    public function test_update_handles_failed_update()
    {
        $updateData = ['name' => 'New Name', 'email' => 'new@example.com', 'timezone' => 'UTC'];

        $this->profileServiceMock->shouldReceive('updateUser')
            ->once()
            ->andReturn(['success' => false, 'message' => 'Incorrect password']);

        $response = $this->actingAs($this->user)->put(route('admin.setting.profile.update'), $updateData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['profile_update_error' => 'Incorrect password']);
    }

    
    public function test_update_validates_request_data()
    {
        $response = $this->actingAs($this->user)->put(route('admin.setting.profile.update'), ['name' => '']);
        $response->assertSessionHasErrors('name');

        $response = $this->actingAs($this->user)->put(route('admin.setting.profile.update'), ['email' => 'not-an-email']);
        $response->assertSessionHasErrors('email');
    }

    
    public function test_update_handles_ajax_request_on_failure()
    {
        $updateData = ['name' => 'New Name', 'email' => 'new@example.com', 'timezone' => 'UTC'];

        $this->profileServiceMock->shouldReceive('updateUser')
            ->once()
            ->andReturn(['success' => false, 'message' => 'Incorrect password']);

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->put(route('admin.setting.profile.update'), $updateData);

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'message' => 'Incorrect password']);
    }

    public function test_update_handles_ajax_request_on_success()
    {
        $updateData = ['name' => 'New Name', 'email' => 'new@example.com', 'timezone' => 'UTC'];

        $this->profileServiceMock->shouldReceive('updateUser')
            ->once()
            ->andReturn(['success' => true]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->put(route('admin.setting.profile.update'), $updateData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Profile updated successfully!']);
    }

    
    public function test_delete_avatar_deletes_avatar_successfully()
    {
        $this->profileServiceMock->shouldReceive('deleteAvatar')
            ->once()
            ->with($this->user);

        $response = $this->actingAs($this->user)->delete(route('admin.setting.profile.delete-avatar'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Avatar deleted successfully!');
    }

    
    public function test_delete_avatar_handles_ajax_request()
    {
        $this->profileServiceMock->shouldReceive('deleteAvatar')
            ->once()
            ->with($this->user);

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->delete(route('admin.setting.profile.delete-avatar'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Avatar deleted successfully!']);
    }

    public function test_delete_avatar_handles_service_exception_for_web_request()
    {
        $this->profileServiceMock->shouldReceive('deleteAvatar')
            ->once()
            ->with($this->user)
            ->andThrow(new \Exception('Failed to delete'));

        $response = $this->actingAs($this->user)->delete(route('admin.setting.profile.delete-avatar'));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Failed to delete avatar.');
    }

    public function test_delete_avatar_handles_service_exception_for_ajax_request()
    {
        $this->profileServiceMock->shouldReceive('deleteAvatar')
            ->once()
            ->with($this->user)
            ->andThrow(new \Exception('Failed to delete'));

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->delete(route('admin.setting.profile.delete-avatar'));

        $response->assertStatus(500);
        $response->assertJson(['success' => false, 'message' => 'Failed to delete avatar.']);
    }
}
