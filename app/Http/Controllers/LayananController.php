<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Services\ProductPricingService;
use Illuminate\Http\Request;
use App\Models\Layanan;
use Illuminate\Support\Str;

class LayananController extends Controller
{
    public function create()
    {
        $datas = Layanan::join('kategoris', 'layanans.kategori_id', 'kategoris.id')->orderBy('layanans.created_at', 'desc')
                ->select('layanans.*', 'kategoris.nama AS nama_kategori')->get();

        $kategori = Kategori::get();

        return view('components.admin.layanan', ['datas' => $datas, 'kategoris' => $kategori]);
    }

    public function store(Request $request)
    {
        $pricing = app(ProductPricingService::class);

        $request->validate([
            'nama' => 'required',
            'kategori' => 'required',
            'harga' => 'required|numeric',
            'harga_member' => 'required|numeric',
            'harga_platinum' => 'required|numeric',
            'harga_gold' => 'required|numeric',
            'provider_id' => 'required|unique:layanans,provider_id',
            'provider' => 'required',
        ]);
        
        if ($request->file('product_logo')){
        
            $img = $request->file('product_logo');
            $filename = Str::random('15') . '.' . $img->extension();
            $img->move('assets/product_logo', $filename);
        
        }
        
         if($request->file('banner_flash_sale')){
            $imgfs = $request->file('banner_flash_sale');
            $filenamefs = Str::random('15') . '.' . $imgfs->extension();
            $imgfs->move('assets/banner_flash_sale', $filenamefs);
        }

        $layanan = new Layanan();
        $layanan->kategori_id = $request->kategori;
        $layanan->layanan = $request->nama;
        $layanan->provider_id = $request->provider_id;
        $pricing->applyDirectTierPrices(
            $layanan,
            $request->harga,
            $request->harga_member,
            $request->harga_platinum,
            $request->harga_gold,
        );
        $layanan->provider = $request->provider;
        $layanan->catatan = '';
        $layanan->status = 'available';
        $layanan->product_logo = ($request->file('product_logo') ? '/assets/product_logo/'.$filename : '');
        $layanan->harga_flash_sale = $request->harga_flash_sale;
        $layanan->stock_flash_sale = $request->stock_flash_sale;
        $layanan->is_flash_sale = $request->flash_sale;
        $layanan->judul_flash_sale = $request->judul_flash_sale;
        $layanan->banner_flash_sale = ($request->file('banner_flash_sale') ?  '/assets/banner_flash_sale/'.$filenamefs : '');
        $layanan->expired_flash_sale = date('Y-m-d H:i:s', strtotime($request->expired_flash_sale));
        $layanan->save();

        return back()->with('success', 'Berhasil menginput layanan');
    }

    public function delete($id)
    {
        Layanan::where('id', $id)->delete();
        return back()->with('success', 'Berhasil menghapus layanan');
    }

    public function update($id, $status)
    {
        Layanan::where('id', $id)->update([
            'status' => $status
        ]);
        return back()->with('success', 'Berhasil mengupdate layanan');
    }
    
