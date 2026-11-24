<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SecurityLogger
{
    public static function logPasswordChange($user)
    {
        self::write('info', 'Password changed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public static function logFailedLogin($email, $reason = 'Invalid credentials')
    {
        self::write('warning', 'Failed login attempt', [
            'email' => $email,
            'reason' => $reason,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public static function logSuccessfulLogin($user)
    {
        self::write('info', 'Successful login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public static function logPermissionDenied($user, $permission)
    {
        self::write('warning', 'Permission denied', [
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
        self::write('alert', 'Suspicious activity detected', array_merge([
            'description' => $description,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ], $data));
    }

    /**
     * Record an arbitrary security event so every security write in the app
     * funnels through the same guarded path instead of calling the channel
     * directly and risking an unhandled exception mid-request.
     */
    public static function log(string $level, string $message, array $context = [])
    {
        self::write($level, $message, $context + [
            'ip' => request()->ip(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * A security audit write happens AFTER the caller's database work is
     * committed, so it must never be able to abort the request. If the security
     * channel is unavailable (a log file the web user cannot append to), the
     * event falls back to the main log and then to PHP's own error log, so the
     * audit trail is kept and the user never sees a 500 for work that succeeded.
     */
    private static function write(string $level, string $message, array $context): void
    {
        try {
            Log::channel('security')->{$level}($message, $context);

            return;
        } catch (\Throwable $e) {
            // Fall through: the primary channel is unavailable.
        }

        try {
            Log::error('SECURITY: '.$message, $context);

            return;
        } catch (\Throwable $e) {
            // Fall through: the main log is unavailable too.
        }

        error_log('[security] '.$message.' '.json_encode($context));
    }
}
