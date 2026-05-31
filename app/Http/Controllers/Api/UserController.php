<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $users = User::with('roleRelation')->orderBy('name')->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role ?? 'receptionist',
            'role_id' => $u->role_id,
            'created_at' => $u->created_at?->toISOString(),
        ]);
        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|max:50',
            'role_id' => 'nullable|integer|exists:roles,id',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        if (empty($validated['role_id']) && !empty($validated['role'])) {
            $role = \App\Models\Role::where('code', $validated['role'])->first();
            if ($role) {
                $validated['role_id'] = $role->id;
            }
        }
        if (empty($validated['role_id'])) {
            $validated['role_id'] = \App\Models\Role::where('code', 'receptionist')->value('id');
        }
        unset($validated['role']);
        $user = User::create($validated);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_id' => $user->role_id,
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? 'receptionist',
            'role_id' => $user->role_id,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'nullable|string|max:50',
            'role_id' => 'nullable|integer|exists:roles,id',
        ]);
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        if (array_key_exists('role', $validated) && empty($validated['role_id'])) {
            $role = \App\Models\Role::where('code', $validated['role'])->first();
            if ($role) {
                $validated['role_id'] = $role->id;
            }
            unset($validated['role']);
        }
        $user->update($validated);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_id' => $user->role_id,
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 422);
        }
        $user->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }
}
