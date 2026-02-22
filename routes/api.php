<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FloorController;
use App\Http\Controllers\Api\RoomTypeController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\UserController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Masters
    Route::apiResource('floors', FloorController::class);
    Route::apiResource('room-types', RoomTypeController::class);
    Route::apiResource('rooms', RoomController::class);
    Route::apiResource('staff', StaffController::class);
    Route::apiResource('users', UserController::class)->except(['create', 'edit']);

    // Dashboard
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    // Bookings
    Route::get('bookings/overdue-checkouts', [BookingController::class, 'overdueCheckouts']);
    Route::get('availability/rooms', [BookingController::class, 'availability']);
    Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn']);
    Route::post('bookings/{booking}/check-out', [BookingController::class, 'checkOut']);
    Route::apiResource('bookings', BookingController::class);
});

Route::get('/test', function () {
    return response()->json([
        'message' => 'API working'
    ]);
});