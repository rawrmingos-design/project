<?php

namespace App\Models;

use App\Support\PembelianStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembelian extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = [
        'display_invoice_id',
        'is_reset_attempt',
        'normalized_status',
        'status_display_label',
    ];

    protected $casts = [
        'harga' => 'integer',
        'email_pembeli' => 'string',
        'base_order_id' => 'string',
        'invoice_version' => 'integer',
        'display_order_id' => 'string',
        'active_layanan_id' => 'integer',
        'active_provider_code' => 'string',
        'active_provider_sku' => 'string',
        'active_attempt_token' => 'string',
        'active_attempt_reference' => 'string',
        'reset_status' => 'string',
        'reset_count' => 'integer',
        'reset_requested_by' => 'integer',
        'reset_requested_at' => 'datetime',
        'reset_reason' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $pembelian): void {
            if (blank($pembelian->order_id)) {
                return;
            }

            $baseOrderId = $pembelian->attributes['base_order_id'] ?? null;
            $invoiceVersion = $pembelian->attributes['invoice_version'] ?? null;

            if (blank($baseOrderId)) {
                $pembelian->attributes['base_order_id'] = $pembelian->order_id;
            }

            if ($invoiceVersion === null) {
                $pembelian->attributes['invoice_version'] = 0;
            }

            if (($pembelian->attributes['reset_count'] ?? null) === null) {
                $pembelian->attributes['reset_count'] = max(0, (int) ($pembelian->attributes['invoice_version'] ?? 0));
            }

            if (blank($pembelian->attributes['reset_status'] ?? null)) {
                $pembelian->attributes['reset_status'] = 'none';
            }

            $displayInvoiceId = $pembelian->deriveDisplayInvoiceId();

            if (blank($pembelian->attributes['display_order_id'] ?? null)) {
                $pembelian->attributes['display_order_id'] = $displayInvoiceId;
            }

            if (blank($pembelian->attributes['active_attempt_reference'] ?? null)) {
                $pembelian->attributes['active_attempt_reference'] = $displayInvoiceId;
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'order_id', 'order_id');
    }

    public function activeLayanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class, 'active_layanan_id');
    }

    // Note: layanan field stores product name, not ID
    // We'll need to find product by name if needed
    public function getProdukAttribute()
    {
        return Produk::where('layanan', $this->layanan)->first();
    }

    public function getBaseOrderIdAttribute($value): string
    {
        return (string) ($value ?: ($this->attributes['order_id'] ?? ''));
    }

    public function getInvoiceVersionAttribute($value): int
    {
        return $value === null ? 0 : (int) $value;
    }

    public function getDisplayOrderIdAttribute($value): string
    {
        return (string) ($value ?: $this->deriveDisplayInvoiceId());
    }

    public function getDisplayInvoiceIdAttribute(): string
    {
        return $this->deriveDisplayInvoiceId();
    }

    public function getIsResetAttemptAttribute(): bool
    {
        return $this->invoice_version > 0;
    }

    public function deriveDisplayInvoiceId(?int $invoiceVersion = null): string
    {
        $baseOrderId = $this->base_order_id;
        $invoiceVersion ??= $this->invoice_version;

        if ($invoiceVersion <= 0) {
            return $baseOrderId;
        }

        return sprintf('%s_%03d', $baseOrderId, $invoiceVersion);
    }

    public function nextDisplayInvoiceId(): string
    {
        return $this->deriveDisplayInvoiceId($this->invoice_version + 1);
    }

    public function getNormalizedStatusAttribute(): string
    {
        return PembelianStatus::normalize($this->status);
    }

    public function getStatusDisplayLabelAttribute(): string
    {
        return PembelianStatus::label($this->status);
    }

    public function hasStatus(string|array $statuses): bool
    {
        $expected = array_map(
            static fn (string $status): string => PembelianStatus::normalize($status),
            (array) $statuses,
        );

        return in_array($this->normalized_status, $expected, true);
    }

    public function hasPaidPaymentStatus(): bool
    {
        return in_array(optional($this->pembayaran)->status, ['Lunas', 'PAID', 'Paid', 'Success'], true);
    }

    public function hasFinalizedPaymentStatus(): bool
    {
        return in_array(optional($this->pembayaran)->status, ['Refunded', 'Batal', 'Canceled', 'Cancelled'], true);
    }

    public function normalizedResetStatus(): string
    {
        $resetStatus = strtolower(trim((string) ($this->reset_status ?? 'none')));

        if ($resetStatus === '') {
            return 'none';
        }

        return match ($resetStatus) {
            'none', 'failed', 'cancelled', 'canceled', 'completed', 'requested', 'preparing', 'processing' => $resetStatus,
            default => $resetStatus,
        };
    }

    public function hasActiveAttemptInFlight(): bool
    {
        if (in_array($this->normalizedResetStatus(), ['requested', 'preparing', 'processing'], true)) {
            return true;
        }

        $activeAttemptReference = trim((string) ($this->active_attempt_reference ?? ''));

        return $activeAttemptReference !== '' && $activeAttemptReference !== $this->display_invoice_id;
    }

    public function isResetEligible(): bool
    {
        if (! $this->hasPaidPaymentStatus() || $this->hasFinalizedPaymentStatus() || $this->hasActiveAttemptInFlight()) {
            return false;
        }

        return $this->hasStatus([
            PembelianStatus::FAILED,
            PembelianStatus::CANCELLED,
        ]);
    }

    public function isResetEditable(): bool
    {
        return $this->invoice_version > 0 && $this->normalizedResetStatus() !== 'none';
    }

    public function canEditResetRouting(): bool
    {
        return $this->isResetEditable() && $this->normalizedResetStatus() === 'requested';
    }

    public function canBeRetried(): bool
    {
        if (! $this->hasPaidPaymentStatus()) {
            return false;
        }

        return $this->hasStatus([
            PembelianStatus::PENDING,
            PembelianStatus::PROCESSING,
            PembelianStatus::FAILED,
            PembelianStatus::CANCELLED,
        ]);
    }

    public function requiresProviderStatusReferenceForRetry(): bool
    {
        return in_array(strtolower(trim((string) $this->active_provider_code)), ['vip', 'vip_reseller'], true);
    }

    public function hasRetryStatusReference(): bool
    {
        $attemptToken = trim((string) ($this->active_attempt_token ?? ''));
        if ($attemptToken !== '') {
            return true;
        }

        $providerOrderId = trim((string) ($this->provider_order_id ?? ''));

        return $providerOrderId !== '';
    }

    public function canRunRetryStatusCheck(): bool
    {
        if (! $this->canBeRetried()) {
            return false;
        }

        if (! $this->requiresProviderStatusReferenceForRetry()) {
            return true;
        }

        return $this->hasRetryStatusReference();
    }

    public function retryUnavailableReason(): ?string
    {
        if (! $this->canBeRetried()) {
            return null;
        }

        if ($this->requiresProviderStatusReferenceForRetry() && ! $this->hasRetryStatusReference()) {
            return 'Retry status check untuk VIP butuh trxid/provider_order_id. Gunakan Reset Invoice setelah saldo/provider sudah siap.';
        }

        return null;
    }

    public function syncPaymentStatusForResetEligibility(?string $targetStatus = null): void
    {
        $payment = $this->pembayaran;

        if (! $payment) {
            return;
        }

        $normalizedStatus = PembelianStatus::normalize($targetStatus ?? $this->status);

        if (in_array($normalizedStatus, [PembelianStatus::CANCELLED, PembelianStatus::REFUNDED], true)) {
            $payment->update(['status' => 'Refunded']);
        }
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', PembelianStatus::aliasesFor(PembelianStatus::PENDING));
    }

    public function scopeSuccess($query)
    {
        return $query->whereIn('status', PembelianStatus::aliasesFor(PembelianStatus::SUCCESS));
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', array_merge(
            PembelianStatus::aliasesFor(PembelianStatus::FAILED),
            PembelianStatus::aliasesFor(PembelianStatus::CANCELLED),
        ));
    }

    public function scopeProcessing($query)
    {
        return $query->whereIn('status', PembelianStatus::aliasesFor(PembelianStatus::PROCESSING));
    }

    // Accessors
    public function getFormattedHargaAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }

    public function getStatusBadgeColorAttribute()
    {
        return PembelianStatus::badgeColor($this->status);
    }

    public function getStatusIconAttribute()
    {
        return PembelianStatus::icon($this->status);
    }
}
