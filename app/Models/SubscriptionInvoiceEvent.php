<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoiceEvent extends Model
{
    use HasFactory;

    public const TYPE_CALLBACK = 'callback';
    public const TYPE_RETRY = 'retry';
    public const TYPE_ADMIN_ACTION = 'admin_action';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'meta' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function record(
        SubscriptionInvoice $invoice,
        string $type,
        ?string $status = null,
        ?string $reference = null,
        ?array $payload = null,
        ?array $meta = null,
        ?int $createdBy = null
    ): self {
        return self::query()->create([
            'subscription_invoice_id' => $invoice->id,
            'type' => $type,
            'gateway' => $invoice->gateway,
            'status' => $status,
            'reference' => $reference,
            'payload' => $payload,
            'meta' => $meta,
            'created_by' => $createdBy,
        ]);
    }
}
