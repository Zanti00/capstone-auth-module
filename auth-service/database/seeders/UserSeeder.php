<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
// UserSeeder.php

    public function run(): void
    {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'admin@example.com', 'is_active' => true]
        );

        \App\Models\UserCredential::firstOrCreate(
            ['user_id' => $user->id],
            ['password_hash' => \Illuminate\Support\Facades\Hash::make('password', ['rounds' => 12])]
        );

        $itAdminRole = \App\Models\Role::where('name', 'IT Admin')->first();
        if ($itAdminRole) {
            \App\Models\UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => 'System',
                    'last_name' => 'Administrator',
                    'role_id' => $itAdminRole->id,
                    'department_id' => \App\Models\Department::first()?->id ?? 1
                ]
            );
        }
    }
}
