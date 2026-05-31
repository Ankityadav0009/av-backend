<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'mobile',
        'email',
        'otp_code',
        'otp_expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function isOtpValid(string $otp): bool
    {
        if ($this->otp_code !== $otp || ! $this->otp_expires_at) {
            return false;
        }
        return $this->otp_expires_at->isFuture();
    }
}
