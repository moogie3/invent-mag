<?php namespace App\Http\Controllers\Admin;

    use App\Http\Controllers\Controller;
    use App\Services\ProfileService;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;

    class ProfileController extends Controller
    {
        protected $profileService;

        public function __construct(ProfileService $profileService)
        {
            $this->profileService = $profileService;
        }

        public function edit()
        {
            $user = Auth::user();
            $hasApiToken = $user->tokens()->exists();
            return view('admin.profile.profile-edit', compact('hasApiToken'));
        }

        public function update(Request $request)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . Auth::id(),
                'shopname' => 'nullable|string',
                'address' => 'nullable|string',
                'timezone' => 'required|string|in:' . implode(',', timezone_identifiers_list()),
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $result = $this->profileService->updateUser(Auth::user(), $request->except(['password', 'password_confirmation']));

            if (!$result['success']) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $result['message']], 422);
                }
                return redirect()->back()->withErrors(['profile_update_error' => $result['message']]);
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Profile updated successfully!']);
            }

            return redirect()->route('admin.setting.profile.edit')->with('success', 'Profile updated successfully!');
        }

        public function updatePassword(Request $request)
        {
            $request->validate([
                'password' => 'required|string|min:6|confirmed',
            ]);

            $result = $this->profileService->updateUser(Auth::user(), $request->only(['password', 'password_confirmation']));

            if (!$result['success']) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $result['message']], 422);
                }
                return redirect()->back()->withErrors(['password_update_error' => $result['message']]);
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Password updated successfully!']);
            }

            return redirect()->route('admin.setting.profile.edit')->with('success', 'Password updated successfully!');
        }

        public function deleteAvatar(Request $request)
        {
            try {
                $this->profileService->deleteAvatar(Auth::user());

                if ($request->ajax()) {
                    return response()->json(['success' => true, 'message' => 'Avatar deleted successfully!']);
                }

                return redirect()->back()->with('success', 'Avatar deleted successfully!');
            } catch (\Exception $e) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Failed to delete avatar.'], 500);
                }
                return redirect()->back()->with('error', 'Failed to delete avatar.');
            }
        }

        public function generateApiToken(Request $request)
        {
            $user = $request->user();
    
            // Revoke all existing tokens
            $user->tokens()->delete();
    
            // Create a new token
            $token = $user->createToken('api-token')->plainTextToken;
    
            return redirect()->route('admin.setting.profile.edit')->with('api_token', $token);
        }
    }
