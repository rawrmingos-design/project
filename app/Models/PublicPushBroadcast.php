<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicPushBroadcast extends Model
{
    protected $fillable = [
        'created_by',
        'send_mode',
        'status',
        'title',
        'body',
        'target_url',
        'payload',
        'scheduled_at',
        'started_at',
        'finished_at',
        'success_count',
        'failed_count',
        'total_count',
        'failure_messages',
    ];

    protected $casts = [
        'payload' => 'array',
        'failure_messages' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
