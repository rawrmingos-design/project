<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceNotificationDelivery extends Model
{
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_EMAIL = 'email';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_id',
        'invoice_version',
        'channel',
        'transition',
        'status',
        'provider',
        'template_slug',
        'recipient',
        'recipient_hash',
        'payload_json',
        'attempts',
        'next_attempt_at',
        'last_error',
        'provider_message_id',
        'locked_at',
        'sent_at',
        'idempotency_key',
    ];

    protected $casts = [
        'invoice_version' => 'integer',
        'payload_json' => 'array',
        'attempts' => 'integer',
        'next_attempt_at' => 'datetime',
        'locked_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}

