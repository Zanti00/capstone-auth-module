<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_update_profile_including_middle_name(): void
    {
        // Get seeded user
        $user = User::with('profile')->where('email', 'sales@example.com')->first();
        $this->assertNotNull($user);

        // Make update request
        $response = $this->actingAs($user, 'api')
            ->putJson('/api/me/profile', [
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
        $profile = UserProfile::where('user_id', $user->id)->first();
        $this->assertEquals('John', $profile->first_name);
        $this->assertEquals('Fitzgerald', $profile->middle_name);
        $this->assertEquals('Kennedy', $profile->last_name);
    }
}
