<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'payment_metadata' => 'array',
    ];

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'order_id', 'order_id');
    }
}
