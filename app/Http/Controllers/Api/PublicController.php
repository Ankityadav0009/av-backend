<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    /** Available rooms for customer (no auth) - with image and price */
    public function availableRooms(): JsonResponse
    {
        $rooms = Room::with(['roomType', 'floor'])
            ->where('is_active', true)
            ->where('status', 'available')
            ->orderBy('room_number')
            ->get();
        $bookedRoomIds = Booking::whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_out_date', '>=', Carbon::today()->toDateString())
            ->where('check_in_date', '<=', Carbon::today()->toDateString())
            ->pluck('room_id');
        foreach ($rooms as $r) {
            $r->is_available = ! $bookedRoomIds->contains($r->id);
            $r->display_price = $r->custom_price ?? $r->roomType?->base_price ?? 0;
        }
        return response()->json($rooms);
    }

    /** Request OTP for mobile - creates/updates customer, sends OTP (stub: returns in response for dev) */
    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mobile' => 'required|string|regex:/^[0-9]{10}$/',
            'name' => 'nullable|string|max:255',
        ]);
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);
        $customer = Customer::updateOrCreate(
            ['mobile' => $validated['mobile']],
            [
                'name' => $validated['name'] ?? 'Guest',
                'otp_code' => $otp,
                'otp_expires_at' => $expiresAt,
                'verified_at' => null,
            ]
        );
        // TODO: Send SMS with $otp. For now return in response for development.
        return response()->json([
            'message' => 'OTP sent to your mobile',
            'otp' => $otp, // Remove in production when SMS is integrated
        ]);
    }

    /** Verify OTP and return customer token for session */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mobile' => 'required|string|regex:/^[0-9]{10}$/',
            'otp' => 'required|string|size:6',
        ]);
        $customer = Customer::where('mobile', $validated['mobile'])->first();
        if (! $customer || ! $customer->isOtpValid($validated['otp'])) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }
        $customer->update(['verified_at' => now(), 'otp_code' => null, 'otp_expires_at' => null]);
        $token = $customer->id . '_' . Str::random(32);
        cache()->put('customer_token_' . $token, $customer->id, now()->addHours(24));
        return response()->json([
            'message' => 'Verified',
            'customer_token' => $token,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile' => $customer->mobile,
            ],
        ]);
    }

    /** Public booking: min 20% advance, after OTP verification. Expects customer_token in header or body. */
    public function createBooking(Request $request): JsonResponse
    {
        $token = $request->header('X-Customer-Token') ?? $request->input('customer_token');
        $customerId = $token ? cache()->get('customer_token_' . $token) : null;
        if (! $customerId) {
            return response()->json(['message' => 'Please verify your mobile with OTP first'], 401);
        }
        $customer = Customer::find($customerId);
        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 401);
        }

        $roomIds = $request->input('room_ids', []);
        if (! is_array($roomIds)) {
            $roomIds = $roomIds ? [$roomIds] : [];
        }
        $roomIds = array_unique(array_filter(array_map('intval', $roomIds)));
        if (empty($roomIds)) {
            return response()->json(['message' => 'Select at least one room'], 422);
        }

        $validated = $request->validate([
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'room_rate' => 'required|numeric|min:0',
            'advance_paid' => 'required|numeric|min:0',
            'guest_name' => 'nullable|string|max:255',
            'guest_email' => 'nullable|email',
        ]);

        foreach ($roomIds as $rid) {
            $exists = Booking::where('room_id', $rid)
                ->orWhereHas('rooms', fn ($q) => $q->where('rooms.id', $rid))
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in_date', '<', $validated['check_out_date'])
                ->where('check_out_date', '>', $validated['check_in_date'])
                ->exists();
            if ($exists) {
                $r = Room::find($rid);
                return response()->json(['message' => 'Room ' . ($r->room_number ?? $rid) . ' is not available for these dates'], 422);
            }
        }

        $nights = Carbon::parse($validated['check_in_date'])->diffInDays(Carbon::parse($validated['check_out_date']));
        $totalAmount = $validated['room_rate'] * $nights * count($roomIds);
        $minAdvance = round($totalAmount * 0.2, 2);
        if ($validated['advance_paid'] < $minAdvance) {
            return response()->json(['message' => 'Minimum 20% advance required (₹' . $minAdvance . ')'], 422);
        }

        $booking = DB::transaction(function () use ($request, $validated, $roomIds, $totalAmount, $customer) {
            $payload = [
                'booking_number' => Booking::generateBookingNumber(),
                'guest_name' => $validated['guest_name'] ?? $customer->name,
                'guest_phone' => $customer->mobile,
                'guest_email' => $validated['guest_email'] ?? $customer->email,
                'room_id' => $roomIds[0],
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'check_in_time' => '14:00',
                'check_out_time' => '11:00',
                'adults' => 1,
                'children' => 0,
                'room_rate' => (float) $request->room_rate,
                'total_amount' => $totalAmount,
                'advance_paid' => (float) $validated['advance_paid'],
                'payment_method' => $request->input('payment_method', 'upi'),
                'status' => 'confirmed',
            ];
            $booking = Booking::create($payload);
            foreach ($roomIds as $rid) {
                $booking->rooms()->attach($rid, ['room_rate' => (float) $request->room_rate]);
            }
            return $booking;
        });

        return response()->json(Booking::with(['room.roomType', 'room.floor', 'rooms.roomType', 'rooms.floor'])->find($booking->id), 201);
    }
}
