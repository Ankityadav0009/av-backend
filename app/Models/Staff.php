<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'department',
        'designation',
        'address',
        'join_date',
        'salary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
