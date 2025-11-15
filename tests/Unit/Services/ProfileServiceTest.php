<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ProfileService;
use Tests\Unit\BaseUnitTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class ProfileServiceTest extends BaseUnitTestCase
{
    protected ProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileService = new ProfileService();
        User::truncate();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(ProfileService::class, $this->profileService);
    }

    #[Test]
    public function it_can_update_user_profile_without_password_or_avatar_change()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'shopname' => 'Old Shop',
            'address' => 'Old Address',
            'timezone' => 'UTC',
        ]);

        $data = [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'shopname' => 'New Shop',
            'address' => 'New Address',
            'timezone' => 'America/New_York',
        ];

        $result = $this->profileService->updateUser($user, $data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Profile updated successfully!', $result['message']);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
            'shopname' => 'New Shop',
            'address' => 'New Address',
            'timezone' => 'America/New_York',
            'email_verified_at' => null, // Should be nullified if email changes
        ]);
    }

    #[Test]
    public function it_can_update_user_password_with_correct_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $data = [
            'name' => $user->name, // Required fields
            'shopname' => $user->shopname,
            'address' => $user->address,
            'timezone' => $user->timezone,
            'current_password' => 'old_password',
            'password' => 'new_password',
            'password_confirmation' => 'new_password',
        ];

        $result = $this->profileService->updateUser($user, $data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Profile updated successfully!', $result['message']);
        $this->assertTrue(Hash::check('new_password', $user->fresh()->password));
    }

    #[Test]
    public function it_returns_error_if_current_password_is_incorrect()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $data = [
            'name' => $user->name, // Required fields
            'shopname' => $user->shopname,
            'address' => $user->address,
            'timezone' => $user->timezone,
            'current_password' => 'wrong_password',
            'password' => 'new_password',
            'password_confirmation' => 'new_password',
        ];

        $result = $this->profileService->updateUser($user, $data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Current password is incorrect.', $result['message']);
        $this->assertTrue(Hash::check('old_password', $user->fresh()->password)); // Password should not change
    }

    #[Test]
    public function it_can_update_user_avatar()
    {
        Storage::fake('public');
        $user = User::factory()->create(['avatar' => null]);
        $file = UploadedFile::fake()->image('avatar.jpg');

        $data = [
            'name' => $user->name, // Required fields
            'shopname' => $user->shopname,
            'address' => $user->address,
            'timezone' => $user->timezone,
            'avatar' => $file,
        ];

        $result = $this->profileService->updateUser($user, $data);

        $this->assertTrue($result['success']);
        $this->assertNotNull($user->fresh()->avatar);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    #[Test]
    public function it_deletes_old_avatar_when_new_one_is_uploaded()
    {
        Storage::fake('public');
        $oldAvatarPath = 'avatars/old_avatar.jpg';
        Storage::disk('public')->put($oldAvatarPath, 'old content');

        $user = User::factory()->create(['avatar' => $oldAvatarPath]);
        $newFile = UploadedFile::fake()->image('new_avatar.png');

        $data = [
            'name' => $user->name, // Required fields
            'shopname' => $user->shopname,
            'address' => $user->address,
            'timezone' => $user->timezone,
            'avatar' => $newFile,
        ];

        $result = $this->profileService->updateUser($user, $data);

        $this->assertTrue($result['success']);
        Storage::disk('public')->assertMissing($oldAvatarPath);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    #[Test]
    public function it_can_delete_user_avatar()
    {
        Storage::fake('public');
        $avatarPath = 'avatars/user_avatar.jpg';
        Storage::disk('public')->put($avatarPath, 'content');

        $user = User::factory()->create(['avatar' => $avatarPath]);

        $result = $this->profileService->deleteAvatar($user);

        $this->assertTrue($result['success']);
        $this->assertEquals('Avatar deleted successfully!', $result['message']);
        $this->assertNull($user->fresh()->avatar);
        Storage::disk('public')->assertMissing($avatarPath);
    }
}
