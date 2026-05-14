<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\UserProfile;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;
    protected $manageRolesPermission;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup IT Admin Role and Permission
        $itAdminRole = Role::create(['name' => 'IT Admin', 'description' => 'IT Admin Role']);
        $this->manageRolesPermission = Permission::create(['name' => 'Manage Roles', 'slug' => 'manage-roles']);
        
        DB::table('permission_role')->insert([
            'role_id' => $itAdminRole->id,
            'permission_id' => $this->manageRolesPermission->id
        ]);

        $this->adminUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $this->adminUser->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role_id' => $itAdminRole->id
        ]);

        $employeeRole = Role::create(['name' => 'Employee', 'description' => 'Employee Role']);
        $this->regularUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $this->regularUser->id,
            'first_name' => 'Regular',
            'last_name' => 'User',
            'role_id' => $employeeRole->id
        ]);

        // Setup Sessions
        $this->setupSessionFor($this->adminUser);
        $this->setupSessionFor($this->regularUser);
    }

    protected function setupSessionFor($user)
    {
        $sessionId = \Illuminate\Support\Str::uuid()->toString();
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'is_active' => true,
            'last_active_at' => now(),
            'created_at' => now(),
        ]);
        
        $user->withSessionId = $sessionId;
    }

    protected function actingAsWithSession($user)
    {
        return $this->actingAs($user)
                    ->withHeader('X-Session-ID', $user->withSessionId);
    }

    public function test_admin_can_list_roles()
    {
        $response = $this->actingAsWithSession($this->adminUser)
                         ->getJson('/api/admin/roles');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    public function test_non_admin_cannot_list_roles()
    {
        $response = $this->actingAsWithSession($this->regularUser)
                         ->getJson('/api/admin/roles');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_role()
    {
        $response = $this->actingAsWithSession($this->adminUser)
                         ->postJson('/api/admin/roles', [
                             'name' => 'New Role',
                             'description' => 'Description'
                         ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('roles', ['name' => 'New Role']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ROLE_CREATED']);
    }

    public function test_admin_can_update_role()
    {
        $role = Role::create(['name' => 'Old Name']);
        
        $response = $this->actingAsWithSession($this->adminUser)
                         ->putJson("/api/admin/roles/{$role->id}", [
                             'name' => 'Updated Name',
                             'description' => 'New Description'
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('roles', ['name' => 'Updated Name']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ROLE_UPDATED']);
    }

    public function test_admin_cannot_delete_role_with_users()
    {
        $role = Role::where('name', 'Employee')->first();
        
        $response = $this->actingAsWithSession($this->adminUser)
                         ->deleteJson("/api/admin/roles/{$role->id}");

        $response->assertStatus(409)
                 ->assertJsonFragment(['message' => 'Cannot delete role with assigned users.']);
    }

    public function test_admin_can_delete_role_without_users()
    {
        $role = Role::create(['name' => 'Empty Role']);
        
        $response = $this->actingAsWithSession($this->adminUser)
                         ->deleteJson("/api/admin/roles/{$role->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ROLE_DELETED']);
    }

    public function test_admin_can_assign_role_to_user()
    {
        $newRole = Role::create(['name' => 'Manager']);
        $targetUser = $this->regularUser;

        Cache::shouldReceive('forget')
             ->with("permissions:user:{$targetUser->id}")
             ->once();

        $response = $this->actingAsWithSession($this->adminUser)
                         ->patchJson("/api/admin/users/{$targetUser->id}/role", [
                             'role_id' => $newRole->id
                         ]);

        $response->assertStatus(200);
        $this->assertEquals($newRole->id, $targetUser->fresh()->profile->role_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ROLE_ASSIGNED']);
    }

    public function test_admin_can_list_users_for_role()
    {
        $role = Role::where('name', 'Employee')->first();
        
        $response = $this->actingAsWithSession($this->adminUser)
                         ->getJson("/api/admin/roles/{$role->id}/users");

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'current_page', 'last_page']);
    }
}
