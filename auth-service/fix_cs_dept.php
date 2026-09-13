<?php
// Fix cs@example.com department to Customer Service
// Run: docker exec auth-service php /var/www/html/fix_cs_dept.php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create Customer Service department if it doesn't exist
$existing = DB::table('departments')->where('name', 'Customer Service')->first();
if (!$existing) {
    DB::table('departments')->insert([
        'name' => 'Customer Service',
        'description' => 'Customer Service department',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $deptId = DB::table('departments')->where('name', 'Customer Service')->value('id');
    echo "Created Customer Service dept with id=$deptId\n";
} else {
    $deptId = $existing->id;
    echo "Customer Service dept already exists with id=$deptId\n";
}

// Update cs@example.com user profile
$updated = DB::table('user_profiles')->where('user_id', 74)->update(['department_id' => $deptId]);
echo "Updated user_profiles rows: $updated\n";
echo "Done!\n";
