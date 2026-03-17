<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use App\Models\Layanan;
use App\Models\Berita;

use Illuminate\Support\Facades\Cache;

class PricelistController extends Controller
{
    public function create()
    {
        $ttl = 600; // 10 minutes for pricelist as it changes less frequently

        $datas = Cache::remember('pricelist_data', $ttl, function () {
            return Layanan::join('kategoris', 'layanans.kategori_id', 'kategoris.id')
                ->where('kategoris.status', 'active')
                ->orderBy('created_at', 'desc')
                ->select(
                    'layanans.id',
                    'layanans.kategori_id',
                    'layanans.layanan',
                    'layanans.provider_id',
                    'layanans.harga_member AS harga',
                    'layanans.harga_member',
                    'layanans.harga_platinum',
                    'layanans.harga_gold',
                    'layanans.status',
                    'kategoris.nama AS nama_kategori'
                )
                ->get();
        });
                
        $kategori = Cache::remember('kategori_all', $ttl, function () {
            return Kategori::get();
        });

        $logoheader = Cache::remember('logo_header', $ttl, function () {
            return Berita::where('tipe', 'logoheader')->latest()->first();
        });

        $logofooter = Cache::remember('logo_footer', $ttl, function () {
            return Berita::where('tipe', 'logofooter')->latest()->first();
        });

        $pay_method = Cache::remember('payment_methods_all', $ttl, function () {
            return \App\Models\Method::all();
        });

        return view('template.pricelist', [
            'datas' => $datas, 
            'kategoris' => $kategori,
            'logoheader' => $logoheader,
            'logofooter' => $logofooter,
            'pay_method' => $pay_method
        ]);
    }
}
