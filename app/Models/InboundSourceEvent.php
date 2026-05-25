<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboundSourceEvent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'matched_entry_id' => 'integer',
        'response_status' => 'integer',
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
