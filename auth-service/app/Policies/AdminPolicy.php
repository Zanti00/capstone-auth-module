<?php

namespace App\Policies;

use App\Models\User;

class AdminPolicy
{
    /**
     * Determine whether the user can manage users.
     */
    public function manageUsers(User $user): bool
    {
        if (!$user->profile || !$user->profile->role_id) {
            return false;
        }
        
        $hasPermission = \Illuminate\Support\Facades\DB::table('permission_role')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->where('permission_role.role_id', $user->profile->role_id)
            ->where('permissions.slug', 'manage_users')
            ->exists();

        return $hasPermission;
    }
}
