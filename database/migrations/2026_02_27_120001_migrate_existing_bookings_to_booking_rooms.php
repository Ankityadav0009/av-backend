<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $bookings = DB::table('bookings')->whereNotNull('room_id')->get();
        foreach ($bookings as $b) {
            DB::table('booking_rooms')->insertOrIgnore([
                'booking_id' => $b->id,
                'room_id' => $b->room_id,
                'room_rate' => $b->room_rate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('booking_rooms')->truncate();
    }
};
