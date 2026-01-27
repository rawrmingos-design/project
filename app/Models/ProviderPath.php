<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderPath extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'modal_price' => 'decimal:2',
        'priority' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    public function layanan()
    {
        return $this->belongsTo(Layanan::class);
    }
}
