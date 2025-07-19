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
        $user->email = $data['email'];
        $user->shopname = $data['shopname'];
        $user->address = $data['address'];
        $user->timezone = $data['timezone'];

        if (isset($data['password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }
            $user->password = Hash::make($data['password']);
        }

        if (isset($data['avatar'])) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $data['avatar']->store('avatars', 'public');
        }

        $user->save();

        return ['success' => true, 'message' => 'Profile updated successfully!'];
    }

    public function deleteAvatar(User $user)
    {
        if ($user->avatar) {
            Storage::delete('public/' . $user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return ['success' => true, 'message' => 'Avatar deleted successfully!'];
    }
}
