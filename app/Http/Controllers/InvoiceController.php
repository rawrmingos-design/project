<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Models\Berita;
use Illuminate\Support\Carbon;
use App\Models\Layanan;
use App\Models\Kategori;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
   public function create($order)
    {
         $data = Pembelian::where('pembayarans.order_id', $order)
        ->join('pembayarans', 'pembelians.order_id', '=', 'pembayarans.order_id')
        ->leftJoin('data_joki', 'pembelians.order_id', '=', 'data_joki.order_id')
        ->leftJoin('methods', DB::raw('pembayarans.metode COLLATE utf8mb4_unicode_ci'), '=', DB::raw('methods.code COLLATE utf8mb4_unicode_ci'))
        ->select('data_joki.*', 'pembayarans.status AS status_pembayaran', 'pembayarans.metode AS metode_pembayaran', 
                 'pembayarans.no_pembayaran', 'pembayarans.reference', 'pembelians.order_id AS id_pembelian', 
                 'user_id', 'zone', 'nickname', 'voucher', 'layanan', 'pembayarans.harga AS harga_pembayaran', 
                 'pembelians.keterangan_sn', 'pembelians.created_at AS created_at', 'pembelians.status AS status_pembelian', 
                 'pembayarans.reference', 'pembelians.tipe_transaksi AS tipe_transaksi', 'methods.name AS metode_name')
        ->orderByDesc('pembayarans.id')
        ->first();



        $layanan = Layanan::where('layanan', $data->layanan)->first();
        $kategori = Kategori::select('nama', 'thumbnail')->where('id', $layanan->kategori_id)->first();
        
        $nama = isset($kategori) && is_object($kategori) ? $kategori->nama : 'N/A';
        $thumbnail = isset($kategori) && is_object($kategori) ? $kategori->thumbnail : 'N/A';
        
        $expired = Carbon::create($data->created_at)->addDay();
        
        $iPayData = array();
        
        
    
        
        return view('template.invoice', [
        'data' => $data,
        'expired' => $expired,
        'namas' => $nama,
        'thumbnails' => $thumbnail,
         'metode_name' => $data->metode_name,
        'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        'order_id' => $data->id_pembelian,
        ]);
        
    }
    
    
   public function ratingCustomer(Request $request, $order_id) {
    $input = $request->all();
    
    $validator = Validator::make($input, [
        'bintang' => 'required',
        'comment' => 'required',
        'kategori_nama' => 'required',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withInput()->withErrors($validator);
    }

    $bintang = $input['bintang'];
    $comment = $input['comment'];
    $kategori_nama = $input['kategori_nama'];

    $kategori = Kategori::where('nama', $kategori_nama)->first();

    if ($kategori) {
        $pembelian = Pembelian::where('order_id', $order_id)->first();
        $pembayaran = Pembayaran::where('order_id', $order_id)->first();

        if ($pembelian && $pembayaran) {
            $username = $pembelian->username ? $pembelian->username : $pembayaran->no_pembeli;

            $ratingId = DB::table('ratings')->insertGetId([
                'bintang' => $bintang,
                'comment' => $comment,
                'rating_id' => $order_id,
                'kategori_id' => $kategori->id,
                'username' => $username,
                'layanan' => $pembelian->layanan,
                'no_pembeli' => $pembayaran->no_pembeli
            ]);

            $rating = DB::table('ratings')->where('id', $ratingId)->first();

            return redirect()->back()->with('success', 'Terima kasih telah memberikan testimoni!')->with('rating', $rating);
        } else {
            return redirect()->back()->withInput()->with('error', 'Data pembelian atau pembayaran tidak lengkap atau tidak ditemukan!');
        }
    } else {
        return redirect()->back()->withInput()->with('error', 'Kategori tidak ditemukan!');
    }
}

    public function checkStatus($order)
    {
        $data = Pembelian::where('pembayarans.order_id', $order)
            ->join('pembayarans', 'pembelians.order_id', '=', 'pembayarans.order_id')
            ->select('pembayarans.status AS status_pembayaran', 'pembelians.status AS status_pembelian')
            ->orderByDesc('pembayarans.id')
            ->first();

        if ($data) {
            return response()->json([
                'success' => true,
                'status_pembayaran' => $data->status_pembayaran,
                'status_pembelian' => $data->status_pembelian
            ]);
        }

        return response()->json(['success' => false], 404);
    }
}
