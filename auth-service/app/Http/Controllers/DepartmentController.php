<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class DepartmentController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-departments');
        return response()->json(Department::withCount('users')->get());
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-departments');

        $validated = $request->validate([
            'name' => 'required|string|unique:departments,name|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $department = Department::create($validated);

        try { Cache::forget('departments:all'); } catch (\Exception $e) {}

        $this->logAudit($request, 'DEPARTMENT_CREATED', "Created department: {$department->name}");

        return response()->json($department, 201);
    }

    public function show($id)
    {
        Gate::authorize('manage-departments');
        return response()->json(Department::withCount('users')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('manage-departments');

        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $department->update($validated);

        try { Cache::forget('departments:all'); } catch (\Exception $e) {}

        $this->logAudit($request, 'DEPARTMENT_UPDATED', "Updated department: {$department->name}");

        return response()->json($department);
    }

    public function destroy(Request $request, $id)
    {
        Gate::authorize('manage-departments');

        $department = Department::withCount('users')->findOrFail($id);

        if ($department->users_count > 0) {
            return response()->json([
                'message' => 'Cannot delete department with assigned users.',
                'user_count' => $department->users_count
            ], 409);
        }

        $name = $department->name;
        $department->delete();

        try { Cache::forget('departments:all'); } catch (\Exception $e) {}

        $this->logAudit($request, 'DEPARTMENT_DELETED', "Deleted department: {$name}");

        return response()->json(['message' => 'Department deleted successfully.']);
    }

    public function users($id)
    {
        Gate::authorize('manage-departments');
        $department = Department::findOrFail($id);

        $users = User::whereHas('profile', function ($query) use ($id) {
            $query->where('department_id', $id);
        })->with(['profile.role'])->paginate(15);

        return response()->json($users);
    }

    public function assignDepartment(Request $request, $userId)
    {
        Gate::authorize('manage-departments');

        $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $user = User::findOrFail($userId);
        $department = Department::findOrFail($request->department_id);

        try {
            DB::beginTransaction();

            $profile = $user->profile;
            $oldDeptName = $profile->department ? $profile->department->name : 'None';

            $profile->update(['department_id' => $department->id]);

            $this->logAudit($request, 'USER_DEPARTMENT_CHANGED', "Assigned department {$department->name} to user {$user->email} (was {$oldDeptName})");

            DB::commit();

            return response()->json([
                'message' => 'Department assigned successfully.',
                'user' => $user->load('profile.department')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to assign department.', 'error' => $e->getMessage()], 500);
        }
    }

    private function logAudit(Request $request, $action, $description)
    {
        DB::table('audit_logs')->insert([
            'actor_id' => $request->user()->id ?? null,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'action_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
