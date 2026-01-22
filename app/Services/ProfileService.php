<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function updateUser(User $user, array $data)
    {
        $user->name = $data['name'];
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }
        $user->shopname = $data['shopname'];
        $user->address = $data['address'];
        $user->timezone = $data['timezone'];

        if (isset($data['delete_avatar']) && $data['delete_avatar'] == '1') {
            if ($user->getRawOriginal('avatar')) {
                Storage::disk('public')->delete($user->getRawOriginal('avatar'));
            }
            $user->avatar = null;
        }

        if (isset($data['avatar'])) {
            if ($user->getRawOriginal('avatar')) {
                Storage::disk('public')->delete($user->getRawOriginal('avatar'));
            }
            $user->avatar = $data['avatar']->store('avatars', 'public');
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if (isset($data['current_password']) && isset($data['password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return ['success' => true, 'message' => 'Profile updated successfully!'];
    }

    /**
     * Update user password
     * 
     * @param User $user
     * @param string $password
     * @return array
     */
    public function updatePassword(User $user, string $password): array
    {
        try {
            $user->password = Hash::make($password);
            $user->save();
            
            return [
                'success' => true, 
                'message' => 'Password updated successfully!'
            ];
        } catch (\Exception $e) {
            \Log::error('Password update failed: ' . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Failed to update password. Please try again.'
            ];
        }
    }

    public function deleteAvatar(User $user)
    {
        if ($user->getRawOriginal('avatar')) {
            Storage::disk('public')->delete($user->getRawOriginal('avatar'));
            $user->avatar = null;
            $user->save();
        }

        return ['success' => true, 'message' => 'Avatar deleted successfully!'];
    }
}
