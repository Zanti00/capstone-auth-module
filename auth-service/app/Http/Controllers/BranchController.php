<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Display a listing of branches with assigned users count.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Branch::withCount('users');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $branches = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json($branches);
    }

    /**
     * Get simple list of branches for dropdown selectors.
     */
    public function options(): JsonResponse
    {
        $branches = Branch::select(['id', 'name', 'address'])->orderBy('name', 'asc')->get();
        return response()->json($branches);
    }

    /**
     * Store a newly created branch in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name',
            'address' => 'required|string|max:500',
        ], [
            'name.unique' => 'A branch with this name already exists.',
            'name.required' => 'Branch name is required.',
            'address.required' => 'Branch address is required.',
        ]);

        $branch = Branch::create($validated);

        return response()->json([
            'message' => 'Branch created successfully.',
            'branch' => $branch,
        ], 201);
    }

    /**
     * Display the specified branch.
     */
    public function show(int $id): JsonResponse
    {
        $branch = Branch::withCount('users')->findOrFail($id);
        return response()->json($branch);
    }

    /**
     * Update the specified branch in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,' . $branch->id,
            'address' => 'required|string|max:500',
        ], [
            'name.unique' => 'A branch with this name already exists.',
            'name.required' => 'Branch name is required.',
            'address.required' => 'Branch address is required.',
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'Branch updated successfully.',
            'branch' => $branch,
        ]);
    }

    /**
     * Remove the specified branch from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $branch = Branch::withCount('users')->findOrFail($id);

        if ($branch->users_count > 0) {
            return response()->json([
                'message' => "Cannot delete branch '{$branch->name}' because it currently has {$branch->users_count} assigned users. Reassign users first."
            ], 422);
        }

        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully.'
        ]);
    }
}
