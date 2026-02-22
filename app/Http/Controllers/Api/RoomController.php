<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        $rooms = Room::with(['roomType', 'floor'])->get();
        return response()->json($rooms);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_number' => 'required|string|max:50|unique:rooms,room_number',
            'room_type_id' => 'required|exists:room_types,id',
            'floor_id' => 'required|exists:floors,id',
            'status' => 'in:available,occupied,maintenance,cleaning',
            'custom_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $room = Room::create($validated);
        return response()->json(Room::with(['roomType', 'floor'])->find($room->id), 201);
    }

    public function show(Room $room): JsonResponse
    {
        $room->load(['roomType', 'floor']);
        return response()->json($room);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'room_number' => 'string|max:50|unique:rooms,room_number,' . $room->id,
            'room_type_id' => 'exists:room_types,id',
            'floor_id' => 'exists:floors,id',
            'status' => 'in:available,occupied,maintenance,cleaning',
            'custom_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $room->update($validated);
        return response()->json(Room::with(['roomType', 'floor'])->find($room->id));
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }
}
