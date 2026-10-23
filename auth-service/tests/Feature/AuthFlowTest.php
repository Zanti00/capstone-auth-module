<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        UserCredential::create([
            'user_id' => $this->user->id,
            'password_hash' => Hash::make('Password123!', ['rounds' => 12]),
        ]);
    }

    /**
     * Test successful login.
     */
    public function test_login_success()
    {
        $this->user->is_password_changed = true;
        $this->user->save();

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user',
            'permissions'
        ]);
        $response->assertCookie('access_token');
        $response->assertCookie('session_id');
    }

    public function test_first_login_does_not_issue_access_or_refresh_tokens(): void
    {
        $this->user->is_password_changed = false;
        $this->user->is_active = false;
        $this->user->save();

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'password_change_required' => true,
            ])
            ->assertCookie('session_id')
            ->assertCookieExpired('access_token')
            ->assertCookieExpired('refresh_token');
    }

    /**
     * Test login with wrong password.
     */
    public function test_login_wrong_password()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test account locking after multiple failed attempts.
     */
    public function test_login_account_locking()
    {
        $email = 'test@example.com';

        // Clear rate limiter for test consistency
        RateLimiter::clear('login:' . $email . '|127.0.0.1');

        // Fail 3 times
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'WrongPassword',
            ]);
        }

        // 4th attempt should be locked
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'Password123!', // Correct password but should be locked
        ]);

        $response->assertStatus(429);
        $response->assertJsonFragment([
            'message' => 'Too many login attempts. Please try again in 15 minutes.'
        ]);
    }

    /**
     * Test logout.
     */
    public function test_logout_revokes_token()
    {
        $this->user->is_password_changed = true;
        $this->user->save();

        // Login first
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->getCookie('access_token', false)->getValue();
        $sessionId = $loginResponse->getCookie('session_id', false)->getValue();

        // Logout with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Session-ID' => $sessionId,
        ])->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Successfully logged out.']);

        // Verify token no longer works
        $userResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $userResponse->assertStatus(401);
    }

    public function test_logout_clears_cookies_even_without_valid_auth_context()
    {
        $response = $this->withCookie('refresh_token', 'stale-refresh-token')
            ->withCookie('session_id', 'stale-session-id')
            ->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Successfully logged out.']);
        $response->assertCookieExpired('access_token');
        $response->assertCookieExpired('refresh_token');
        $response->assertCookieExpired('session_id');
        $response->assertCookieExpired('is_authenticated');
    }

    public function test_first_login_user_can_change_password_with_session_only(): void
    {
        $this->user->is_password_changed = false;
        $this->user->is_active = false;
        $this->user->save();

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $sessionId = $loginResponse->getCookie('session_id', false)->getValue();

        $response = $this->withHeader('X-Session-ID', $sessionId)
            ->postJson('/api/me/password', [
                'current_password' => 'Password123!',
                'new_password' => 'UpdatedPassword123!',
                'new_password_confirmation' => 'UpdatedPassword123!',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password has been successfully updated.']);

        $this->user->refresh();
        $this->assertTrue($this->user->is_password_changed);
        $this->assertEquals(1, $this->user->is_active);
        $this->assertTrue(Hash::check('UpdatedPassword123!', $this->user->credentials->fresh()->password_hash));
    }
}
