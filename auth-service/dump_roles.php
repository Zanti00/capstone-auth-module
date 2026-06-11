<?php
try {
    require 'vendor/autoload.php';
    $app = require 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

    $user = \App\Models\User::where('email', 'finance-admin@example.com')->first();
    if (!$user) {
        echo "User not found!\n";
    } else {
        echo "User: " . $user->email . "\n";
        echo "Role: " . ($user->profile?->role?->name ?? 'None') . "\n";
        echo "Department: " . ($user->profile?->department?->name ?? 'None') . "\n";
        $permissions = $user->profile?->role?->permissions?->pluck('slug')->toArray() ?? [];
        echo "Permissions: " . implode(',', $permissions) . "\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
