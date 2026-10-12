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
     * Tenant pemilik tagihan.
     *
     * Dicapai lewat `subscription` — `subscription_invoices` sengaja TIDAK punya
     * `tenant_id` (lihat migrasi create-nya).
     */
    public function tenant(): ?Tenant
    {
        return $this->subscription?->tenant;
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

    /** D13.1: token acak 32 byte. 64 hex, tidak bisa ditebak. */
    public static function generatePublicToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Token publik invoice ini, dibuat kalau belum ada.
     *
     * Idempoten di level DATABASE, bukan hanya di memori: keputusan diambil dari
     * kolomnya sendiri, bukan dari atribut instance. Dua proses yang berlomba
     * (mis. dua notifikasi untuk invoice yang sama, atau instance model yang
     * sudah basi) tidak boleh saling menimpa — tautan yang terlanjur dikirim
     * lewat email/WA akan mati kalau tokennya berputar (D13.1).
     */
    public function ensurePublicToken(): string
    {
        if (filled($this->public_token)) {
            return (string) $this->public_token;
        }

        // `whereNull` membuat pengisian hanya berhasil sekali; yang kalah balapan
        // tidak menimpa apa pun.
        static::query()
            ->whereKey($this->getKey())
            ->whereNull('public_token')
            ->update(['public_token' => self::generatePublicToken()]);

        // Ambil nilai yang benar-benar tersimpan — bisa jadi milik proses lain.
        $this->public_token = static::query()
            ->whereKey($this->getKey())
            ->value('public_token');

        return (string) $this->public_token;
    }

    /**
     * Matikan token publik. Dipakai saat invoice dibatalkan atau dibuat ulang
     * (D13.6) — tautan lama harus berhenti bekerja, bukan menunjuk invoice lain.
     */
    public function revokePublicToken(): void
    {
        if (filled($this->public_token)) {
            $this->forceFill(['public_token' => null])->save();
        }
    }
}
