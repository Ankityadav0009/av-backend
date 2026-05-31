<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $menus = Menu::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();
        return response()->json($menus);
    }

    /** Flat list for role-menu mapping (all menus including submenus) */
    public function listAll(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $menus = Menu::orderBy('sort_order')->orderBy('id')->get();
        return response()->json($menus);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:menus,id',
            'label' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:255',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $menu = Menu::create($validated);
        return response()->json($menu, 201);
    }

    public function show(Request $request, Menu $menu): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $menu->load('children');
        return response()->json($menu);
    }

    public function update(Request $request, Menu $menu): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:menus,id',
            'label' => 'string|max:255',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:255',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);
        if (isset($validated['parent_id']) && (int) $validated['parent_id'] === (int) $menu->id) {
            return response()->json(['message' => 'Menu cannot be its own parent'], 422);
        }
        $menu->update($validated);
        return response()->json($menu);
    }

    public function destroy(Request $request, Menu $menu): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($menu->children()->exists()) {
            return response()->json(['message' => 'Delete submenus first'], 422);
        }
        $menu->roles()->detach();
        $menu->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }
}
