<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $roles = Role::orderBy('name')->get();
        return response()->json($roles);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:roles,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $validated['is_active'] ?? true;
        $role = Role::create($validated);
        return response()->json($role, 201);
    }

    public function show(Request $request, Role $role): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json($role);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:roles,code,' . $role->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $role->update($validated);
        return response()->json($role);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($role->users()->exists()) {
            return response()->json(['message' => 'Cannot delete role that is assigned to users'], 422);
        }
        $role->menus()->detach();
        $role->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }

    /** Get menu IDs assigned to a role */
    public function menus(Request $request, Role $role): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $menuIds = $role->menus()->pluck('menus.id');
        return response()->json(['role_id' => $role->id, 'menu_ids' => $menuIds]);
    }

    /** Sync menus for a role (role-menu mapping) */
    public function syncMenus(Request $request, Role $role): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'menu_ids' => 'required|array',
            'menu_ids.*' => 'integer|exists:menus,id',
        ]);
        $role->menus()->sync($validated['menu_ids']);
        return response()->json(['message' => 'Updated', 'menu_ids' => $role->menus()->pluck('menus.id')]);
    }
}