    public function detail($id)
    {
        $data = Layanan::where('id', $id)->first();
        
        $send = "
                <form action='".route("layanan.detail.update", [$id])."' method='POST' enctype='multipart/form-data'>
                    <input type='hidden' name='_token' value='".csrf_token()."'>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Layanan</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='".$data->layanan. "' name='layanan'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Provider</label>
                        <div class='col-lg-10'>
                            <select class='form-select' name='provider'>
                                <option value='digiflazz' " . ($data->provider == 'digiflazz' ? 'selected' : '') . ">DigiFlazz</option>
                                <option value='vip' " . ($data->provider == 'vip' ? 'selected' : '') . ">VipReseller</option>
                                <option value='apigames' " . ($data->provider == 'apigames' ? 'selected' : '') . ">ApiGames</option>
                                <option value='bangjeff' " . ($data->provider == 'bangjeff' ? 'selected' : '') . ">BangJeff</option>
                                <option value='topupedia' " . ($data->provider == 'topupedia' ? 'selected' : '') . ">TopuPedia</option>
                                <option value='yezzpay' " . ($data->provider == 'yezzpay' ? 'selected' : '') . ">Yezzpay</option>
                                <option value='elitedias' " . ($data->provider == 'elitedias' ? 'selected' : '') . ">Elitedias</option>
                                <option value='gameshop' " . ($data->provider == 'gameshop' ? 'selected' : '') . ">Gameshop</option>
                                <option value='strleyashop' " . ($data->provider == 'strleyashop' ? 'selected' : '') . ">Strleyashop</option>
                                <option value='moogold' " . ($data->provider == 'moogold' ? 'selected' : '') . ">Moogold</option>
                                <option value='digiflazz' " . ($data->provider == 'digiflazz' ? 'selected' : '') . ">Digiflazz</option>
                                <option value='giftskin' " . ($data->provider == 'giftskin' ? 'selected' : '') . ">Gift SKin</option>
                                <option value='vilogml' " . ($data->provider == 'vilogml' ? 'selected' : '') . ">Vilog ML</option>
                                <option value='joki' " . ($data->provider == 'joki' ? 'selected' : '') . ">Joki MLBB</option>
                            </select>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Provider Id</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='" . $data->provider_id . "' name='provider_id'>
                        </div>
                    </div>  
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Harga Modal</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='". $data->harga ."' name='harga'>
                        </div>
                    </div>  
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Harga Member / Publik</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='". $data->harga_member ."' name='harga_member'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Harga Platinum</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='". $data->harga_platinum ."' name='harga_platinum'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Harga Gold</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='". $data->harga_gold ."' name='harga_gold'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label'>Flash Sale?</label>
                        <div class='col-lg-10'>
                            <select class='form-select' name='flash_sale'>
                                <option value='0'>No</option>
                                <option value='1'>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Judul Flash Sale</label>
                        <div class='col-lg-10'>
                            <input type='text' class='form-control' value='". $data->judul_flash_sale ."' name='judul_flash_sale'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Harga Flash Sale</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='". $data->harga_flash_sale ."' name='harga_flash_sale'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Stock Flash Sale</label>
                        <div class='col-lg-10'>
                            <input type='number' class='form-control' value='0' name='stock_flash_sale'>
                        </div>
                    </div>
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Flashsale Berakhir Sampai</label>
                        <div class='col-lg-10'>
                            <input type='datetime-local' class='form-control' value='". $data->expired_flash_sale ."' name='expired_flash_sale' data-provider='flatpickr' data-date-format='d.m.y'
                                data-enable-time>
                        </div>
                    </div>
                   
                    <div class='mb-3 row'>
                        <label class='col-lg-2 col-form-label' for='example-fileinput'>Status</label>
                        <div class='col-lg-10'>
                            <select class='form-control' name='status'>
                                <option value='available'>Available</option>
                                <option value='unavailable'>Unavailable</option>
                            </select>
                        </div>
                    </div>                                        
                    <div class='modal-footer'>
                        <button type='submit' class='btn btn-primary bg-gradient waves-effect waves-light'>Simpan</button>
                        <button type='button' class='btn btn-dark bg-gradient waves-effect waves-light' data-bs-dismiss='modal'>Close</button>
                        
                    </div>
                </form>
        ";

        return $send;        
    }    
        
    
    public function patch(Request $request, $id)
    {
        $pricing = app(ProductPricingService::class);
        
        if($request->file('banner_flash_sale')){
            $imgfs = $request->file('banner_flash_sale');
            $filenamefs = Str::random('15') . '.' . $imgfs->extension();
            $imgfs->move('assets/banner_flash_sale', $filenamefs);
        }
        
          if($request->file('product_logo')){
            $img = $request->file('product_logo');
            $filename = Str::random('15') . '.' . $img->extension();
            $img->move('assets/product_logo', $filename);
        }
        
        $cek = Layanan::where('id', $id)->first();
        $payload = [
            'layanan' => $request->layanan,
            'provider' => $request->provider,
            'provider_id' => $request->provider_id,
            'status' => $request->status,
            'harga_flash_sale' => $request->harga_flash_sale,
            'stock_flash_sale' => $request->stock_flash_sale,
            'is_flash_sale' => $request->flash_sale,
            'judul_flash_sale' => $request->judul_flash_sale,
            'expired_flash_sale' => date('Y-m-d H:i:s', strtotime($request->expired_flash_sale)),
            'banner_flash_sale' => (!$request->file('banner_flash_sale') ? $cek->banner_flash_sale : '/assets/banner_flash_sale/'.$filenamefs),
            'product_logo' =>  (!$request->file('product_logo') ? $cek->product_logo : '/assets/product_logo/'.$filename)
        ];

        $draft = new Layanan($cek->toArray());
        $pricing->applyDirectTierPrices(
            $draft,
            $request->harga,
            $request->harga_member,
            $request->harga_platinum,
            $request->harga_gold,
        );

        $payload['harga'] = $draft->harga;
        $payload['harga_member'] = $draft->harga_member;
        $payload['harga_platinum'] = $draft->harga_platinum;
        $payload['harga_gold'] = $draft->harga_gold;
        $payload['profit_member'] = $draft->profit_member;
        $payload['profit_platinum'] = $draft->profit_platinum;
        $payload['profit_gold'] = $draft->profit_gold;

        Layanan::where('id', $id)->update($payload);
        
           
        return back()->with('success', 'Berhasil update layanan');        
    }
}
