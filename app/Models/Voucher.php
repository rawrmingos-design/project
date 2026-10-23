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
     * Voucher yang boleh dipakai di sebuah situs — ISOLASI KETAT.
     *
     * Situs platform hanya melihat voucher platform; toko tenant hanya melihat
     * voucher miliknya sendiri. Tidak ada voucher yang menyeberang.
     *
     * Kenapa bukan "voucher platform berlaku di semua toko" (perilaku lama):
     * kupon platform yang dipakai di toko tenant memotong MARGIN TENANT, bukan
     * margin platform, karena `applyTenantMarginAfterDiscount()` tidak
     * membedakan pemilik kupon. Artinya promo platform membuat tenant nombok
     * tanpa pernah menyetujuinya — dan kalau promo itu melewati lantai modal
     * tenant, ordernya ditolak di toko tenant padahal jalan di situs platform.
     * Isolasi menghapus pertanyaan "siapa menanggung" sepenuhnya.
     */
    public function scopeAvailableTo(Builder $query, ?int $tenantId): Builder
    {
        if ($tenantId === null) {
            return $query->whereNull('tenant_id');
        }

        return $query->where('tenant_id', $tenantId);
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
     *
     * `layanan_id` TIDAK memakai foreign key, jadi produk yang dihapus tidak
     * bisa mengubah arti kupon ini secara diam-diam. Membandingkan nilai apa
     * adanya berarti kupon untuk produk yang sudah tidak ada tidak berlaku di
     * mana pun — jauh lebih aman daripada berubah jadi "semua produk".
     */
    public function appliesToLayanan(?int $layananId): bool
    {
        if ($this->layanan_id === null) {
            return true;
        }

        return $layananId !== null && (int) $this->layanan_id === (int) $layananId;
    }

    /**
     * Kenapa kupon ini tidak boleh dipakai untuk produk tertentu, atau null
     * kalau boleh. Dipusatkan supaya website, bot, dan API memberi pesan yang
     * sama — bukan tiga variasi.
     */
    public function rejectionReason(?int $layananId): ?string
    {
        if ($this->appliesToLayanan($layananId)) {
            return null;
        }

        return 'Kupon ini hanya berlaku untuk produk tertentu, bukan produk yang Anda pilih.';
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
