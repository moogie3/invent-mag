<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $adminUser;
    protected $profileServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for authentication
        $this->adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create the superuser role if it doesn't exist
        $superUserRole = Role::firstOrCreate(['name' => 'superuser']);
        // Assign the superuser role to the admin user
        $this->adminUser->assignRole($superUserRole);

        // Mock the ProfileService
        $this->profileServiceMock = Mockery::mock(ProfileService::class);
        $this->app->instance(ProfileService::class, $this->profileServiceMock);

        // Fake the storage disk
        Storage::fake('public');
    }

    public function test_edit_displays_profile_edit_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.setting.profile.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.profile.profile-edit');
    }

    public function test_update_updates_profile_successfully()
    {
        $this->withoutMiddleware();
        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'timezone' => 'UTC',
        ];

        $this->profileServiceMock->shouldReceive('updateUser')
            ->once()
            ->with($this->adminUser, Mockery::on(function ($data) use ($updateData) {
                return $data['name'] === $updateData['name'] &&
                       $data['email'] === $updateData['email'] &&
                       $data['timezone'] === $updateData['timezone'];
            }))
            ->andReturn(['success' => true]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.setting.profile.update'), $updateData);

        $response->assertRedirect(route('admin.setting.profile.edit'));
        $response->assertSessionHas('success', 'Profile updated successfully!');
    }

    
    public function test_update_handles_failed_update()
    {
        $this->withoutMiddleware();
        $updateData = ['name' => 'New Name', 'email' => 'new@example.com', 'timezone' => 'UTC'];

        $this->profileServiceMock->shouldReceive('updateUser')
            ->once()
            ->andReturn(['success' => false, 'message' => 'Incorrect password']);

        $response = $this->actingAs($this->adminUser)->put(route('admin.setting.profile.update'), $updateData);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['current_password' => 'Incorrect password']);
    }

    
    public function test_update_validates_request_data()
    {
        $this->withoutMiddleware();
        $response = $this->actingAs($this->adminUser)->put(route('admin.setting.profile.update'), ['name' => '']);
        $response->assertSessionHasErrors('name');

        $response = $this->actingAs($this->adminUser)->put(route('admin.setting.profile.update'), ['email' => 'not-an-email']);
        $response->assertSessionHasErrors('email');

        $response = $this->actingAs($this->adminUser)->put(route('admin.setting.profile.update'), ['password' => '123']);
        $response->assertSessionHasErrors('password');
    }

    
    public function test_update_handles_ajax_request_on_failure()
    {
        $this->withoutMiddleware();
        $updateData = ['name' => 'New Name', 'email' => 'new@example.com', 'timezone' => 'UTC'];

        $this->profileServiceMock->shouldReceive('updateUser')
            ->once()
            ->andReturn(['success' => false, 'message' => 'Incorrect password']);

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->put(route('admin.setting.profile.update'), $updateData);

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'message' => 'Incorrect password']);
    }

    
    public function test_delete_avatar_deletes_avatar_successfully()
    {
        $this->withoutMiddleware();
        $this->profileServiceMock->shouldReceive('deleteAvatar')
            ->once()
            ->with($this->adminUser);

        $response = $this->actingAs($this->adminUser)->delete(route('admin.setting.profile.delete-avatar'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Avatar deleted successfully!');
    }

    
    public function test_delete_avatar_handles_ajax_request()
    {
        $this->withoutMiddleware();
        $this->profileServiceMock->shouldReceive('deleteAvatar')
            ->once()
            ->with($this->adminUser);

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->delete(route('admin.setting.profile.delete-avatar'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Avatar deleted successfully!']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}