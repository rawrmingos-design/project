<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    use HasFactory;

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }

    public function isUsable(): bool
    {
        return (int) $this->stock > 0 && ! $this->isExpired();
    }
}
