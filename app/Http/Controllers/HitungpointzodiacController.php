<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Berita;

class HitungpointzodiacController extends Controller
{
    public function create()
    {
        return view('template.hitungpointzodiac', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }
    
    
}
