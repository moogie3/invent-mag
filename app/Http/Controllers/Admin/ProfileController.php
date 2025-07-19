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
            return view('admin.profile.profile-edit');
        }

        public function update(Request $request)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . Auth::id(),
                'shopname' => 'required|string',
                'address' => 'required|string',
                'timezone' => 'required|string|in:' . implode(',', timezone_identifiers_list()),
                'password' => 'nullable|min:6|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $result = $this->profileService->updateUser(Auth::user(), $request->all());

            if (!$result['success']) {
                return redirect()->back()->withErrors(['current_password' => $result['message']]);
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Profile updated successfully!']);
            }

            return redirect()->back()->with('success', 'Profile updated successfully!');
        }

        public function deleteAvatar(Request $request)
        {
            $this->profileService->deleteAvatar(Auth::user());

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Avatar deleted successfully!']);
            }

            return redirect()->back()->with('success', 'Avatar deleted successfully!');
        }
    }
