<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
        'api_key',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'balance' => 'integer',
    ];

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
    
    // Accessors
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
