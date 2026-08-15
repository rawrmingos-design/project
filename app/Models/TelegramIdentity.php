<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramIdentity extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'bot_scope',
        'telegram_user_id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'linked_at',
        'verified_at',
        'last_seen_at',
        'revoked_at',
    ];

    protected $casts = [
        'linked_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->verified_at !== null;
    }
}
