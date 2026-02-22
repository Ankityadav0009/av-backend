<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
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
