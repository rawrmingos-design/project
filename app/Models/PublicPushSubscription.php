<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicPushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id_hash',
        'endpoint',
        'endpoint_hash',
        'content_encoding',
        'public_key',
        'auth_token',
        'device_label',
        'user_agent',
        'ip_address',
        'locale',
        'last_seen_at',
        'subscribed_at',
        'unsubscribed_at',
        'is_active',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
