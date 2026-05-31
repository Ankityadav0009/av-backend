<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booking extends Model
{
    protected $fillable = [
        'booking_number',
        'guest_name',
        'guest_phone',
        'guest_email',
        'guest_address',
        'id_type',
        'id_number',
        'id_proof_paths',
        'room_id',
        'check_in_date',
        'check_out_date',
        'check_in_time',
        'check_out_time',
        'adults',
        'children',
        'room_rate',
        'total_amount',
        'advance_paid',
        'status',
        'notes',
        'checked_in_at',
        'checked_out_at',
        'created_by',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'room_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'advance_paid' => 'decimal:2',
            'id_proof_paths' => 'array',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'booking_rooms')
            ->withPivot('room_rate')
            ->withTimestamps();
    }

    public function getRoomNumbersAttribute(): array
    {
        $rooms = $this->rooms;
        if ($rooms->isNotEmpty()) {
            return $rooms->pluck('room_number')->all();
        }
        if ($this->room) {
            return [$this->room->room_number];
        }
        return [];
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public static function generateBookingNumber(): string
    {
        $prefix = 'BK' . date('Ymd');
        $last = static::where('booking_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $seq = $last ? (int) substr($last->booking_number, -4) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
