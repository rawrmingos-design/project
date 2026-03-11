<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index()
    {
        $ratings = DB::table('ratings')
            ->join('pembelians', 'ratings.rating_id', '=', 'pembelians.order_id')
            ->join('pembayarans', 'ratings.rating_id', '=', 'pembayarans.order_id')
            ->leftJoin('kategoris', 'ratings.kategori_id', '=', 'kategoris.id') 
            ->select(
                'ratings.bintang', 
                'ratings.comment', 
                'ratings.id', 
                'ratings.created_at', 
                'pembelians.username', 
                'pembelians.layanan', 
                'pembayarans.no_pembeli', 
                'ratings.kategori_id', 
                'kategoris.nama AS kategori_nama'
            )
            ->orderByDesc('ratings.id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ratings
        ]);
    }
}
