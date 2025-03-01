<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProfileController extends Controller
{
    public function edit(){
        return view('admin.profile.profile-edit');
    }

    public function update(Request $request)
    {
        /**
        * @var \App\Models\User $user
        */
        $user = Auth::user();

        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'shopname' => 'required|string',
            'address' => 'required|string',
            'password' => 'nullable|min:6|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Update user details
        $user->name = $request->name;
        $user->email = $request->email;
        $user->shopname = $request->shopname;
        $user->address = $request->address;

        if ($request->filled('password')) {
            $request->validate([
                'current_password' => 'required|string',
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $user->password = Hash::make($request->password);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }
}