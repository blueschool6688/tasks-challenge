<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithCookieToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasCookie('auth_token') && ! $request->hasHeader('Authorization')) {
            $token = $request->cookie('auth_token');
            if (is_string($token) && ! empty($token)) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
