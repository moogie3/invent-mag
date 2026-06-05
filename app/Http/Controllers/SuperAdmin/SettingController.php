<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'mail_driver' => config('mail.default'),
            'mail_from_address' => config('mail.from.address'),
            'mail_from_name' => config('mail.from.name'),
        ];

        return view('superadmin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'app_debug' => 'boolean',
        ]);

        $envFile = base_path('.env');
        $env = file_get_contents($envFile);

        $env = preg_replace('/^APP_NAME=.*/m', 'APP_NAME="' . $validated['app_name'] . '"', $env);
        $env = preg_replace('/^APP_URL=.*/m', 'APP_URL=' . $validated['app_url'], $env);
        $env = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=' . ($request->boolean('app_debug') ? 'true' : 'false'), $env);

        file_put_contents($envFile, $env);

        Artisan::call('config:clear');

        return back()->with('success', 'Settings updated successfully.');
    }
}
