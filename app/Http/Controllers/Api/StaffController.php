<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(): JsonResponse
    {
        $staff = Staff::orderBy('name')->get();
        return response()->json($staff);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:staff,email',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:50',
            'designation' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'join_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $staff = Staff::create($validated);
        return response()->json($staff, 201);
    }

    public function show(Staff $staff): JsonResponse
    {
        return response()->json($staff);
    }

    public function update(Request $request, Staff $staff): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:staff,email,' . $staff->id,
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:50',
            'designation' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'join_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $staff->update($validated);
        return response()->json($staff);
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $staff->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }
}
