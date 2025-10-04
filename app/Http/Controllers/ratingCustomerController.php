<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Berita;
use App\Models\Kategori; 
use App\Models\Rating;
use Illuminate\Support\Facades\DB;
class ratingCustomerController extends Controller
{

public function create()
{
    $ratings = DB::table('ratings')
        ->selectRaw('*, CAST(ratings.created_at AS DATETIME) AS created_at, kategoris.nama AS kategori_nama')
        ->join('pembelians', 'ratings.rating_id', '=', 'pembelians.order_id')
        ->join('pembayarans', 'ratings.rating_id', '=', 'pembayarans.order_id')
        ->leftJoin('kategoris', 'ratings.kategori_id', '=', 'kategoris.id') 
        ->select('ratings.bintang', 'ratings.comment', 'ratings.id', 'ratings.created_at', 'pembelians.username', 'pembelians.layanan', 'pembayarans.no_pembeli', 'ratings.kategori_id', 'kategoris.nama AS kategori_nama') // Memilih kolom nama dari tabel kategoris sebagai kategori_nama
        ->orderByDesc('ratings.id')
        ->get();

    return view('template.ratingcust', [
        'banner' => Berita::where('tipe', 'banner')->get(),
        'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        'popup' => Berita::where('tipe', 'popup')->latest()->first(),
        'ratings' => $ratings,
    ]);
}

}

