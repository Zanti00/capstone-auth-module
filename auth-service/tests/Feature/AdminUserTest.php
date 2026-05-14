<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\WelcomeEmail;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    //public function test_debug_permissions()
    //{
    //    dd(\App\Models\Permission::all()->toArray());
    //}
    private function getAdminTokens()
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            ['email' => 'admin@example.com', 'is_active' => true]
        );

        $role = Role::firstOrCreate(['name' => 'IT Admin']); // always safe

        // Use firstOrCreate — won't blow up if the seeder already inserted it
        $permission = \App\Models\Permission::firstOrCreate(
            ['slug' => 'manage_users'],
            ['name' => 'Manage Users']
        );

        // Only attach if not already attached
        if (!$role->permissions()->where('permissions.id', $permission->id)->exists()) {
            $role->permissions()->attach($permission);
        }

        UserProfile::updateOrCreate(
            ['user_id' => $admin->id],
            ['role_id' => $role->id]
        );

        $accessToken = $admin->createToken('auth_token')->plainTextToken;
        $sessionId   = (string) Str::uuid();

        DB::table('user_sessions')->insert([
            'user_id'        => $admin->id,
            'session_id'     => $sessionId,
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'TestAgent',
            'last_active_at' => now(),
            'is_active'      => true,
            'created_at'     => now(),
        ]);

        return ['access_token' => $accessToken, 'session_id' => $sessionId, 'user' => $admin];
    }


    public function test_admin_can_create_user()
    {
        Mail::fake();
        $tokens = $this->getAdminTokens();

        $role = Role::firstOrCreate(['name' => 'Employee']);
        $dept = Department::firstOrCreate(['name' => 'IT']);

        $response = $this->call('POST', '/api/admin/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'role_id' => $role->id,
            'department_id' => $dept->id,
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);
        $admin = $tokens['user']->fresh(['profile.role']);
        dump([
            'user_id'    => $admin->id,
            'profile'    => $admin->profile?->toArray(),
            'role_id'    => $admin->profile?->role_id,
            'perms'      => $admin->profile?->role?->permissions?->pluck('slug'),
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['username' => 'johndoe']);
        $this->assertDatabaseHas('user_profiles', ['first_name' => 'John']);
        
        $user = User::where('username', 'johndoe')->first();
        $this->assertDatabaseHas('user_credentials', [
            'user_id' => $user->id,
            'must_change_password' => 1
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ACCOUNT_CREATED',
            'actor_id' => $tokens['user']->id
        ]);

        Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_admin_cannot_create_duplicate_username()
    {
        $tokens = $this->getAdminTokens();
        User::create(['username' => 'existing', 'email' => 'ex@example.com', 'is_active' => true]);

        $role = Role::firstOrCreate(['name' => 'Employee']);
        $dept = Department::firstOrCreate(['name' => 'IT']);

        $response = $this->call('POST', '/api/admin/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john2@example.com',
            'username' => 'existing',
            'role_id' => $role->id,
            'department_id' => $dept->id,
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tokens['access_token'],
            'HTTP_X_SESSION_ID' => $tokens['session_id']
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['username']);
    }

    public function test_unauthorized_user_cannot_access()
    {
        // Normal user without IT Admin role
        $user = User::create(['username' => 'normal', 'email' => 'norm@example.com', 'is_active' => true]);
        UserProfile::create(['user_id' => $user->id]);

        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $sessionId = (string) Str::uuid();

        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $response = $this->call('GET', '/api/admin/users', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
            'HTTP_X_SESSION_ID' => $sessionId
        ]);

        $response->assertStatus(403);
    }
}
