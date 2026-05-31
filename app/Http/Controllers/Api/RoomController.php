<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        $rooms = Room::with(['roomType', 'floor'])->get();
        return response()->json($rooms);
    }

    public function store(Request $request): JsonResponse
    {
        self::normalizeRoomRequest($request);
        $validated = $request->validate([
            'room_number' => 'required|string|max:50|unique:rooms,room_number',
            'room_type_id' => 'required|exists:room_types,id',
            'floor_id' => 'required|exists:floors,id',
            'status' => 'in:available,occupied,maintenance,cleaning',
            'custom_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('rooms', 'public');
        }
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
        self::normalizeRoomRequest($request);
        $validated = $request->validate([
            'room_number' => 'string|max:50|unique:rooms,room_number,' . $room->id,
            'room_type_id' => 'exists:room_types,id',
            'floor_id' => 'exists:floors,id',
            'status' => 'in:available,occupied,maintenance,cleaning',
            'custom_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        if ($request->hasFile('image')) {
            if ($room->image_path) {
                Storage::disk('public')->delete($room->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('rooms', 'public');
        }
        $room->update($validated);
        return response()->json(Room::with(['roomType', 'floor'])->find($room->id));
    }

    public function destroy(Room $room): JsonResponse
    {
        if ($room->image_path) {
            Storage::disk('public')->delete($room->image_path);
        }
        $room->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }

    private static function normalizeRoomRequest(Request $request): void
    {
        $merge = [];
        if ($request->has('custom_price') && $request->input('custom_price') === '') {
            $merge['custom_price'] = null;
        }
        if ($request->has('notes') && $request->input('notes') === '') {
            $merge['notes'] = null;
        }
        if ($request->has('is_active')) {
            $v = $request->input('is_active');
            if ($v === '1' || $v === 1 || $v === true) {
                $merge['is_active'] = true;
            } elseif ($v === '0' || $v === 0 || $v === false) {
                $merge['is_active'] = false;
            }
        }
        if ($merge !== []) {
            $request->merge($merge);
        }
    }
}
