<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class InternalUserController extends Controller
{
    private function verifySecret(Request $request): ?JsonResponse
    {
        $secret = $request->header('X-Internal-Secret');
        $expectedSecret = env('INTERNAL_SERVICE_SECRET');

        if (!$secret || $secret !== $expectedSecret) {
            return response()->json(['valid' => false, 'message' => 'Forbidden.'], 403);
        }

        return null;
    }

    /**
     * GET /internal/users/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $forbidden = $this->verifySecret($request);
        if ($forbidden) {
            return $forbidden;
        }

        $user = User::with(['profile.role', 'profile.department'])->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->profile?->first_name ?? '',
            'last_name' => $user->profile?->last_name ?? '',
            'role' => $user->profile?->role?->name,
            'department' => $user->profile?->department?->name,
        ]);
    }

    /**
     * GET /internal/users-by-roles
     */
    public function getUsersByRoles(Request $request): JsonResponse
    {
        $forbidden = $this->verifySecret($request);
        if ($forbidden) {
            return $forbidden;
        }

        $rolesString = $request->query('roles', '');
        $rolesList = array_filter(array_map('trim', explode(',', $rolesString)));

        if (empty($rolesList)) {
            return response()->json(['data' => []]);
        }

        $users = User::where('is_active', true)
            ->whereHas('profile.role', function ($query) use ($rolesList) {
                $query->whereIn('name', $rolesList);
            })
            ->with(['profile.role', 'profile.department'])
            ->get();

        $data = $users->map(fn ($user) => [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->profile?->first_name ?? '',
            'last_name' => $user->profile?->last_name ?? '',
            'role' => $user->profile?->role?->name,
            'department' => $user->profile?->department?->name,
        ])->values()->all();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /internal/users-batch
     */
    public function getUsersBatch(Request $request): JsonResponse
    {
        $forbidden = $this->verifySecret($request);
        if ($forbidden) {
            return $forbidden;
        }

        $idsString = $request->query('ids', '');
        $idsList = array_filter(array_map('intval', explode(',', $idsString)));

        if (empty($idsList)) {
            return response()->json(['data' => []]);
        }

        $users = User::whereIn('id', $idsList)
            ->with(['profile.role', 'profile.department'])
            ->get();

        $data = $users->map(fn ($user) => [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->profile?->first_name ?? '',
            'last_name' => $user->profile?->last_name ?? '',
            'role' => $user->profile?->role?->name,
            'department' => $user->profile?->department?->name,
        ])->values()->all();

        return response()->json(['data' => $data]);
    }
}
