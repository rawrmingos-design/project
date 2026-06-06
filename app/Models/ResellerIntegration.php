<?php

namespace App\Models;

use App\Support\ResellerCallbackUrlValidator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ResellerIntegration extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'api_key_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'json',
        'api_key_last_used_at' => 'datetime',
        'api_key_rotated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $integration): void {
            if (blank($integration->integration_type)) {
                $integration->integration_type = 'provider';
            }

            if (blank($integration->credential_source)) {
                $integration->credential_source = 'global';
            }

            if (blank($integration->mode)) {
                $integration->mode = 'live';
            }
        });

        static::saved(function (self $integration): void {
            $cacheService = app(\App\Services\ResellerIntegrationCacheService::class);
            
            // Forget current hash
            if ($integration->api_key_hash) {
                $cacheService->forgetByHash($integration->api_key_hash, $integration->mode);
            }

            // Forget original hash if it was changed (e.g. key rotation)
            $originalHash = $integration->getOriginal('api_key_hash');
            $originalMode = $integration->getOriginal('mode') ?? $integration->mode;
            
            if ($originalHash && $originalHash !== $integration->api_key_hash) {
                $cacheService->forgetByHash($originalHash, $originalMode);
            }
        });

        static::deleted(function (self $integration): void {
            if ($integration->api_key_hash) {
                app(\App\Services\ResellerIntegrationCacheService::class)
                    ->forgetByHash($integration->api_key_hash, $integration->mode);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function callbackProfile(): HasOne
    {
        return $this->hasOne(ResellerCallbackProfile::class);
    }

    public function callbackDeliveries(): HasMany
    {
        return $this->hasMany(ResellerCallbackDelivery::class)->latest('id');
    }

    public function latestCallbackDelivery(): HasOne
    {
        return $this->hasOne(ResellerCallbackDelivery::class)->latestOfMany();
    }

    public function pembelians(): HasMany
    {
        return $this->hasMany(Pembelian::class);
    }

    public function outboundReadinessSummary(): array
    {
        if (! $this->is_active) {
            return [
                'state' => 'inactive',
                'label' => 'Inactive',
                'color' => 'gray',
            ];
        }

        $profile = $this->callbackProfile;

        if (! $profile) {
            return [
                'state' => 'missing_profile',
                'label' => 'Needs setup',
                'color' => 'warning',
            ];
        }

        if (! $profile->is_enabled) {
            return [
                'state' => 'disabled',
                'label' => 'Disabled',
                'color' => 'gray',
            ];
        }

        if (trim((string) $profile->callback_url) === '') {
            return [
                'state' => 'missing_url',
                'label' => 'Missing URL',
                'color' => 'warning',
            ];
        }

        if ($profile->decryptedWebhookSecret() === '') {
            return [
                'state' => 'missing_secret',
                'label' => 'Missing secret',
                'color' => 'warning',
            ];
        }

        if (ResellerCallbackUrlValidator::failureReason($profile->callback_url, $this->mode) !== null) {
            return [
                'state' => 'invalid_url',
                'label' => 'Fix URL',
                'color' => 'danger',
            ];
        }

        return [
            'state' => 'ready',
            'label' => 'Ready',
            'color' => 'success',
        ];
    }

    public function overallReadinessSummary(bool $incomingConfigured): array
    {
        if (! $this->is_active) {
            return [
                'label' => 'Inactive',
                'color' => 'gray',
            ];
        }

        $outbound = $this->outboundReadinessSummary();

        if ($incomingConfigured && $outbound['state'] === 'ready') {
            return [
                'label' => 'Ready',
                'color' => 'success',
            ];
        }

        if ($incomingConfigured || $outbound['state'] === 'ready') {
            return [
                'label' => 'Partial',
                'color' => 'warning',
            ];
        }

        return [
            'label' => 'Needs setup',
            'color' => 'danger',
        ];
    }

    public function getAllowedIpsAttribute()
    {
        $metadata = $this->metadata ?? [];
        return $metadata['allowed_ips'] ?? [];
    }

    public function setAllowedIpsAttribute($value)
    {
        $metadata = $this->metadata ?? [];
        $metadata['allowed_ips'] = $value;
        $this->metadata = $metadata;
    }

    public function setApiKeyAttribute($value): void
    {
        if (filled($value)) {
            $valueStr = (string) $value;
            $this->attributes['api_key_hash'] = hash('sha256', $valueStr);
            $this->attributes['api_key_hint'] = '...' . substr($valueStr, -6);
            $this->attributes['api_key_prefix'] = substr($valueStr, 0, 8);
        } else {
            $this->attributes['api_key_hash'] = null;
            $this->attributes['api_key_hint'] = null;
            $this->attributes['api_key_prefix'] = null;
        }
    }

    public function verifyApiKey(string $token): bool
    {
        $storedHash = $this->api_key_hash;

        if (blank($storedHash)) {
            return false;
        }

        return hash_equals($storedHash, hash('sha256', $token));
    }
}
