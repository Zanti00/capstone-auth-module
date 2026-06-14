<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class InternalVerifyTokenController extends Controller
{
    /**
     * Verify token and return user details with permissions.
     */
    public function __invoke(Request $request)
    {
        $secret = $request->header('X-Internal-Secret');
        $expectedSecret = env('INTERNAL_SERVICE_SECRET');

        if (!$secret || $secret !== $expectedSecret) {
            return response()->json(['valid' => false, 'message' => 'Forbidden.'], 403);
        }

        $token = $request->input('token');
        if (!$token) {
            return response()->json(['valid' => false, 'message' => 'Token missing.'], 422);
        }

        try {
            // Set Bearer Authorization header so auth('api')->user() reads it
            $request->headers->set('Authorization', 'Bearer ' . $token);
            
            $user = auth('api')->user();
            
            if (!$user || !$user->is_active) {
                return response()->json(['valid' => false]);
            }

            $user->loadMissing(['profile.role.permissions', 'profile.department']);
            
            $permissions = $user->profile?->role?->permissions
                ?->pluck('slug')
                ->toArray() ?? [];

            return response()->json([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => trim(($user->profile?->first_name ?? '') . ' ' . ($user->profile?->last_name ?? '')),
                    'role' => $user->profile?->role?->name,
                    'department' => $user->profile?->department?->name,
                    'permissions' => $permissions,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('InternalVerifyToken error', ['message' => $e->getMessage()]);
            return response()->json(['valid' => false]);
        }
    }
}
