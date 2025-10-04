<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\Berita;
use App\Models\Pembayaran;
use App\Http\Controllers\TokoPayController;
use Illuminate\Support\Facades\Auth;

class DepositController extends Controller
{
    public function reloadd()
    {
        return view('template.reload', ['data' => Deposit::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->get(),
        'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
          'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
          'pay_method' => \App\Models\Method::all()
        ]);
    }
    public function create()
    {
        // return view('components.deposit', ['data' => Deposit::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->paginate(10),
        // 'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        //   'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        // ]);
        
        return view('template.deposit', ['data' => Deposit::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->get(),
        'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
          'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
          'pay_method' => \App\Models\Method::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'jumlah' => 'required|numeric',
            'metode' => 'required',
            'no_telfon' => 'required'
        ], [
            'jumlah.numeric' => "Invalid amount",
            "jumlah.min" => "Invalid amount",
            'jumlah.required' => "Please fill in the amount",
            'no_telfon.required' => "Please fill in your WhatsApp number",
            'metode.required' => "Please fill in the method"
            ]);
        $api = \DB::table('setting_webs')->where('id',1)->first();
        
        $unik = date('Hs');
        
        $characters = '0123456789'; // Tambahan huruf kapital A-Z
        $code = '';
        
        for ($i = 0; $i < 8; $i++) { // Panjang kode 12 karakter
            $randomIndex = rand(0, strlen($characters) - 1);
            $code .= $characters[$randomIndex];
        }
        
        $kode_unik = $code;
        $order_id = 'LVD'.$unik.$kode_unik;
        $tokopay = new TokoPayController();
        
        $ttldeposit= '';
        if($request->metode == "QRIS"){
            $ttldeposit = $request->jumlah + ($request->jumlah * 0.003);
        }elseif($request->metode == "OVOPUSH" || $request->metode == "SHOPEEPAY" || $request->metode == "DANA" || $request->metode == "LINKAJA" || $request->metode == "VIRGO" || $request->metode == "ASTRAPAY"){
            $ttldeposit = $request->jumlah + ($request->jumlah * 0.005);
        }elseif($request->metode == "GOPAY"){
            $ttldeposit = $request->jumlah + ($request->jumlah * 0.01);
        }else{
            $ttldeposit = $request->jumlah;
        }

        
        $tokopayres = $tokopay->createOrder($request->jumlah, $order_id, $request->metode);
        
            
            if($tokopayres['status'] != 'Success') return response()->json(['status' => false, 'data' => 'error']);
            // $tripayres = $tripay->request($order_id, $dataLayanan->harga, $request->payment_method, $order_id.'@email.com', $request->nomor);
            

            
            // if($tripayres['success'] != true) return response()->json(['status' => false, 'data' => $tripayres['msg']]);
            if (isset($tokopayres['data'])) {
                $no_pembayaran = $tokopayres['data']['pay_url'];
            if (isset($tokopayres['data']['nomor_va'])) {
                $no_pembayaran = $tokopayres['data']['nomor_va'];
            } else if (isset($tokopayres['data']['qr_link'])) {
                $no_pembayaran = $tokopayres['data']['qr_link'];
            } else if (isset($tokopayres['data']['checkout_url'])) {
                $no_pembayaran = $tokopayres['data']['checkout_url'];
            }
        }

            $reference = $tokopayres['data']['trx_id'];
            $amount = $tokopayres['data']['total_bayar'];

        $deposit = new Deposit();
        $deposit->order_id = $order_id;
        $deposit->username = Auth::user()->username;
        $deposit->metode = $request->metode;
        $deposit->no_pembayaran = "-";
        $deposit->jumlah = $request->jumlah;
        $deposit->status = "Pending";
        $deposit->save();
        
        $pembayaran = new Pembayaran();
        $pembayaran->order_id = $order_id;
        $pembayaran->harga = $amount;
        $pembayaran->no_pembayaran = $no_pembayaran;
        $pembayaran->no_pembeli = $request->no_telfon;
        $pembayaran->status = 'Belum Lunas';
        $pembayaran->metode = $request->metode;
        $pembayaran->reference = $reference;
        $pembayaran->save();

        return redirect('/id/deposit/' . $order_id)->with('success', 'Successfully deposited balance, please make payment');
    }
}
