<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['room.roomType', 'room.floor', 'rooms.roomType', 'rooms.floor', 'createdByUser']);
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('from_date')) {
            $query->where('check_out_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('check_in_date', '<=', $request->to_date);
        }
        if ($request->filled('booking_date_from')) {
            $query->whereDate('created_at', '>=', $request->booking_date_from);
        }
        if ($request->filled('booking_date_to')) {
            $query->whereDate('created_at', '<=', $request->booking_date_to);
        }
        if ($request->filled('check_in_from')) {
            $query->whereDate('check_in_date', '>=', $request->check_in_from);
        }
        if ($request->filled('check_in_to')) {
            $query->whereDate('check_in_date', '<=', $request->check_in_to);
        }
        if ($request->filled('check_out_from')) {
            $query->whereDate('check_out_date', '>=', $request->check_out_from);
        }
        if ($request->filled('check_out_to')) {
            $query->whereDate('check_out_date', '<=', $request->check_out_to);
        }
        if ($request->filled('room_number')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('room', fn ($r) => $r->where('room_number', 'like', '%' . $request->room_number . '%'))
                    ->orWhereHas('rooms', fn ($r) => $r->where('room_number', 'like', '%' . $request->room_number . '%'));
            });
        }
        $bookings = $query->orderBy('created_at', 'desc')->get();
        return response()->json($bookings);
    }

    public function store(Request $request): JsonResponse
    {
        $roomIds = $request->input('room_ids', $request->input('room_id'));
        if (! is_array($roomIds)) {
            $roomIds = $roomIds ? [$roomIds] : [];
        }
        $roomIds = array_unique(array_filter(array_map('intval', $roomIds)));
        $validated = $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'required|string|max:20',
            'guest_email' => 'nullable|email',
            'guest_address' => 'nullable|string',
            'id_type' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:50',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'check_in_time' => 'nullable|string|regex:/^\d{2}:\d{2}$/',
            'check_out_time' => 'nullable|string|regex:/^\d{2}:\d{2}$/',
            'adults' => 'integer|min:1|max:20',
            'children' => 'integer|min:0|max:10',
            'room_rate' => 'required|numeric|min:0',
            'advance_paid' => 'numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,upi',
            'notes' => 'nullable|string',
        ]);
        if (empty($roomIds)) {
            return response()->json(['message' => 'Select at least one room'], 422);
        }
        foreach ($roomIds as $rid) {
            if (! Room::where('id', $rid)->exists()) {
                return response()->json(['message' => 'Invalid room selected'], 422);
            }
        }
        foreach ($roomIds as $rid) {
            $existing = Booking::where(function ($q) use ($rid) {
                $q->where('room_id', $rid)
                    ->orWhereHas('rooms', fn ($q2) => $q2->where('rooms.id', $rid));
            })
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in_date', '<', $validated['check_out_date'])
                ->where('check_out_date', '>', $validated['check_in_date'])
                ->exists();
            if ($existing) {
                $r = Room::find($rid);
                return response()->json(['message' => 'Room ' . ($r->room_number ?? $rid) . ' is already booked for selected dates'], 422);
            }
        }
        $checkIn = \Carbon\Carbon::parse($validated['check_in_date']);
        $checkOut = \Carbon\Carbon::parse($validated['check_out_date']);
        $nights = $checkIn->diffInDays($checkOut);
        if ($nights < 1) $nights = 1;
        $roomRate = (float) ($request->input('room_rate', $validated['room_rate'] ?? 0));
        $totalAmount = $roomRate * $nights * count($roomIds);
        $validated['room_id'] = $roomIds[0];
        $validated['room_rate'] = $roomRate;
        $validated['total_amount'] = $totalAmount;
        $validated['advance_paid'] = $validated['advance_paid'] ?? 0;
        $validated['check_in_time'] = $validated['check_in_time'] ?? '14:00';
        $validated['check_out_time'] = $validated['check_out_time'] ?? '11:00';
        $validated['adults'] = $validated['adults'] ?? 1;
        $validated['children'] = $validated['children'] ?? 0;
        $validated['status'] = 'confirmed';
        $validated['booking_number'] = Booking::generateBookingNumber();
        $validated['payment_method'] = $validated['payment_method'] ?? 'cash';
        $validated['created_by'] = $request->user()?->id;

        $paths = [];
        if ($request->hasFile('id_proofs')) {
            foreach ($request->file('id_proofs') as $file) {
                $path = $file->store('id_proofs', 'public');
                $paths[] = $path;
            }
        }
        $validated['id_proof_paths'] = $paths;

        $booking = Booking::create($validated);
        foreach ($roomIds as $rid) {
            $booking->rooms()->attach($rid, ['room_rate' => $roomRate]);
        }
        return response()->json(Booking::with(['room.roomType', 'room.floor', 'rooms.roomType', 'rooms.floor', 'createdByUser'])->find($booking->id), 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load(['room.roomType', 'room.floor', 'rooms.roomType', 'rooms.floor', 'createdByUser']);
        return response()->json($booking);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'Cannot edit this booking'], 422);
        }
        $validated = $request->validate([
            'guest_name' => 'string|max:255',
            'guest_phone' => 'string|max:20',
            'guest_email' => 'nullable|email',
            'guest_address' => 'nullable|string',
            'id_type' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:50',
            'room_id' => 'exists:rooms,id',
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'check_in_time' => 'nullable|string|regex:/^\d{2}:\d{2}$/',
            'check_out_time' => 'nullable|string|regex:/^\d{2}:\d{2}$/',
            'adults' => 'integer|min:1|max:20',
            'children' => 'integer|min:0|max:10',
            'room_rate' => 'numeric|min:0',
            'advance_paid' => 'numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['check_in_date']) && isset($validated['check_out_date'])) {
            $nights = \Carbon\Carbon::parse($validated['check_in_date'])->diffInDays(\Carbon\Carbon::parse($validated['check_out_date']));
            if ($nights >= 1 && isset($validated['room_rate'])) {
                $validated['total_amount'] = $validated['room_rate'] * $nights;
            }
        }
        $booking->update($validated);
        return response()->json(Booking::with(['room.roomType', 'room.floor'])->find($booking->id));
    }

    public function destroy(Booking $booking): JsonResponse
    {
        if ($booking->status === 'checked_in') {
            return response()->json(['message' => 'Cannot delete checked-in booking'], 422);
        }
        foreach ($booking->id_proof_paths ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }
        $booking->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }

    public function checkIn(Booking $booking): JsonResponse
    {
        $booking->load(['rooms', 'room']);
        if ($booking->status !== 'confirmed') {
            return response()->json(['message' => 'Booking must be confirmed to check-in'], 422);
        }
        $rooms = $booking->rooms->isNotEmpty() ? $booking->rooms : collect([$booking->room])->filter();
        foreach ($rooms as $room) {
            if ($room && $room->status === 'occupied') {
                return response()->json(['message' => 'Room ' . $room->room_number . ' is already occupied'], 422);
            }
        }
        $booking->update(['status' => 'checked_in', 'checked_in_at' => now()]);
        foreach ($rooms as $room) {
            if ($room) $room->update(['status' => 'occupied']);
        }
        return response()->json(Booking::with(['room.roomType', 'room.floor', 'rooms.roomType', 'rooms.floor'])->find($booking->id));
    }

    public function checkOut(Booking $booking): JsonResponse
    {
        $booking->load(['rooms', 'room']);
        if ($booking->status !== 'checked_in') {
            return response()->json(['message' => 'Booking must be checked-in to check-out'], 422);
        }
        $booking->update(['status' => 'checked_out', 'checked_out_at' => now()]);
        $rooms = $booking->rooms->isNotEmpty() ? $booking->rooms : collect([$booking->room])->filter();
        foreach ($rooms as $room) {
            if ($room) $room->update(['status' => 'available']);
        }
        return response()->json(Booking::with(['room.roomType', 'room.floor', 'rooms.roomType', 'rooms.floor'])->find($booking->id));
    }

    /**
     * Returns checked-in guests whose checkout date is today and checkout time has passed.
     */
    public function overdueCheckouts(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $now = now();
        $bookings = Booking::with(['room.roomType', 'room.floor'])
            ->where('status', 'checked_in')
            ->whereDate('check_out_date', $today)
            ->orderBy('guest_name')
            ->get()
            ->filter(function ($b) use ($now) {
                $time = $b->check_out_time ?? '11:00';
                $checkoutDt = \Carbon\Carbon::parse($b->check_out_date . ' ' . $time);
                return $now->gte($checkoutDt);
            })
            ->values();
        return response()->json($bookings);
    }

    public function availability(Request $request): JsonResponse
    {
        $date = $request->get('date', date('Y-m-d'));
        $rooms = Room::with(['roomType', 'floor'])->where('is_active', true)->get();
        $bookedRoomIds = Booking::whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->pluck('room_id');
        foreach ($rooms as $room) {
            $room->is_available = !$bookedRoomIds->contains($room->id) && $room->status === 'available';
        }
        return response()->json($rooms);
    }
}
