<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Berita extends Model
{
    protected $fillable = [
        'path',
        'tipe',
        'deskripsi'
    ];

    use HasFactory;
}
