<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubscriptionInvoice extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'integer',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionInvoiceEvent::class)->latest();
    }

    /**
     * merchantOrderId Duitku untuk invoice BARU.
     *
     * WAJIB dipanggil per invoice: kolom `gateway_ref` unik dan billing
     * berulang butuh banyak invoice per langganan. Jangan pernah menurunkan
     * ref dari langganan — invoice periode berikutnya tidak akan bisa dibuat.
     */
    public static function freshGatewayRef(): string
    {
        return 'SUB-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(6));
    }
}
