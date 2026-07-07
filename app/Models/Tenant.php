<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';

    public const RESERVED_SUBDOMAINS = [
        'admin',
        'api',
        'app',
        'assets',
        'cdn',
        'docs',
        'mail',
        'static',
        'support',
        'www',
    ];

    protected $guarded = [];

    protected $casts = [
        'margin_config' => 'array',
        'theme' => 'array',
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    protected function marginConfig(): Attribute
    {
        return Attribute::make(
            get: function ($value): ?array {
                $config = is_array($value) ? $value : json_decode((string) $value, true);

                if (! is_array($config)) {
                    return null;
                }

                if (array_key_exists('markup_value', $config)) {
                    $config['markup_value'] = (float) $config['markup_value'];
                }

                return $config;
            },
        );
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pembelians(): HasMany
    {
        return $this->hasMany(Pembelian::class);
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
