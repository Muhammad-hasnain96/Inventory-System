<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all active branches
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $branches = Branch::active()->get();

            return response()->json([
                'success' => true,
                'data' => $branches,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single branch
     */
    public function show(Branch $branch): JsonResponse
    {
        $this->authorize('view', $branch);

        return response()->json([
            'success' => true,
            'data' => $branch,
        ]);
    }

    /**
     * Create a new branch
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Branch::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:branches,code|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'status' => 'nullable|in:active,inactive',
        ]);

        try {
            $branch = Branch::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Branch created successfully',
                'data' => $branch,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a branch
     */
    public function update(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('update', $branch);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:branches,code,' . $branch->id . '|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'status' => 'nullable|in:active,inactive',
        ]);

        try {
            $branch->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Branch updated successfully',
                'data' => $branch,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a branch
     */
    public function destroy(Branch $branch): JsonResponse
    {
        $this->authorize('delete', $branch);

        try {
            $branch->delete();

            return response()->json([
                'success' => true,
                'message' => 'Branch deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get branch statistics
     */
    public function stats(Branch $branch): JsonResponse
    {
        $this->authorize('view', $branch);

        try {
            $stats = [
                'total_products' => $branch->inventories()->count(),
                'total_inventory_value' => $branch->inventories()->sum('quantity'),
                'total_users' => $branch->users()->count(),
                'total_orders' => $branch->orders()->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
