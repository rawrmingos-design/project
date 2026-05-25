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

    protected $casts = [
        'is_active' => 'boolean',
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

        if (ResellerCallbackUrlValidator::failureReason($profile->callback_url) !== null) {
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
}
