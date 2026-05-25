<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ResellerCallbackProfile extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'webhook_secret_encrypted',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'version' => 'integer',
        'ip_allowlist' => 'array',
        'retry_enabled' => 'boolean',
        'last_tested_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(ResellerIntegration::class, 'reseller_integration_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ResellerCallbackDelivery::class)->latest('id');
    }

    public function setWebhookSecretAttribute($value): void
    {
        $value = filled($value) ? (string) $value : null;

        $this->attributes['webhook_secret_encrypted'] = $value !== null
            ? Crypt::encryptString($value)
            : null;
    }

    public function decryptedWebhookSecret(): string
    {
        $encrypted = (string) ($this->attributes['webhook_secret_encrypted'] ?? '');

        if ($encrypted === '') {
            return '';
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return '';
        }
    }
}
