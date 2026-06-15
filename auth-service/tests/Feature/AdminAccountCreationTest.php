<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
        \Illuminate\Support\Facades\Mail::fake();
    }

    private function getUserWithSession($email)
    {
        $user = User::where('email', $email)->first();
        $user->is_password_changed = true;
        $user->save();
        
        $sessionId = (string) \Illuminate\Support\Str::uuid();
        DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [$user, $sessionId];
    }

    public function test_it_admin_can_create_account_for_any_department()
    {
        [$user, $sessionId] = $this->getUserWithSession('admin@example.com'); // IT Admin
        $itDept = Department::where('name', 'IT')->first();
        $role = Role::where('name', 'Manager')->first();

        $response = $this->actingAs($user, 'api')
                         ->withHeader('X-Session-ID', $sessionId)
                         ->postJson('/api/admin/users', [
                             'first_name' => 'New',
                             'last_name' => 'ITUser',
                             'email' => 'new.it@sbsi.com',
                             'role_id' => $role->id,
                             'department_id' => $itDept->id
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['email' => 'new.it@sbsi.com']);
    }

    public function test_sales_marketing_admin_can_create_account_for_sales_marketing_department()
    {
        [$user, $sessionId] = $this->getUserWithSession('sales-marketing-admin@example.com');
        $salesMarketingDept = Department::where('name', 'Sales & Marketing')->first();
        $role = Role::where('name', 'Manager')->first();

        $response = $this->actingAs($user, 'api')
                         ->withHeader('X-Session-ID', $sessionId)
                         ->postJson('/api/admin/users', [
                             'first_name' => 'New',
                             'last_name' => 'SalesMarketingUser',
                             'email' => 'new.sales-marketing@sbsi.com',
                             'role_id' => $role->id,
                             'department_id' => $salesMarketingDept->id
                         ]);

        $response->dump()->assertStatus(201)
                 ->assertJsonFragment(['email' => 'new.sales-marketing@sbsi.com']);
    }

    public function test_sales_marketing_admin_cannot_create_account_for_other_departments()
    {
        [$user, $sessionId] = $this->getUserWithSession('sales-marketing-admin@example.com');
        $itDept = Department::where('name', 'IT')->first();
        $role = Role::where('name', 'Manager')->first();

        $response = $this->actingAs($user, 'api')
                         ->withHeader('X-Session-ID', $sessionId)
                         ->postJson('/api/admin/users', [
                             'first_name' => 'New',
                             'last_name' => 'ITUser',
                             'email' => 'new.it2@sbsi.com',
                             'role_id' => $role->id,
                             'department_id' => $itDept->id
                         ]);

        $response->assertStatus(403)
                 ->assertJsonFragment(['message' => 'You can only create accounts for the Sales & Marketing department.']);
    }

    public function test_sales_marketing_admin_cannot_assign_admin_role()
    {
        [$user, $sessionId] = $this->getUserWithSession('sales-marketing-admin@example.com');
        $salesMarketingDept = Department::where('name', 'Sales & Marketing')->first();
        $role = Role::where('name', 'Admin')->first();

        $response = $this->actingAs($user, 'api')
                         ->withHeader('X-Session-ID', $sessionId)
                         ->postJson('/api/admin/users', [
                             'first_name' => 'New',
                             'last_name' => 'SalesMarketingUser',
                             'email' => 'new.sales-marketing2@sbsi.com',
                             'role_id' => $role->id,
                             'department_id' => $salesMarketingDept->id
                         ]);

        $response->assertStatus(403)
                 ->assertJsonFragment(['message' => 'You are only authorized to assign Manager or Employee roles.']);
    }
}
