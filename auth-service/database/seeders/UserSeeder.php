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
            ['username' => 'admin'],
            ['email' => 'admin@example.com', 'is_active' => true]
        );

        \App\Models\UserCredential::firstOrCreate(
            ['user_id' => $user->id],
            ['password_hash' => \Illuminate\Support\Facades\Hash::make('password', ['rounds' => 12])]
        );
    }
}
