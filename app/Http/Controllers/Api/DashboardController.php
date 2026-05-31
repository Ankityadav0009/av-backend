<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Future bookings calendar: for each date in range, list room numbers that are booked.
     * Used to show on dashboard calendar which rooms are booked on which day.
     */
    public function futureBookingsCalendar(Request $request): JsonResponse
    {
        $from = $request->get('from', Carbon::today()->toDateString());
        $to = $request->get('to', Carbon::today()->addDays(60)->toDateString());
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();
        if ($fromDate->gt($toDate)) {
            return response()->json(['by_date' => []]);
        }
        $bookings = Booking::with(['room', 'rooms'])
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_out_date', '>=', $fromDate->toDateString())
            ->where('check_in_date', '<=', $toDate->toDateString())
            ->get();
        $byDate = [];
        for ($d = $fromDate->copy(); $d->lte($toDate); $d->addDay()) {
            $dateStr = $d->toDateString();
            $roomNumbers = [];
            foreach ($bookings as $b) {
                $checkIn = $b->check_in_date instanceof \Carbon\Carbon ? $b->check_in_date->toDateString() : $b->check_in_date;
                $checkOut = $b->check_out_date instanceof \Carbon\Carbon ? $b->check_out_date->toDateString() : $b->check_out_date;
                $isBooked = $checkIn <= $dateStr && ($checkOut > $dateStr || ($checkIn === $checkOut && $checkOut === $dateStr));
                if ($isBooked) {
                    foreach ($b->room_numbers as $rn) {
                        $roomNumbers[] = $rn;
                    }
                }
            }
            $byDate[$dateStr] = array_values(array_unique($roomNumbers));
        }
        return response()->json(['by_date' => $byDate]);
    }

    public function stats(): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        $totalRooms = Room::where('is_active', true)->count();
        $availableRooms = Room::where('is_active', true)->where('status', 'available')->count();
        $occupiedRooms = Room::where('is_active', true)->where('status', 'occupied')->count();

        $todayCheckIns = Booking::whereDate('check_in_date', $today)
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->count();

        $todayCheckOuts = Booking::whereDate('check_out_date', $today)
            ->where('status', 'checked_out')
            ->count();

        $todayRevenue = Booking::whereDate('checked_out_at', $today)
            ->where('status', 'checked_out')
            ->sum('total_amount');

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $monthlyRevenue = Booking::where('status', 'checked_out')
            ->whereBetween('checked_out_at', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        return response()->json([
            'total_rooms' => $totalRooms,
            'available_rooms' => $availableRooms,
            'occupied_rooms' => $occupiedRooms,
            'today_check_ins' => $todayCheckIns,
            'today_check_outs' => $todayCheckOuts,
            'today_revenue' => (float) $todayRevenue,
            'monthly_revenue' => (float) $monthlyRevenue,
        ]);
    }
}
