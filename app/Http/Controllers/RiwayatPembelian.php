<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RiwayatPembelian extends Controller
{
    public function create()
    {
        $joki = \DB::table('data_joki')->get();
        return view('template.riwayat', ['data' => Pembelian::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->get(),'joki' => $joki]);
    }
}
