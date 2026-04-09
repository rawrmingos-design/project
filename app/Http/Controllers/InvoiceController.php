<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Models\Berita;
use App\Models\Method;
use Illuminate\Support\Carbon;
use App\Models\Layanan;
use App\Models\Kategori;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    private function applyMethodsJoin($query)
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return $query->leftJoin('methods', 'pembayarans.metode', '=', 'methods.code');
        }

        return $query->leftJoin(
            'methods',
            DB::raw('pembayarans.metode COLLATE utf8mb4_unicode_ci'),
            '=',
            DB::raw('methods.code COLLATE utf8mb4_unicode_ci')
        );
    }

   public function create($order)
    {
        $payment = Pembayaran::query()
            ->where('order_id', $order)
            ->latest('id')
            ->first();

        abort_if(! $payment, 404);

        $payment->syncExpiredStatus();

        $dataQuery = Pembelian::where('pembayarans.order_id', $order)
            ->join('pembayarans', 'pembelians.order_id', '=', 'pembayarans.order_id')
            ->leftJoin('data_joki', 'pembelians.order_id', '=', 'data_joki.order_id');

        $data = $this->applyMethodsJoin($dataQuery)
            ->select(
                'data_joki.*',
                'pembayarans.status AS status_pembayaran',
                'pembayarans.metode AS metode_pembayaran',
                'pembayarans.no_pembayaran',
                'pembayarans.reference',
                'pembayarans.expired_at',
                'pembelians.order_id AS id_pembelian',
                'user_id',
                'zone',
                'nickname',
                'voucher',
                'layanan',
                'pembayarans.harga AS harga_pembayaran',
                'pembelians.keterangan_sn',
                'pembelians.created_at AS created_at',
                'pembelians.status AS status_pembelian',
                'pembayarans.reference',
                'pembelians.tipe_transaksi AS tipe_transaksi',
                'methods.name AS metode_name'
            )
            ->orderByDesc('pembayarans.id')
            ->first();

        abort_if(! $data, 404);

        $layanan = Layanan::query()
            ->with('kategori:id,nama,thumbnail')
            ->where('layanan', $data->layanan)
            ->first();

        $kategori = $layanan?->kategori;

        $nama = $kategori?->nama ?: ($data->layanan ?: 'Produk');
        $thumbnail = $kategori?->thumbnail ?: 'assets/logo/favicon.webp';
        $methodCode = trim((string) ($data->metode_pembayaran ?? ''));
        $methodName = $data->metode_name;

        if (blank($methodName) && $methodCode !== '') {
            $matchedMethod = Method::query()
                ->get(['code', 'name'])
                ->first(function (Method $method) use ($methodCode) {
                    $rawCode = trim((string) ($method->getRawOriginal('code') ?? $method->code));
                    $rawName = trim((string) ($method->getRawOriginal('name') ?? $method->name));

                    return strcasecmp($rawCode, $methodCode) === 0
                        || strcasecmp($rawName, $methodCode) === 0;
                });

            $methodName = $matchedMethod?->name;
        }

        if (blank($methodName)) {
            $methodName = Str::of($methodCode)
                ->replace(['_', '-'], ' ')
                ->squish()
                ->title()
                ->value();
        }

        if (blank($methodName)) {
            $methodName = 'Metode Tidak Dikenal';
        }

        $expired = $data->expired_at
            ? Carbon::parse($data->expired_at)
            : Carbon::create($data->created_at)->addHours(3);

        return view('template.invoice', [
            'data' => $data,
            'expired' => $expired,
            'expiredIso' => $expired->toIso8601String(),
            'namas' => $nama,
            'thumbnails' => $thumbnail,
            'metode_name' => $methodName,
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
        $payment = Pembayaran::query()
            ->where('order_id', $order)
            ->latest('id')
            ->first();

        if ($payment) {
            $payment->syncExpiredStatus();
        }

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
