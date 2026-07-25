<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CaptureImpersonation
{
    /**
     * Capture impersonation state from an encrypted URL parameter and inject it
     * into the tenant's session.
     *
     * The super admin controller encrypts the impersonation payload and appends
     * it as ?impersonation_token=... to the redirect URL.  This middleware
     * decrypts the token, writes the impersonation metadata into the session,
     * then logs in as the target user via the standard guard login() path
     * before redirecting to the clean URL.
     *
     * login() calls updateSession() which calls migrate(true), rotating the
     * session ID.  This is safe because StartSession commits the session
     * AFTER all middleware runs, so it picks up the migrated ID and sets
     * the correct cookie on the redirect response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('impersonation_token');

        if ($token && is_string($token)) {
            try {
                $data = decrypt($token);

                $user = Auth::guard('web')->getProvider()->retrieveById(
                    $data['impersonating_user_id']
                );

                if ($user) {
                    // Write impersonation metadata into the session
                    // StartSession already loaded (same object, same ID).
                    session([
                        'impersonating_tenant_id'   => $data['impersonating_tenant_id'],
                        'impersonating_tenant_name' => $data['impersonating_tenant_name'],
                        'impersonating_user_name'   => $data['impersonating_user_name'],
                        'superadmin_return_url'     => $data['superadmin_return_url'],
                    ]);

                    // Log in as the tenant user via the standard guard login path.
                    // This calls updateSession() which stores the auth key AND
                    // calls migrate(true) to rotate the session ID, ensuring
                    // StartSession's cookie handler picks up the new ID.
                    Auth::guard('web')->login($user);

                    // Redirect to the clean URL (no token) to avoid replay.
                    // StartSession will commit the session and set the cookie
                    // on this response automatically.
                    return redirect()->to($request->url());
                }
            } catch (\Throwable $e) {
                // Invalid / tampered token — ignore and continue normally
            }
        }

        return $next($request);
    }
}
