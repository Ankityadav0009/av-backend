<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $roomTypes = RoomType::all();
        return response()->json($roomTypes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'base_price' => 'required|numeric|min:0',
            'max_occupancy' => 'required|integer|min:1|max:20',
            'bed_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $roomType = RoomType::create($validated);
        return response()->json($roomType, 201);
    }

    public function show(RoomType $roomType): JsonResponse
    {
        return response()->json($roomType);
    }

    public function update(Request $request, RoomType $roomType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|max:10',
            'base_price' => 'numeric|min:0',
            'max_occupancy' => 'integer|min:1|max:20',
            'bed_type' => 'string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $roomType->update($validated);
        return response()->json($roomType);
    }

    public function destroy(RoomType $roomType): JsonResponse
    {
        $roomType->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }
}
