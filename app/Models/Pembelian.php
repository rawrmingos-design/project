<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembelian extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    
    protected $casts = [
        'harga' => 'integer',
        'email_pembeli' => 'string', // Assuming email_pembeli is a string, not integer
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'order_id', 'order_id');
    }

    // Note: layanan field stores product name, not ID
    // We'll need to find product by name if needed
    public function getProdukAttribute()
    {
        return Produk::where('layanan', $this->layanan)->first();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'Success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'Failed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'Processing');
    }

    // Accessors
    public function getFormattedHargaAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }

    public function getStatusBadgeColorAttribute()
    {
        return match($this->status) {
            'Success' => 'success',
            'Pending' => 'warning',
            'Processing' => 'info',
            'Failed' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusIconAttribute()
    {
        return match($this->status) {
            'Success' => 'heroicon-o-check-circle',
            'Pending' => 'heroicon-o-clock',
            'Processing' => 'heroicon-o-arrow-path',
            'Failed' => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle'
        };
    }
}
