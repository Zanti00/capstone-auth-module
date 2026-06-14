<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disableCookieEncryption();
        $this->seed();
    }

    public function test_user_can_update_profile_including_middle_name(): void
    {
        // Mark password as changed in DB before login
        $user = User::where('email', 'sales@example.com')->first();
        $this->assertNotNull($user);
        $user->is_password_changed = true;
        $user->save();

        // Login to get real session cookies & session ID
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'sales@example.com',
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200);

        $cookies = $loginResponse->headers->getCookies();
        $accessToken = null;
        $sessionId = null;

        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'access_token') {
                $accessToken = $cookie->getValue();
            }
            if ($cookie->getName() === 'session_id') {
                $sessionId = $cookie->getValue();
            }
        }

        $this->assertNotNull($accessToken);
        $this->assertNotNull($sessionId);

        // Make update request with auth credentials
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'X-Session-ID' => $sessionId,
        ])->putJson('/api/me/profile', [
            'first_name' => 'John',
            'middle_name' => 'Fitzgerald',
            'last_name' => 'Kennedy',
            'phone' => '1234567890',
            'email' => 'sales@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.first_name', 'John');
        $response->assertJsonPath('user.middle_name', 'Fitzgerald');
        $response->assertJsonPath('user.last_name', 'Kennedy');
        $response->assertJsonPath('user.phone', '1234567890');

        // Check DB
        $user = User::where('email', 'sales@example.com')->first();
        $profile = UserProfile::where('user_id', $user->id)->first();
        $this->assertEquals('John', $profile->first_name);
        $this->assertEquals('Fitzgerald', $profile->middle_name);
        $this->assertEquals('Kennedy', $profile->last_name);
    }
}
