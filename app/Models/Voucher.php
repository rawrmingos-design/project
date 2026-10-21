<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'expired_at' => 'datetime',
        'tenant_id' => 'integer',
        'layanan_id' => 'integer',
        'promo' => 'float',
        'stock' => 'integer',
        'mintrx' => 'integer',
        'max_potongan' => 'integer',
        'max_uses_per_user' => 'integer',
        'used_count' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class);
    }

    public function isPlatformOwned(): bool
    {
        return $this->tenant_id === null;
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }

    public function isUsable(): bool
    {
        return (int) $this->stock > 0 && ! $this->isExpired();
    }

    /**
     * Voucher yang boleh dipakai di situs sebuah tenant.
     *
     * Milik tenant itu sendiri, ATAU milik platform (tenant_id NULL) yang
     * memang berlaku lintas toko.
     */
    public function scopeAvailableTo(Builder $query, ?int $tenantId): Builder
    {
        if ($tenantId === null) {
            return $query->whereNull('tenant_id');
        }

        return $query->where(function (Builder $q) use ($tenantId): void {
            $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
        });
    }

    public function scopeOwnedBy(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePlatform(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Batas cakupan produk: voucher tanpa `layanan_id` berlaku untuk semua
     * produk tenant; kalau diisi, hanya produk itu.
     */
    public function appliesToLayanan(?int $layananId): bool
    {
        if ($this->layanan_id === null) {
            return true;
        }

        return $layananId !== null && (int) $this->layanan_id === (int) $layananId;
    }

    /**
     * Nilai diskon untuk nominal tertentu.
     *
     * `max_potongan = 0` berarti TANPA CAP (kontrak yang sudah dipakai
     * CheckoutOrderService dan form admin), bukan diskon nol.
     */
    public function discountFor(int $amount): int
    {
        if ($amount <= 0 || $this->promo <= 0) {
            return 0;
        }

        $discount = (int) round($amount * ($this->promo / 100));
        $max = (int) $this->max_potongan;

        if ($max > 0 && $discount > $max) {
            $discount = $max;
        }

        return max(0, min($discount, $amount));
    }
}
