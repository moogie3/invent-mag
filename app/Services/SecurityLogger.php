<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SecurityLogger
{
    public static function logPasswordChange($user)
    {
        Log::channel('security')->info('Password changed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public static function logFailedLogin($email, $reason = 'Invalid credentials')
    {
        Log::channel('security')->warning('Failed login attempt', [
            'email' => $email,
            'reason' => $reason,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public static function logSuccessfulLogin($user)
    {
        Log::channel('security')->info('Successful login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public static function logPermissionDenied($user, $permission)
    {
        Log::channel('security')->warning('Permission denied', [
            'user_id' => $user->id,
            'email' => $user->email,
            'permission' => $permission,
            'route' => request()->path(),
            'ip' => request()->ip(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public static function logSuspiciousActivity($description, $data = [])
    {
        Log::channel('security')->alert('Suspicious activity detected', array_merge([
            'description' => $description,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ], $data));
    }
}
