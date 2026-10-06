<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Authenticate user via stateful session or issue Sanctum PlainTextToken.
     *
     * Source: https://laravel.com/docs/11.x/sanctum#spa-authentication
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Issue Sanctum Personal Access Token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Store the Sanctum token in an HttpOnly cookie
        $cookie = cookie(
            name: 'auth_token',
            value: $token,
            minutes: 60 * 24 * 7,
            path: '/',
            domain: null,
            secure: (bool) env('SESSION_SECURE_COOKIE', false),
            httpOnly: true,
            raw: false,
            sameSite: 'lax'
        );

        $responseData = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
        ];

        return response()->json($responseData, 200)->withCookie($cookie);
    }

    /**
     * Revoke access token and clear the auth cookie.
     *
     * Source: https://laravel.com/docs/11.x/sanctum#revoking-tokens
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->user() && method_exists($request->user(), 'currentAccessToken') && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(null, 204)->withoutCookie('auth_token');
    }

    /**
     * Get the authenticated user info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ], 200);
    }
}
