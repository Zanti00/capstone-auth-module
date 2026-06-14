<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
// RolePermissionSeeder.php

    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin',         'description' => 'System wide access'],
            ['name' => 'IT Admin',            'description' => 'IT infrastructure and user management'],
            ['name' => 'Admin',               'description' => 'General administration'],
            ['name' => 'Manager',             'description' => 'Management of specific department'],
            ['name' => 'Sales',               'description' => 'Sales staff access'],
            ['name' => 'Finance',             'description' => 'Finance staff access'],
            ['name' => 'Employee',            'description' => 'Regular staff access'],
            ['name' => 'Finance Manager',     'description' => 'Finance department manager'],
            ['name' => 'Finance Employee',    'description' => 'Finance department employee'],
        ];

        // insertOrIgnore: skips duplicates instead of crashing
        // insertOrIgnore: skips duplicates instead of crashing
        DB::table('roles')->insertOrIgnore($roles);

        $permissions = [
            // Auth Service Internal Permissions
            ['name' => 'View Dashboard',       'slug' => 'view-dashboard',       'system' => 'auth'],
            ['name' => 'Manage Users',          'slug' => 'manage-users',         'system' => 'auth'],
            ['name' => 'Manage Roles',          'slug' => 'manage-roles',         'system' => 'auth'],
            ['name' => 'View Audit Logs',       'slug' => 'view-audit-logs',      'system' => 'auth'],
            ['name' => 'Manage Departments',    'slug' => 'manage-departments',   'system' => 'auth'],

            // CRMS Feature Permissions (legacy / internal)
            ['name' => 'Manage CRMS Roles',    'slug' => 'crms.roles.manage',        'system' => 'crms'],
            ['name' => 'Manage Templates',     'slug' => 'crms.templates.manage',    'system' => 'crms'],
            ['name' => 'Use Templates',        'slug' => 'crms.templates.use',       'system' => 'crms'],
            ['name' => 'OCR Upload',           'slug' => 'crms.ocr.upload',          'system' => 'crms'],
            ['name' => 'OCR Process',          'slug' => 'crms.ocr.process',         'system' => 'crms'],
            ['name' => 'OCR Review',           'slug' => 'crms.ocr.review',          'system' => 'crms'],
            ['name' => 'Generate Draft',       'slug' => 'crms.contracts.generate',  'system' => 'crms'],
            ['name' => 'Run Risk Assessment',  'slug' => 'crms.risk.assess',         'system' => 'crms'],
            ['name' => 'View Risk Highlights', 'slug' => 'crms.risk.view',           'system' => 'crms'],
            ['name' => 'Approve/Override Risk','slug' => 'crms.risk.approve',        'system' => 'crms'],

            // CRMS CRUD Permissions — Contracts (maps to "Contracts" UI category)
            ['name' => 'View Contracts',   'slug' => 'crms.contracts.view',   'system' => 'crms'],
            ['name' => 'Create Contracts', 'slug' => 'crms.contracts.create', 'system' => 'crms'],
            ['name' => 'Edit Contracts',   'slug' => 'crms.contracts.edit',   'system' => 'crms'],
            ['name' => 'Delete Contracts', 'slug' => 'crms.contracts.delete', 'system' => 'crms'],
            ['name' => 'Approve Contracts', 'slug' => 'crms.contracts.approve', 'system' => 'crms'],

            // CRMS CRUD Permissions — User Management (maps to "User Management" UI category)
            ['name' => 'View Users',   'slug' => 'crms.users.view',   'system' => 'crms'],
            ['name' => 'Create Users', 'slug' => 'crms.users.create', 'system' => 'crms'],
            ['name' => 'Edit Users',   'slug' => 'crms.users.edit',   'system' => 'crms'],
            ['name' => 'Delete Users', 'slug' => 'crms.users.delete', 'system' => 'crms'],

            // CRMS CRUD Permissions — Partners (maps to "Business Partners & Suppliers" UI category)
            ['name' => 'View Partners',   'slug' => 'crms.partners.view',   'system' => 'crms'],
            ['name' => 'Create Partners', 'slug' => 'crms.partners.create', 'system' => 'crms'],
            ['name' => 'Edit Partners',   'slug' => 'crms.partners.edit',   'system' => 'crms'],
            ['name' => 'Delete Partners', 'slug' => 'crms.partners.delete', 'system' => 'crms'],


            // PRS Permissions
            ['name' => 'View PRS Dashboard', 'slug' => 'prs.dashboard.view', 'system' => 'prs'],
            ['name' => 'View PRS Activity Log', 'slug' => 'prs.activity-log.view', 'system' => 'prs'],
            ['name' => 'Create PRS Submissions', 'slug' => 'prs.submissions.create', 'system' => 'prs'],
            ['name' => 'Validate PRS Submissions', 'slug' => 'prs.submissions.validate', 'system' => 'prs'],
            ['name' => 'Manage PRS Itineraries', 'slug' => 'prs.itineraries.manage', 'system' => 'prs'],
            ['name' => 'View PRS Leaderboard', 'slug' => 'prs.leaderboard.view', 'system' => 'prs'],
            ['name' => 'View PRS Reports', 'slug' => 'prs.reports.view', 'system' => 'prs'],
            ['name' => 'Manage PRS Users', 'slug' => 'prs.settings.users.manage', 'system' => 'prs'],
            ['name' => 'Manage PRS Departments', 'slug' => 'prs.settings.departments.manage', 'system' => 'prs'],
            ['name' => 'Manage PRS Products', 'slug' => 'prs.settings.products.manage', 'system' => 'prs'],
            ['name' => 'Manage PRS Institutions', 'slug' => 'prs.settings.institutions.manage', 'system' => 'prs'],
            ['name' => 'View PRS Files', 'slug' => 'prs.files.view', 'system' => 'prs'],
            // SERMS Permissions
            ['name' => 'Manage Reimbursements', 'slug' => 'serms.reimbursements.manage', 'system' => 'serms'],
            ['name' => 'Manage Liquidations',   'slug' => 'serms.liquidations.manage',   'system' => 'serms'],
            ['name' => 'Manage Cash Advances',  'slug' => 'serms.cash_advances.manage',  'system' => 'serms'],
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // --- Role Assignment Logic ---

        // 1. IT Admin / Super Admin — full auth management
        $authAdminRoles = \App\Models\Role::whereIn('name', ['Super Admin', 'IT Admin'])->get();
        $authPermissions = \App\Models\Permission::where('system', 'auth')->get();
        foreach ($authAdminRoles as $role) {
            $role->permissions()->syncWithoutDetaching($authPermissions->pluck('id'));
        }

        // 2. CRMS Admin — full access to everything
        $crmsAdmin = \App\Models\Role::where('name', 'Admin')->first();
        if ($crmsAdmin) {
            $adminPerms = \App\Models\Permission::whereIn('slug', [
                'crms.roles.manage',
                'crms.templates.manage',
                'crms.templates.use',
                'crms.ocr.upload',
                'crms.ocr.process',
                'crms.ocr.review',
                'crms.contracts.generate',
                'crms.risk.assess',
                'crms.risk.view',
                'crms.risk.approve',
                // CRUD
                'crms.contracts.view', 'crms.contracts.create', 'crms.contracts.edit', 'crms.contracts.delete', 'crms.contracts.approve',
                'crms.users.view',     'crms.users.create',     'crms.users.edit',     'crms.users.delete',
                'crms.partners.view',  'crms.partners.create',  'crms.partners.edit',  'crms.partners.delete',
                'manage-users',
                'serms.reimbursements.manage', 'serms.liquidations.manage', 'serms.cash_advances.manage',
                'prs.dashboard.view', 'prs.activity-log.view', 'prs.submissions.create', 'prs.submissions.validate',
                'prs.itineraries.manage', 'prs.leaderboard.view', 'prs.reports.view', 'prs.settings.users.manage',
                'prs.settings.departments.manage', 'prs.settings.products.manage', 'prs.settings.institutions.manage', 'prs.files.view',
            ])->get();
            $crmsAdmin->permissions()->syncWithoutDetaching($adminPerms->pluck('id'));
        }

        // 3. CRMS Manager — broad access, cannot delete users or manage roles
        $crmsManager = \App\Models\Role::where('name', 'Manager')->first();
        if ($crmsManager) {
            $managerPerms = \App\Models\Permission::whereIn('slug', [
                'crms.templates.use',
                'crms.ocr.upload',
                'crms.ocr.process',
                'crms.ocr.review',
                'crms.contracts.generate',
                'crms.risk.assess',
                'crms.risk.view',
                'crms.risk.approve',
                // CRUD
                'crms.contracts.view', 'crms.contracts.create', 'crms.contracts.edit', 'crms.contracts.approve',
                'crms.users.view',
                'crms.partners.view',  'crms.partners.create',  'crms.partners.edit',
                'serms.reimbursements.manage', 'serms.liquidations.manage', 'serms.cash_advances.manage',
                'prs.dashboard.view', 'prs.activity-log.view', 'prs.submissions.create', 'prs.submissions.validate',
                'prs.itineraries.manage', 'prs.leaderboard.view', 'prs.reports.view', 'prs.files.view',
            ])->get();
            $crmsManager->permissions()->syncWithoutDetaching($managerPerms->pluck('id'));
        }

        // 3b. Finance Manager — same default access as CRMS Manager, but scoped to Finance department
        $financeManager = \App\Models\Role::where('name', 'Finance Manager')->first();
        if ($financeManager) {
            $managerPerms = \App\Models\Permission::whereIn('slug', [
                'crms.templates.use',
                'crms.ocr.upload',
                'crms.ocr.process',
                'crms.ocr.review',
                'crms.contracts.generate',
                'crms.risk.assess',
                'crms.risk.view',
                'crms.risk.approve',
                // CRUD
                'crms.contracts.view', 'crms.contracts.create', 'crms.contracts.edit', 'crms.contracts.approve',
                'crms.users.view',
                'crms.partners.view',  'crms.partners.create',  'crms.partners.edit',
                'serms.reimbursements.manage', 'serms.liquidations.manage', 'serms.cash_advances.manage',
                'prs.dashboard.view', 'prs.activity-log.view', 'prs.submissions.create', 'prs.submissions.validate',
                'prs.itineraries.manage', 'prs.leaderboard.view', 'prs.reports.view', 'prs.settings.users.manage',
                'prs.settings.departments.manage', 'prs.settings.products.manage', 'prs.settings.institutions.manage', 'prs.files.view',
            ])->get();
            $financeManager->permissions()->syncWithoutDetaching($managerPerms->pluck('id'));
        }


        // PRS role assignments
        $prsAllPerms = \App\Models\Permission::where('system', 'prs')->get();
        $prsBasicPerms = \App\Models\Permission::whereIn('slug', [
            'prs.dashboard.view',
            'prs.activity-log.view',
            'prs.submissions.create',
            'prs.leaderboard.view',
            'prs.files.view',
        ])->get();

        $prsValidationPerms = \App\Models\Permission::whereIn('slug', [
            'prs.dashboard.view',
            'prs.activity-log.view',
            'prs.submissions.validate',
            'prs.itineraries.manage',
            'prs.leaderboard.view',
            'prs.files.view',
        ])->get();

        foreach (\App\Models\Role::whereIn('name', ['Super Admin', 'IT Admin', 'Admin'])->get() as $role) {
            $role->permissions()->syncWithoutDetaching($prsAllPerms->pluck('id'));
        }

        foreach (\App\Models\Role::whereIn('name', ['Manager', 'Finance Manager'])->get() as $role) {
            $role->permissions()->syncWithoutDetaching($prsValidationPerms->pluck('id'));
        }

        foreach (\App\Models\Role::whereIn('name', ['Sales', 'Employee', 'Finance', 'Finance Employee'])->get() as $role) {
            $role->permissions()->syncWithoutDetaching($prsBasicPerms->pluck('id'));
        }

        // 4. CRMS Sales — limited, view-only on most; own-record access enforced by app logic
        $crmsSales = \App\Models\Role::where('name', 'Sales')->first();
        if ($crmsSales) {
            $salesPerms = \App\Models\Permission::whereIn('slug', [
                'crms.templates.use',
                'crms.ocr.upload',
                'crms.ocr.process',
                'crms.ocr.review',
                'crms.contracts.generate',
                'crms.risk.assess',
                'crms.risk.view',
                'crms.risk.approve',
                // CRUD
                'crms.contracts.view',
                'crms.contracts.approve',
                'crms.users.view',
                'crms.partners.view',
            ])->get();
            $crmsSales->permissions()->syncWithoutDetaching($salesPerms->pluck('id'));
        }
    }
}
