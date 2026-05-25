<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerCallbackDelivery extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'attempt_count' => 'integer',
        'last_response_status' => 'integer',
        'last_attempted_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(ResellerIntegration::class, 'reseller_integration_id');
    }

    public function callbackProfile(): BelongsTo
    {
        return $this->belongsTo(ResellerCallbackProfile::class, 'reseller_callback_profile_id');
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
    }
}
