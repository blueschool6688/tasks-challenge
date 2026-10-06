<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SpaAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'spa-user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
    }

    public function test_login_attaches_auth_token_cookie(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'spa-user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertCookie('auth_token')
            ->assertJsonPath('user.email', 'spa-user@example.com');
    }

    public function test_request_with_cookie_token_authenticates_on_me_endpoint(): void
    {
        $token = $this->user->createToken('test-cookie-token')->plainTextToken;

        $response = $this->withCredentials()
            ->withUnencryptedCookie('auth_token', $token)
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('email', 'spa-user@example.com');
    }

    public function test_logout_clears_auth_token_cookie(): void
    {
        $token = $this->user->createToken('test-logout-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/logout');

        $response->assertStatus(204)
            ->assertCookieExpired('auth_token');
    }

    public function test_bearer_token_still_authenticates_stateless_api_requests(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('email', 'spa-user@example.com');
    }
}
