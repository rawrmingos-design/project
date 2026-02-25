<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    // Menentukan nama tabel yang digunakan oleh model ini
    protected $table = 'withdrawals';

    protected $fillable = [
        'rekening',
        'total_transfer',
        'biaya_admin',
        'status',
        'user_id',
        'bukti_transfer',
        'created_at',
        'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Menentukan kolom timestamp jika Anda ingin mengelola timestamp secara manual
    // public $timestamps = true;

    // Jika tidak menggunakan timestamp otomatis
    // protected $timestamps = false;
}
