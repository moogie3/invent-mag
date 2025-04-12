<?php namespace App\Http\Controllers\Admin;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use App\Models\User;
    use Illuminate\Support\Facades\Storage;

    class ProfileController extends Controller
    {
        public function edit()
        {
            return view('admin.profile.profile-edit');
        }

        public function update(Request $request)
        {
            /**
             * @var \App\Models\User $user
             */
            $user = Auth::user();

            // validate input
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'shopname' => 'required|string',
                'address' => 'required|string',
                'timezone' => 'required|string|in:' . implode(',', timezone_identifiers_list()), // ← add this
                'password' => 'nullable|min:6|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // update user details
            $user->name = $request->name;
            $user->email = $request->email;
            $user->shopname = $request->shopname;
            $user->address = $request->address;
            $user->timezone = $request->timezone;

            if ($request->filled('password')) {
                $request->validate([
                    'current_password' => 'required|string',
                ]);

                if (!Hash::check($request->current_password, $user->password)) {
                    return redirect()
                        ->back()
                        ->withErrors(['current_password' => 'Current password is incorrect.']);
                }

                $user->password = Hash::make($request->password);
            }

            // handle avatar upload
            if ($request->hasFile('avatar')) {
                // delete the old avatar if it exists
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // upload new avatar
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $avatarPath;
            }

            $user->save();

            return redirect()->back()->with('success', 'Profile updated successfully!');
        }

        public function deleteAvatar()
        {
            /**
             * @var \App\Models\User $user
             */
            $user = Auth::user();

            if ($user->avatar) {
                Storage::delete('public/' . $user->avatar);
                $user->save();
            }

            return redirect()->back()->with('success', 'Avatar deleted successfully!');
        }
    }