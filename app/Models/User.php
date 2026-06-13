<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;


class User extends Authenticatable implements FilamentUser, MustVerifyEmail, HasAppAuthentication
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'email',
        'role',
        'balance',
        'no_wa',
        'otp',
        'google_id',
        'google_avatar',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'referral_code',
        'uplink',
        'affiliate_status',
        'affiliate_requested_at',
        'affiliate_requirement_acknowledged_at',
        // Legacy affiliate KYC document columns remain readable for compatibility, but are no longer writable.
        'affiliate_application_note',
        'affiliate_application_meta',
        'point_balance',
        'reset_callback_enabled',
        'reset_callback_url',
        'reset_callback_secret',
        'reset_callback_signing_algorithm',
        'reset_callback_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'reset_callback_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'balance' => 'integer',
        'point_balance' => 'integer',
        'affiliate_requested_at' => 'datetime',
        'affiliate_requirement_acknowledged_at' => 'datetime',
        'affiliate_application_meta' => 'array',
        'reset_callback_enabled' => 'boolean',
        'reset_callback_version' => 'integer',
    ];



    public function resellerIntegrations()
    {
        return $this->hasMany(ResellerIntegration::class);
    }

    public function resetCallbackDeliveries()
    {
        return $this->hasMany(ResetCallbackDelivery::class);
    }

    public function resellerCallbackDeliveries()
    {
        return $this->hasMany(ResellerCallbackDelivery::class);
    }

    /**
     * Reseller Application Relationships
     */
    public function resellerApplication()
    {
        return $this->hasOne(ResellerApplication::class);
    }

    public function resellerDocuments()
    {
        return $this->hasMany(ResellerDocument::class);
    }

    public function resellerApplicationReviews()
    {
        return $this->hasMany(ResellerApplicationReview::class);
    }

    /**
    * Get the app authentication (TOTP) secret.
    */
    public function getAppAuthenticationSecret(): ?string
    {
        return $this->two_factor_secret;
    }

    /**
    * Save the app authentication (TOTP) secret.
    */
    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->two_factor_secret = $secret
            ? strtoupper(trim($secret))
            : null;

        $this->save();
    }

    /**
    * Get the name shown in the authenticator app.
    */
    public function getAppAuthenticationHolderName(): string
    {
        return config('app.name') . ' (' . $this->email . ')';
    }

    /**
    * Get the recovery codes for app authentication.
    */
    public function getAppAuthenticationRecoveryCodes(): array
    {
        return json_decode($this->two_factor_recovery_codes ?? '[]', true);
    }

    /**
    * Save the recovery codes for app authentication.
    */
    public function saveAppAuthenticationRecoveryCodes(array $codes): void
    {
        $this->two_factor_recovery_codes = json_encode($codes);
        $this->save();
    }



    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'Admin';
    }
    
    // Role Management Methods
    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }
    
    public function isMember(): bool
    {
        return $this->role === 'Member';
    }
    
    public function isGold(): bool
    {
        return $this->role === 'Gold';
    }
    
    public function isPlatinum(): bool
    {
        return $this->role === 'Platinum';
    }
    
    public function isPremiumTier(): bool
    {
        return in_array($this->role, ['Gold', 'Platinum']);
    }

    // Affiliate Helper Methods
    public function isAffiliateActive(): bool
    {
        return $this->normalizedAffiliateStatus() === 'active';
    }

    public function isAffiliatePending(): bool
    {
        return $this->normalizedAffiliateStatus() === 'pending';
    }

    public function isAffiliateInactive(): bool
    {
        return $this->normalizedAffiliateStatus() === 'inactive';
    }

    public function normalizedAffiliateStatus(): string
    {
        $status = strtolower(trim((string) $this->affiliate_status));

        return match ($status) {
            'active', 'pending', 'rejected', 'inactive' => $status,
            default => 'inactive',
        };
    }
    
    // Reseller Helper Methods
    public function hasResellerApplication(): bool
    {
        return $this->resellerApplication()->exists();
    }

    public function hasPendingResellerApplication(): bool
    {
        return $this->resellerApplication()
            ->where('status', 'pending')
            ->exists();
    }

    public function hasResellerAccess(): bool
    {
        return in_array($this->role, ['Gold', 'Platinum', 'Admin'], true);
    }

    public function hasAllResellerDocuments(): bool
    {
        $requiredTypes = ['identity', 'selfie', 'business_proof'];
        $uploadedTypes = $this->resellerDocuments()->pluck('document_type')->toArray();
        
        return count(array_diff($requiredTypes, $uploadedTypes)) === 0;
    }

    public function getResellerDocument(string $type): ?ResellerDocument
    {
        return $this->resellerDocuments()
            ->where('document_type', $type)
            ->first();
    }
    
    // Scopes
    public function scopeAdmins($query)
    {
        return $query->where('role', 'Admin');
    }
    
    public function scopeMembers($query)
    {
        return $query->where('role', 'Member');
    }
    
    public function scopeGoldMembers($query)
    {
        return $query->where('role', 'Gold');
    }
    
    public function scopePlatinumMembers($query)
    {
        return $query->where('role', 'Platinum');
    }
    
    public function scopePremiumTiers($query)
    {
        return $query->whereIn('role', ['Gold', 'Platinum']);
    }
    
    // Point Relationships
    public function pointHistories()
    {
        return $this->hasMany(PointHistory::class)->latest();
    }

    // Accessors
    public function getFormattedPointBalanceAttribute(): string
    {
        return number_format($this->point_balance ?? 0, 0, ',', '.') . ' poin';
    }

    public function getPointValueAttribute(): int
    {
        // Nilai rupiah dari saldo poin sesuai setting
        try {
            $setting = DB::table('setting_webs')->where('id', 1)->first();
            return ($this->point_balance ?? 0) * ($setting->point_value ?? 100);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp ' . number_format($this->balance ?? 0, 0, ',', '.');
    }
    
    public function getRoleBadgeColorAttribute(): string
    {
        return match($this->role) {
            'Admin' => 'danger',
            'Platinum' => 'info',
            'Gold' => 'warning',
            'Member' => 'success',
            default => 'secondary',
        };
    }
    
    public function getTierLevelAttribute(): int
    {
        return match($this->role) {
            'Admin' => 4,
            'Platinum' => 3,
            'Gold' => 2,
            'Member' => 1,
            default => 0,
        };
    }
}
