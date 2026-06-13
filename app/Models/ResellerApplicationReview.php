<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerApplicationReview extends Model
{
    use HasFactory;

    protected $table = 'reseller_application_reviews';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'reviewed_by',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'submitted' => 'Application Submitted',
            'approved' => 'Application Approved',
            'rejected' => 'Application Rejected',
            'resubmitted' => 'Application Resubmitted',
            default => ucfirst((string) $this->action),
        };
    }

    public function isAdminAction(): bool
    {
        return in_array($this->action, ['approved', 'rejected'], true);
    }

    public function isUserAction(): bool
    {
        return in_array($this->action, ['submitted', 'resubmitted'], true);
    }

    public function scopeAdminActions($query)
    {
        return $query->whereIn('action', ['approved', 'rejected']);
    }

    public function scopeUserActions($query)
    {
        return $query->whereIn('action', ['submitted', 'resubmitted']);
    }
}
