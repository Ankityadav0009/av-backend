<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function index(): JsonResponse
    {
        $floors = Floor::orderBy('floor_number')->get();
        return response()->json($floors);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'floor_number' => 'required|integer|min:0',
            'floor_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $floor = Floor::create($validated);
        return response()->json($floor, 201);
    }

    public function show(Floor $floor): JsonResponse
    {
        return response()->json($floor);
    }

    public function update(Request $request, Floor $floor): JsonResponse
    {
        $validated = $request->validate([
            'floor_number' => 'integer|min:0',
            'floor_name' => 'string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $floor->update($validated);
        return response()->json($floor);
    }

    public function destroy(Floor $floor): JsonResponse
    {
        $floor->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }
}
