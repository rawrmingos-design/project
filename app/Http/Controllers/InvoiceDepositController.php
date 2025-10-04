<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\Berita;
use Illuminate\Support\Carbon;
use App\Models\Layanan;
use App\Models\Kategori;
use App\Http\Controllers\TriPayController;
use App\Http\Controllers\TriPayCallbackController;

class InvoiceDepositController extends Controller
{
    public function create($order)
    {
        $data = Deposit::where('pembayarans.order_id', $order)->join('pembayarans', 'deposits.order_id', 'pembayarans.order_id')
                ->select('pembayarans.status AS status_pembayaran', 'pembayarans.metode AS metode_pembayaran', 'pembayarans.no_pembayaran', 'pembayarans.reference', 'deposits.order_id AS id_pembelian', 'deposits.created_at AS created_at', 'deposits.updated_at AS updated_at',
                        'pembayarans.harga AS harga_pembayaran', 'pembayarans.reference', 'pembayarans.status AS status_pembayaran')
                ->first();
        
        $expired = Carbon::create($data->created_at)->addDay();
        
        $iPayData = array();
        
        // if($data->metode_pembayaran != "OVO" && $data->metode_pembayaran != "GOPAY" && $data->metode_pembayaran != "QRIS" && $data->metode_pembayaran != "BCA" && $data->metode_pembayaran != "BNI" && $data->metode_pembayaran != "MANDIRI"
        //  && $data->metode_pembayaran != "BRI" && $data->metode_pembayaran != "CIMB" && $data->metode_pembayaran != "BSI" && $data->metode_pembayaran != "BMI" && $data->metode_pembayaran != "PERMATA" && $data->metode_pembayaran != "INDOMARET" && $data->metode_pembayaran != "ALFAMART"){
        //     $ipay = new iPaymuController();
        //     $iPayData = $ipay->checkTransaction($data->reference);
        // }
        
      
        
    
        
        return view('template.invoicedeposit', [
        'data' => $data,
        'expired' => $expired,
        'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
        
    }
}
