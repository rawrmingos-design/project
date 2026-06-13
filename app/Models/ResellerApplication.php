<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerApplication extends Model
{
    use HasFactory;

    protected $table = 'reseller_applications';

    protected $fillable = [
        'user_id',
        'status',
        'applied_at',
        'approved_at',
        'rejected_at',
        'reviewed_by',
        'rejection_reason',
        'business_meta',
        'submitted_from_ip',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'business_meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function getBusinessNameAttribute(): ?string
    {
        return data_get($this->business_meta, 'business_name');
    }

    public function getBusinessUrlAttribute(): ?string
    {
        return data_get($this->business_meta, 'business_url');
    }

    public function getEstimatedTransactionsAttribute(): ?int
    {
        $value = data_get($this->business_meta, 'estimated_monthly_transactions');

        return $value === null ? null : (int) $value;
    }

    public function getApplicationReasonAttribute(): ?string
    {
        return data_get($this->business_meta, 'application_reason');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
