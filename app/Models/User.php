<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
