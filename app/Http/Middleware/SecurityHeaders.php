<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a unique nonce for this request.
        // CSP Level 3: when a nonce is present, browsers ignore 'unsafe-inline' for scripts.
        // Keeping 'unsafe-inline' here as a fallback for older browsers that don't support nonces.
        $nonce = base64_encode(random_bytes(16));

        // Share the nonce with all Blade views so <script nonce="{{ $cspNonce }}"> works.
        View::share('cspNonce', $nonce);

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Content Security Policy
        $localSources = app()->environment('local') ? ' http://localhost:5173 ws://localhost:5173 http://localhost:4000 ws://localhost:4000' : '';
        $localImages = app()->environment('local') ? ' http://localhost:5173 http://localhost:4000' : '';
        
        // Midtrans domains for payment gateway
        $midtransSources = ' https://app.sandbox.midtrans.com https://app.midtrans.com';
        
        // 'nonce-{value}' allows the specific inline scripts tagged with this nonce.
        // 'unsafe-inline' is kept as a fallback for browsers without nonce support (CSP Level 2).
        // In CSP Level 3 browsers, 'unsafe-inline' is ignored when a nonce is present.
        // 'unsafe-eval' is required by jQuery (used for $.globalEval which internally calls eval())
        // and by Chart.js / ECharts for certain dynamic renderers. If these libraries are replaced,
        // 'unsafe-eval' can be removed. See ARCHITECTURE_ASSESSMENT.md section 3.6.
        $response->headers->set('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com{$midtransSources}{$localSources}; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://rsms.me{$localSources}; " .
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest data: https://rsms.me; " .
            "img-src 'self' data: https:{$localImages}; " .
            "frame-src 'self'{$midtransSources}; " .
            "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com{$midtransSources}{$localSources};"
        );

        return $response;
    }
}
