<?php

namespace App\Models;

use App\Support\PembelianStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * Pembelian (Order) Model
 * 
 * IMPORTANT: Naming Ambiguity Alert
 * ================================
 * Due to legacy schema design, this model has confusing field names that violate Laravel conventions:
 * 
 * - `username` (string): The RESELLER's username. This is the buyer/agent who placed the order.
 *                        Relates to the `users` table as a string FK (not a proper foreign key).
 * 
 * - `user_id` (string):  The TARGET GAME ACCOUNT ID (e.g., Mobile Legends ID: "12345678").
 *                        This is NOT a foreign key to users table. This is the end-customer's game account.
 * 
 * - `zone` (string):     The TARGET GAME ZONE/SERVER ID (e.g., server ID for games that require it).
 * 
 * In Laravel conventions, `user_id` typically means a FK to the users table. Here it does NOT.
 * Use the `target_game_account_id` accessor alias for semantic clarity in new code.
 * 
 * @property string $username              Reseller username (FK to users.username)
 * @property string $user_id               Target game account ID (NOT a FK)
 * @property string|null $zone             Target game zone/server ID
 * @property string $order_id              Unique order identifier
 * @property string $layanan               Product/service name
 * @property int $harga                    Order price in IDR
 * @property int $profit                   Profit margin
 * @property string $status                Order status (Pending, Success, Failed, etc.)
 * @property-read string $target_game_account_id  Semantic alias for user_id field
 */
class Pembelian extends Model

{
    use HasFactory;

    protected $guarded = [];

    protected $appends = [
        'display_invoice_id',
        'is_reset_attempt',
        'normalized_status',
        'status_display_label',
        'target_game_account_id',
    ];

    protected $casts = [
        'harga'                   => 'integer',
        'refund_amount'           => 'integer',
        'refunded_at'             => 'datetime',
        'email_pembeli'           => 'string',
        'reseller_integration_id' => 'integer',
        'base_order_id'           => 'string',
        'invoice_version'         => 'integer',
        'display_order_id'        => 'string',
        'active_layanan_id'       => 'integer',
        'active_provider_code'    => 'string',
        'active_provider_sku'     => 'string',
        'active_attempt_token'    => 'string',
        'active_attempt_reference'=> 'string',
        'environment'             => 'string',
        'is_sandbox'              => 'boolean',
        'reset_status'            => 'string',
        'reset_count'             => 'integer',
        'reset_requested_by'      => 'integer',
        'reset_requested_at'      => 'datetime',
        'reset_reason'            => 'string',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
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

    public function resellerIntegration(): BelongsTo
    {
        return $this->belongsTo(ResellerIntegration::class);
    }

    public function resellerCallbackDeliveries(): HasMany
    {
        return $this->hasMany(ResellerCallbackDelivery::class)->latest('id');
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

    /**
     * Semantic accessor for the target game account ID.
     * 
     * This provides a clearer alternative to accessing $pembelian->user_id directly,
     * making it explicit that this is NOT a reseller/user FK but rather the
     * end-customer's game account identifier.
     * 
     * Usage:
     *   $gameAccountId = $pembelian->target_game_account_id;
     *   
     * Instead of the ambiguous:
     *   $userId = $pembelian->user_id;  // Confusing - is this the reseller or game account?
     * 
     * @return string The target game account ID
     */
    public function getTargetGameAccountIdAttribute(): string
    {
        return (string) $this->user_id;
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

    public function scopeSandbox($query)
    {
        return $query->where(function ($inner): void {
            $inner->where('is_sandbox', true)
                ->orWhere('environment', 'sandbox')
                ->orWhere('log', 'like', '%"environment":"sandbox"%');
        });
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

    public function sandboxMetadata(): array
    {
        $decoded = json_decode((string) ($this->log ?? ''), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function isSandboxOrder(): bool
    {
        if ($this->attributes['is_sandbox'] ?? null) {
            return (bool) $this->attributes['is_sandbox'];
        }

        $environment = strtolower(trim((string) ($this->attributes['environment'] ?? '')));
        if ($environment !== '') {
            return $environment === 'sandbox';
        }

        $metadata = $this->sandboxMetadata();

        return ($metadata['environment'] ?? null) === 'sandbox'
            && (($metadata['source'] ?? null) === 'reseller_h2h' || ($metadata['sandbox'] ?? false) === true);
    }

    public function sandboxEnvironmentLabel(): string
    {
        $environment = strtolower(trim((string) ($this->attributes['environment'] ?? '')));

        if ($environment !== '') {
            return $environment === 'sandbox' ? 'Sandbox' : ucfirst($environment);
        }

        return $this->isSandboxOrder() ? 'Sandbox' : 'Live';
    }
}
