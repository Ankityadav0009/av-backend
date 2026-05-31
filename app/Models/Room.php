<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'room_type_id',
        'floor_id',
        'status',
        'custom_price',
        'notes',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'custom_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
