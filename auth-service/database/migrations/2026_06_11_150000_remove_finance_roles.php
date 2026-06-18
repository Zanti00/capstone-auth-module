<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\UserProfile;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $managerRole = Role::where('name', 'Manager')->first();
        $employeeRole = Role::where('name', 'Employee')->first();

        $financeManagerRole = Role::where('name', 'Finance Manager')->first();
        $financeEmployeeRole = Role::where('name', 'Finance Employee')->first();

        if ($financeManagerRole && $managerRole) {
            UserProfile::where('role_id', $financeManagerRole->id)
                ->update(['role_id' => $managerRole->id]);
        }

        if ($financeEmployeeRole && $employeeRole) {
            UserProfile::where('role_id', $financeEmployeeRole->id)
                ->update(['role_id' => $employeeRole->id]);
        }

        // Delete the roles (cascades automatically to role_permission pivot table)
        Role::whereIn('name', ['Finance Manager', 'Finance Employee'])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create the roles
        $financeManager = Role::firstOrCreate(
            ['name' => 'Finance Manager'],
            ['description' => 'Finance department manager']
        );

        $financeEmployee = Role::firstOrCreate(
            ['name' => 'Finance Employee'],
            ['description' => 'Finance department employee']
        );

        // Sync manager permissions back to Finance Manager
        $managerPerms = DB::table('permissions')->whereIn('slug', [
            'cms.templates.use',
            'cms.ocr.upload',
            'cms.ocr.process',
            'cms.ocr.review',
            'cms.contracts.generate',
            'cms.risk.assess',
            'cms.risk.view',
            'cms.risk.approve',
            'cms.contracts.view',
            'cms.contracts.create',
            'cms.contracts.edit',
            'cms.users.view',
            'cms.partners.view',
            'cms.partners.create',
            'cms.partners.edit',
        ])->pluck('id');
        $financeManager->permissions()->syncWithoutDetaching($managerPerms);
    }
};
