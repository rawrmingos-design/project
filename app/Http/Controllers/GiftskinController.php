<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Berita;
use App\Models\Seting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class GiftskinController extends Controller {
    
    public function create(){
        $data = \App\Models\Pembelian::whereIn('tipe_transaksi', ['mobile-legends-gift-skin', 'mobile-legends-gift-items', 'mobile-legends-gift-charisma'])
            ->orderBy('id', 'desc')
            ->get();
        
        foreach ($data as $datas) {
            $datas->gift_date = Carbon::parse($datas->created_at)->addDays(7)->format('Y-m-d H:i:s');
        }
                   
        return view('components.gift-skin', [
            'banner' => Berita::where('tipe', 'banner')->get(),
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'popup' => Berita::where('tipe', 'popup')->latest()->first(),
            'data' => $data
        ]);
    }
}