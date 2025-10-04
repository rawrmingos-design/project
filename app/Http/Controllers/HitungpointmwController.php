<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Berita;
use App\Models\Seting;
class HitungpointmwController extends Controller
{
    public function create()
    {
        return view('template.hitungpointmw', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }
    
    
}

