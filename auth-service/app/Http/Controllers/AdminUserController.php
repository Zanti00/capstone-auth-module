<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use App\Mail\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $key = 'users:list:' . md5(serialize($request->all()));
        
        try {
            return Cache::remember($key, 300, function() use ($request) {
                return $this->buildUserQuery($request)->paginate($request->get('per_page', 15));
            });
        } catch (\Exception $e) {
            Log::warning('Cache unavailable for users list, querying DB directly', ['error' => $e->getMessage()]);
            return $this->buildUserQuery($request)->paginate($request->get('per_page', 15));
        }
    }

    private function buildUserQuery(Request $request)
    {
        $query = User::with(['profile.role', 'profile.department']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('role_id')) {
            $query->filterByRole($request->role_id);
        }

        if ($request->filled('department_id')) {
            $query->filterByDepartment($request->department_id);
        }

        if ($request->filled('is_active')) {
            $query->filterByStatus($request->is_active);
        }

        return $query;
    }

    public function show($id)
    {
        return User::with(['profile.role', 'profile.department'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        $tempPassword = $this->generateSecurePassword();

        try {
            DB::beginTransaction();

            $user = User::create([
                'email' => $validated['email'],
                'is_active' => true,
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'role_id' => $validated['role_id'],
                'department_id' => $validated['department_id'],
            ]);

            DB::table('user_credentials')->insert([
                'user_id' => $user->id,
                'password_hash' => Hash::make($tempPassword),
                'must_change_password' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('audit_logs')->insert([
                'actor_id' => $request->user()->id ?? null,
                'action' => 'ACCOUNT_CREATED',
                'description' => 'Admin created account for ' . $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'action_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Targeted cache invalidation instead of Cache::flush()
            try {
                Cache::forget('roles:list');
                Cache::forget('roles:all');
                Cache::forget('departments:all');
            } catch (\Exception $e) {
                Log::warning('Failed to invalidate cache after user creation', ['error' => $e->getMessage()]);
            }

            Mail::to($user->email)->queue(new WelcomeEmail($user->email, $tempPassword));

            return response()->json([
                'message' => 'User created successfully.',
                'user' => $user->load(['profile.role', 'profile.department'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create user.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getRoles()
    {
        try {
            $roles = Cache::remember('roles:all', 3600, function() {
                return \App\Models\Role::all()->toArray(); // toArray()
            });
            return response()->json($roles);
        } catch (\Exception $e) {
            return response()->json(\App\Models\Role::all());
        }
    }

    public function getDepartments()
    {
        try {
            $departments = Cache::remember('departments:all', 3600, function() {
                return \App\Models\Department::all()->toArray(); // toArray()
            });
            return response()->json($departments);
        } catch (\Exception $e) {
            return response()->json(\App\Models\Department::all());
        }
    }

    public function getUserPermissions($id)
    {
        // For security, only allow the user to see their own permissions or admins to see anyone's
        if (auth()->id() != $id && Gate::denies('manage-users')) {
            abort(403, 'Unauthorized.');
        }

        try {
            return Cache::store('database')->remember("permissions:user:{$id}", 300, function () use ($id) {
                return $this->fetchUserPermissions($id);
            });
        } catch (\Exception $e) {
            Log::warning('Cache unavailable for user permissions, querying DB directly', ['error' => $e->getMessage()]);
            return $this->fetchUserPermissions($id);
        }
    }

    private function fetchUserPermissions($id)
    {
        $user = User::with('profile.role.permissions')->findOrFail($id);
        
        if (!$user->profile || !$user->profile->role) {
            return [];
        }

        return $user->profile->role->permissions->pluck('slug')->toArray();
    }

    private function generateSecurePassword()
    {
        return Str::random(8) . 'A1!'; // Simple way to meet policy (min 8, 1 uppercase, 1 number, 1 special char)
    }
}
