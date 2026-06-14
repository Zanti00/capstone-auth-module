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
            'crms.templates.use',
            'crms.ocr.upload',
            'crms.ocr.process',
            'crms.ocr.review',
            'crms.contracts.generate',
            'crms.risk.assess',
            'crms.risk.view',
            'crms.risk.approve',
            'crms.contracts.view',
            'crms.contracts.create',
            'crms.contracts.edit',
            'crms.users.view',
            'crms.partners.view',
            'crms.partners.create',
            'crms.partners.edit',
        ])->pluck('id');
        $financeManager->permissions()->syncWithoutDetaching($managerPerms);
    }
};
