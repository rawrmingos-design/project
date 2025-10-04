<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    // Menentukan nama tabel yang digunakan oleh model ini
    protected $table = 'withdrawals';

    // Menentukan kolom yang dapat diisi (mass assignable)
    protected $fillable = [
        'rekening',
        'total_transfer',
        'biaya_admin',
        'status',
        'created_at',
        'updated_at'
    ];

    // Menentukan kolom timestamp jika Anda ingin mengelola timestamp secara manual
    // public $timestamps = true;

    // Jika tidak menggunakan timestamp otomatis
    // protected $timestamps = false;
}
