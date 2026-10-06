<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithCookieToken
{
    /**
     * Handle an incoming request.
     *
     * Validates CSRF mitigation headers when authenticating via HttpOnly cookie
     * and bridges the cookie token to the Authorization header for Sanctum.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasCookie('auth_token') && ! $request->hasHeader('Authorization')) {
            // CSRF protection: State-changing requests using cookie authentication
            // must provide a custom header (e.g. X-Requested-With or X-XSRF-TOKEN)
            // which cannot be sent by cross-origin form submissions without CORS consent.
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $hasValidCsrfHeader = $request->header('X-Requested-With') === 'XMLHttpRequest'
                    || $request->hasHeader('X-XSRF-TOKEN');

                if (! $hasValidCsrfHeader) {
                    return response()->json([
                        'message' => 'CSRF verification failed: Missing required request header.',
                    ], 403);
                }
            }

            $token = $request->cookie('auth_token');
            if (is_string($token) && ! empty($token)) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
