<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateHistory extends Model
{
    use HasFactory;

    protected $table = 'affiliate_histories';

    protected $fillable = [
        'uplink_id',
        'downlink_id',
        'order_id',
        'amount',
        'note',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function uplink()
    {
        return $this->belongsTo(User::class, 'uplink_id');
    }

    public function downlink()
    {
        return $this->belongsTo(User::class, 'downlink_id');
    }
}
