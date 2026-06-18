<?php
 
namespace Tests\Feature;
 
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
 
class CmsAuthorizationTest extends TestCase
{
    use RefreshDatabase;
 
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
    }
 
    /**
     * Test that CMS permissions are correctly seeded and tagged.
     */
    public function test_cms_permissions_are_seeded()
    {
        $this->assertDatabaseHas('permissions', [
            'slug' => 'cms.roles.manage',
            'system' => 'cms'
        ]);
 
        $this->assertDatabaseHas('permissions', [
            'slug' => 'cms.ocr.upload',
            'system' => 'cms'
        ]);
    }
 
    /**
     * Test that Admin role has CMS admin permissions.
     */
    public function test_admin_has_correct_cms_permissions()
    {
        $adminRole = Role::where('name', 'Admin')->first();
        
        $this->assertTrue($adminRole->permissions->contains('slug', 'cms.roles.manage'));
        $this->assertTrue($adminRole->permissions->contains('slug', 'cms.templates.manage'));
        
        // Admin should have OCR Upload permission in this setup
        $this->assertTrue($adminRole->permissions->contains('slug', 'cms.ocr.upload'));
    }
 
    /**
     * Test that Sales role has the full OCR/Risk suite.
     */
    public function test_sales_has_ocr_and_risk_permissions()
    {
        $salesRole = Role::where('name', 'Sales')->first();
        
        $this->assertTrue($salesRole->permissions->contains('slug', 'cms.ocr.upload'));
        $this->assertTrue($salesRole->permissions->contains('slug', 'cms.risk.assess'));
        $this->assertTrue($salesRole->permissions->contains('slug', 'cms.risk.approve'));
    }
 
    /**
     * Test the API endpoint filtering by system.
     */
    public function test_api_returns_filtered_permissions()
    {
        // Find or create a sales user
        $user = User::where('email', 'sales@example.com')->first();
        
        // Create a valid session to satisfy CheckActiveSession middleware
        $sessionId = (string) \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Testing',
            'last_active_at' => now(),
            'is_active' => true,
            'created_at' => now(),
        ]);
 
        $user->is_password_changed = true;
        $user->save();
 
        $response = $this->actingAs($user, 'api')
                         ->withHeader('X-Session-ID', $sessionId)
                         ->getJson('/api/me/permissions?system=cms');
 
        $response->assertStatus(200)
                 ->assertJsonFragment(['permissions' => [
                     'cms.templates.use',
                     'cms.ocr.upload',
                     'cms.ocr.process',
                     'cms.ocr.review',
                     'cms.contracts.generate',
                     'cms.risk.assess',
                     'cms.risk.view',
                     'cms.risk.approve',
                     'cms.contracts.view',
                     'cms.contracts.approve',
                     'cms.users.view',
                     'cms.partners.view'
                 ]]);
                 
        // Ensure auth-service internal permissions are NOT included in CMS filtered request
        $response->assertJsonMissing(['view-dashboard']);
    }
}
