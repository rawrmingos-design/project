<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Pembayaran extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'order_id', 'order_id');
    }

    public function normalizedStatus(): string
    {
        return strtolower(trim((string) $this->status));
    }

    public function isExpiredUnpaid(): bool
    {
        if (! in_array($this->normalizedStatus(), ['belum lunas', 'unpaid', 'pending'], true)) {
            return false;
        }

        if (! $this->expired_at) {
            return false;
        }

        return $this->expired_at->lte(now());
    }

    public function syncExpiredStatus(): bool
    {
        if (! $this->isExpiredUnpaid()) {
            return false;
        }

        $this->forceFill([
            'status' => 'Expired',
        ])->saveQuietly();

        $this->refresh();

        return true;
    }
}
