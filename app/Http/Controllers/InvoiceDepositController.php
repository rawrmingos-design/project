<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\Berita;
use App\Models\Pembayaran;
use Illuminate\Support\Carbon;
use App\Models\Layanan;
use App\Models\Kategori;
use App\Http\Controllers\TriPayController;
use App\Http\Controllers\TriPayCallbackController;

class InvoiceDepositController extends Controller
{
    public function create($order)
    {
        $payment = Pembayaran::query()
            ->where('order_id', $order)
            ->latest('id')
            ->first();

        abort_if(! $payment, 404);

        $payment->syncExpiredStatus();

        $data = Deposit::where('pembayarans.order_id', $order)->join('pembayarans', 'deposits.order_id', 'pembayarans.order_id')
                ->select('pembayarans.status AS status_pembayaran', 'pembayarans.metode AS metode_pembayaran', 'pembayarans.no_pembayaran', 'pembayarans.reference', 'pembayarans.expired_at', 'deposits.order_id AS id_pembelian', 'deposits.created_at AS created_at', 'deposits.updated_at AS updated_at',
                        'pembayarans.harga AS harga_pembayaran', 'pembayarans.reference', 'pembayarans.status AS status_pembayaran')
                ->orderByDesc('pembayarans.id')
                ->first();
        
        $expired = $data->expired_at
            ? Carbon::parse($data->expired_at)
            : Carbon::create($data->created_at)->addHours(3);
        
        $iPayData = array();
        
        // if($data->metode_pembayaran != "OVO" && $data->metode_pembayaran != "GOPAY" && $data->metode_pembayaran != "QRIS" && $data->metode_pembayaran != "BCA" && $data->metode_pembayaran != "BNI" && $data->metode_pembayaran != "MANDIRI"
        //  && $data->metode_pembayaran != "BRI" && $data->metode_pembayaran != "CIMB" && $data->metode_pembayaran != "BSI" && $data->metode_pembayaran != "BMI" && $data->metode_pembayaran != "PERMATA" && $data->metode_pembayaran != "INDOMARET" && $data->metode_pembayaran != "ALFAMART"){
        //     $ipay = new iPaymuController();
        //     $iPayData = $ipay->checkTransaction($data->reference);
        // }
        
      
        
    
        
        return view('template.invoicedeposit', [
        'data' => $data,
        'expired' => $expired,
        'expiredIso' => $expired->toIso8601String(),
        'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
        
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

        $data = Deposit::where('pembayarans.order_id', $order)
            ->join('pembayarans', 'deposits.order_id', '=', 'pembayarans.order_id')
            ->select('pembayarans.status AS status_pembayaran', 'deposits.status AS status_deposit')
            ->orderByDesc('pembayarans.id')
            ->first();

        if ($data) {
            return response()->json([
                'success' => true,
                'status_pembayaran' => $data->status_pembayaran,
                'status_deposit' => $data->status_deposit
            ]);
        }

        return response()->json(['success' => false], 404);
    }
}
