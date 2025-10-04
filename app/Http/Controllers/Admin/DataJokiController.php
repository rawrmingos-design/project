<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;



class DataJokiController extends Controller
{
    
    public function dataJoki()
    {
                
        $data = \DB::table('data_joki')->join('pembayarans','data_joki.order_id','pembayarans.order_id')->join('pembelians','data_joki.order_id','pembelians.order_id')
                                       ->select('data_joki.order_id AS order_id','data_joki.email_joki AS email','data_joki.password_joki AS password','data_joki.loginvia_joki AS loginvia',
                                                'data_joki.nickname_joki AS nickname','data_joki.request_joki AS request','data_joki.catatan_joki AS catatan', 'data_joki.qty AS qty' ,'data_joki.status_joki AS status_joki',
                                                'pembayarans.status AS status_pembayaran','pembelians.layanan AS layanan','data_joki.id AS id','pembayarans.no_pembeli AS nomor'
                                               )
                                        ->get();    
                
        return view('components.admin.datajoki',compact('data'));
    }
    
    
   public function statusJoki(Request $request, $order_id, $status)
{
    \DB::beginTransaction();

    try {
        // Update status joki di tabel data_joki
        \DB::table('data_joki')->where('order_id', $order_id)->update(['status_joki' => $status]);

        // Perbarui status di tabel pembelian
        \DB::table('pembelians')->where('order_id', $order_id)->update(['status' => $status]);

        \DB::commit();

        return back()->with('success', 'Status joki berhasil diperbarui.');
    } catch (\Exception $e) {
        \DB::rollback();
        return back()->with('error', 'Gagal memperbarui status joki: ' . $e->getMessage());
    }
}

    
     public function hapusJoki(Request $request,$id)
    {
        \DB::table('data_joki')->where('id',$id)->delete();
        
        return back();
    }
    
    
}