<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voucher;
use App\Models\Layanan;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    public function create()
    {
        return view('components.admin.voucher', ['vouchers' => Voucher::orderBy('created_at', 'desc')->get()]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|unique:vouchers,kode',
            'promo' => 'required|numeric|min:0|max:100',
            'stock' => 'required|numeric|min:0',
            'max_potongan' => 'required|numeric|min:0'
        ]);
        
        $voucher = new Voucher();
        $voucher->kode = $request->kode;
        $voucher->promo = $request->promo;
        $voucher->stock = $request->stock;
        $voucher->mintrx = $request->mintrx;
        $voucher->max_potongan = $request->max_potongan;
        $voucher->save();
        
        return back()->with('success', 'Berhasil menambahkan voucher');
    }
    
    public function destroy($id)
    {
        Voucher::where('id', $id)->delete();
        
        return back()->with('success', 'Berhasil menghapus voucher');
    }
    
    public function confirm(Request $request)
    {
        $request->validate([
            'voucher' => 'required'
        ]);
        
        $voucher = Voucher::where('kode', $request->voucher)->first();
        
        if(!$voucher) return response()->json(['status' => false, 'message' => 'Voucher tidak ditemukan'], 404);
        if($voucher->stock == 0) return response()->json(['status' => false, 'message' => 'Voucher sudah tidak valid'], 404);
        
        if(isset($request->service)){
            if(Auth::check()){
                if(Auth::user()->role == "Member"){
                    $service = Layanan::where('id', $request->service)->select('harga_member AS harga')->first();
                }else if(Auth::user()->role == "Platinum"){
                    $service = Layanan::where('id', $request->service)->select('harga_platinum AS harga')->first();
                } else if(Auth::user()->role == "Gold"  || Auth::user()->role == "Admin"){
                    $service = Layanan::where('id', $request->service)->select('harga_gold AS harga')->first();
                }
        }else{
            $service = Layanan::where('id', $request->service)->select('harga_member AS harga')->first();
        }
            
            $potongan = $service->harga * ($voucher->promo / 100);
            $maxPotongan = (float) ($voucher->max_potongan ?? 0);
            
            if($maxPotongan > 0 && $potongan > $maxPotongan){
                $potongan = $maxPotongan;
            }
            
            $service->harga = $service->harga - $potongan;
            
            if ($voucher->mintrx && $voucher->mintrx > $service->harga) {
            return response()->json([
                'status'  => false,
                'title'   => 'Ooops!',
                'message' => 'Minimal Transaksi > Rp ' . number_format($voucher->mintrx, 0, ',', '.'),
            ], 422);
        }
            
            return response()->json([
                'status' => true,
                'message' => 'Voucher ditemukan',
                'harga' => $service->harga
            ]);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'Voucher ditemukan'
        ]);
    }

    public function best(Request $request)
    {
        $request->validate([
            'service' => 'required|integer|exists:layanans,id',
        ]);

        $role = Auth::check() ? Auth::user()->role : 'Guest';

        $service = Layanan::where('id', $request->service)
            ->select('harga_member', 'harga_platinum', 'harga_gold')
            ->first();

        $basePrice = match ($role) {
            'Platinum' => (float) $service->harga_platinum,
            'Gold', 'Admin' => (float) $service->harga_gold,
            default => (float) $service->harga_member,
        };

        $vouchers = Voucher::query()
            ->where('stock', '>', 0)
            ->get()
            ->map(function (Voucher $voucher) use ($basePrice) {
                if ($voucher->mintrx && $basePrice < (float) $voucher->mintrx) {
                    return null;
                }

                $discount = $basePrice * ((float) $voucher->promo / 100);
                $maxDiscount = (float) $voucher->max_potongan;

                if ($maxDiscount > 0 && $discount > $maxDiscount) {
                    $discount = $maxDiscount;
                }

                $discount = max(0, (int) round($discount));
                $finalPrice = max(0, (int) round($basePrice - $discount));

                return [
                    'kode' => $voucher->kode,
                    'promo' => (float) $voucher->promo,
                    'stock' => (int) $voucher->stock,
                    'mintrx' => (int) ($voucher->mintrx ?? 0),
                    'max_potongan' => (int) ($voucher->max_potongan ?? 0),
                    'discount_amount' => $discount,
                    'final_price' => $finalPrice,
                ];
            })
            ->filter()
            ->sortByDesc('discount_amount')
            ->values();

        return response()->json([
            'status' => true,
            'message' => $vouchers->isEmpty() ? 'Tidak ada promo yang tersedia' : 'Promo tersedia',
            'base_price' => (int) round($basePrice),
            'vouchers' => $vouchers,
        ]);
    }
    
    public function show($id)
    {
        $data = Voucher::where('id', $id)->first();
        
        $send = "
                <form action='".route("voucher.detail.update", [$id])."' method='POST'>
                    <input type='hidden' name='_token' value='".csrf_token()."'>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Kode</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='".$data->kode. "' name='kode'>
                        </div>
                    </div>                     
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Potongan</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='".$data->promo. "' name='promo'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Stock</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='".$data->stock. "' name='stock'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Minimal Transaksi</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='".$data->mintrx. "' name='mintrx'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Max Potongan</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='".$data->max_potongan. "' name='max_potongan'>
                        </div>
                    </div>                    
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
        ";

        return $send;          
    }
    
    public function patch(Request $request, $id)
    {
        $request->validate([
            'kode'  => 'required',
            'promo' => 'required|numeric|min:0|max:100',
            'stock' => 'required|numeric|min:0',
            'max_potongan' => 'required|numeric|min:0'
        ]);
        
        Voucher::where('id', $id)->update([
            'kode' => $request->kode,
            'promo' => $request->promo,
            'stock' => $request->stock,
            'mintrx' => $request->mintrx,
            'max_potongan' => $request->max_potongan
        ]);
        
        return back()->with('success', 'Berhasil update kode promo');
    }
}
