<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Adds a few standard browser-protection headers to every page. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), payment=()');
        // Site must not appear in search results yet. Remove this line to allow indexing.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        if ($request->isSecure()) $response->headers->set('Strict-Transport-Security', 'max-age=31536000');

        return $response;
    }
}
