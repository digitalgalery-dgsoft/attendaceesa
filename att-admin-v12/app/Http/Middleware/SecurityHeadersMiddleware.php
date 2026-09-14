<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and append industry-standard security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Anti-Clickjacking: Only allow framing by the same origin
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Anti-MIME-Sniffing: Force browser to adhere to declared Content-Type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Legacy XSS Protection for older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy to safeguard sensitive URLs in referrer headers
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy: Restrict sensor access to trusted origins
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

        // Strict-Transport-Security (HSTS) when accessed via HTTPS
        if ($request->isSecure() || $request->header('X-Forwarded-Proto') === 'https') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
