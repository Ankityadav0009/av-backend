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
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\PublicController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Public (no auth) - for customer landing & booking
Route::get('public/rooms/available', [PublicController::class, 'availableRooms']);
Route::post('public/customer/request-otp', [PublicController::class, 'requestOtp']);
Route::post('public/customer/verify-otp', [PublicController::class, 'verifyOtp']);
Route::post('public/bookings', [PublicController::class, 'createBooking']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Masters
    Route::apiResource('floors', FloorController::class);
    Route::apiResource('room-types', RoomTypeController::class);
    Route::apiResource('rooms', RoomController::class);
    Route::apiResource('staff', StaffController::class);
    Route::apiResource('users', UserController::class)->except(['create', 'edit']);
    Route::apiResource('roles', RoleController::class)->except(['create', 'edit']);
    Route::get('menus/list-all', [MenuController::class, 'listAll']);
    Route::apiResource('menus', MenuController::class)->except(['create', 'edit']);
    Route::get('roles/{role}/menus', [RoleController::class, 'menus']);
    Route::post('roles/{role}/menus', [RoleController::class, 'syncMenus']);

    // Dashboard
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('dashboard/future-bookings-calendar', [DashboardController::class, 'futureBookingsCalendar']);

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