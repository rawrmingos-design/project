<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\DigiFlazzController;
use App\Http\Controllers\provider\MoogoldController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\TopupediaController;
use App\Http\Controllers\provider\BangJeffController;
use App\Libraries\Provider\ElitediasProvider;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;
use App\Models\Layanan;
use App\Models\Kategori;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class ProdukController extends Controller
{
    public function get($provider = null)
    {

        
        $kategori = Kategori::get();

        return view('components.admin.produk.get', [
            'title' => 'Get Produk',
            'kategoris' => $kategori
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'provider' => 'required|string',
            'kategori' => 'required|string',
            'profit' => 'required|numeric',
            'profit_member' => 'required|numeric',
            'profit_platinum' => 'required|numeric',
            'profit_gold' => 'required|numeric',
        ];

        $messages = [
            'provider.required' => 'Provider is required',
            'kategori.required' => 'Kategori is required.',
            'profit.required' => 'Profit is required.',
            'profit.numeric' => 'Profit must be a number.',
            'profit_member.required' => 'Profit Member is required.',
            'profit_member.numeric' => 'Profit Member must be a number.',
            'profit_platinum.required' => 'Profit Platinum is required.',
            'profit_platinum.numeric' => 'Profit Platinum must be a number.',
            'profit_gold.required' => 'Profit Gold is required.',
            'profit_gold.numeric' => 'Profit Gold must be a number.',
        ];

        $validatedData = $request->validate($rules, $messages);

        if ($request->provider == "vip") {

          $api = \DB::table('setting_webs')->where('id',1)->first();

            $sign = md5($api->vip_apiid.$api->vip_apikey);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://vip-reseller.co.id/api/game-feature');
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "key=".$api->vip_apikey."&sign=$sign&type=services");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $data = json_decode(curl_exec($ch), true);

            if ($data['result'] === true) {
                foreach ($data['data'] as $product) {
                    $kategoriArray = explode(',', $request->kategori);
                    if ($product['status'] === 'available' && in_array($product['game'], $kategoriArray)) {
                        $dataGames = Kategori::where('nama', $product['game'])->first();

                        if ($dataGames) {
                            $layanan = new Layanan();
                            $layanan->kategori_id = $dataGames->id;
                            $layanan->layanan = $product['name'];
                            $layanan->provider_id = $product['code'];
                            $layanan->harga = $product['price']['basic'] + ($product['price']['basic'] * $request->profit / 100);
                            $layanan->harga_member = $product['price']['basic'] + ($product['price']['basic'] * $request->profit_member / 100);
                            $layanan->harga_platinum = $product['price']['basic'] + ($product['price']['basic'] * $request->profit_platinum / 100);
                            $layanan->harga_gold = $product['price']['basic'] + ($product['price']['basic'] * $request->profit_gold / 100);
                            $layanan->profit = $request->profit;
                            $layanan->profit_member = $request->profit_member;
                            $layanan->profit_platinum = $request->profit_platinum;
                            $layanan->profit_gold = $request->profit_gold;
                            $layanan->provider = 'vip';
                            $layanan->catatan = '';
                            $layanan->status = 'available';
                            $layanan->save();
                        }
                    }
                }
                return back()->with('success', 'Berhasil menginput layanan');
            } else {
                echo "API Error: " . $data['message'];
            }
            
        } else if ($request->provider == 'topupedia') {
            $url = 'https://api.topupedia.com/api/v3/variant';
            $your_api_key = '';

            $data = array(
                'code' => $request->kategori,
            );

            $data_json = json_encode($data);
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $your_api_key,
            );
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);

            $buka = fopen(storage_path('logging.txt'), 'w');

            fwrite($buka, 'test' . $response);

            if ($response === false) {
                return back()->with('error', 'Gagal mengambil data dari API.');
            }

            $responseData = json_decode($response, true);


            if ($responseData === null) {
                return back()->with('error', 'Gagal menguraikan respons JSON dari API.');
            }

            if (isset($responseData['error']) && $responseData['error'] === true) {
                return back()->with('error', 'API mengembalikan kesalahan: ' . $responseData['message']);
            }

            if (isset($responseData['data']) && is_array($responseData['data'])) {
                foreach ($responseData['data'] as $product) {
                    $kategoriArray = explode(',', $request->kategori);
                    if ($product['isActive'] === true) {
                        $dataLayanan =  Layanan::all();
                        
                        if($dataLayanan->where('provider_id', $product['code'])->isNotEmpty()){
                                return back()->with('error', 'Data Sudah Ditambahkan');    
                        }
                        
                        $dataGames = Kategori::where('kode', $request->kategori)->first();
                        
                         $buka= fopen(storage_path('logging.txt'), 'w');
                    
                         fwrite($buka,'test '. json_encode($dataGames));

                        if ($dataGames) {
                                $layanan = new Layanan();
                                $layanan->kategori_id = $dataGames->id;
                                $layanan->layanan = $product['name'];
                                $layanan->provider_id = $product['code'];
                                $layanan->harga = $product['price'] + ($product['price'] * $request->profit / 100);
                                $layanan->harga_member = $product['price'] + ($product['price'] * $request->profit_member / 100);
                                $layanan->harga_platinum = $product['price'] + ($product['price'] * $request->profit_platinum / 100);
                                $layanan->harga_gold = $product['price'] + ($product['price'] * $request->profit_gold / 100);
                                $layanan->profit = $request->profit;
                                $layanan->profit_member = $request->profit_member;
                                $layanan->profit_platinum = $request->profit_platinum;
                                $layanan->profit_gold = $request->profit_gold;
                                $layanan->provider = 'topupedia';
                                $layanan->catatan = '';
                                $layanan->status = 'available';
                                $layanan->save();
                        }
                    }
                }
                
                return back()->with('success', 'Berhasil menginput layanan');
            } else {
                return back()->with('error', 'Data layanan tidak valid dari API.');
            }
          }else if ($request->provider == 'bangjeff') {
            $url = 'https://api.bangjeff.com/api/v3/variant';
            $your_api_key = '';

            $data = array(
                'code' => $request->kategori,
            );

            $data_json = json_encode($data);
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $your_api_key,
            );
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);

            $buka = fopen(storage_path('logging.txt'), 'w');

            fwrite($buka, 'test' . $response);

            if ($response === false) {
                return back()->with('error', 'Gagal mengambil data dari API.');
            }

            $responseData = json_decode($response, true);


            if ($responseData === null) {
                return back()->with('error', 'Gagal menguraikan respons JSON dari API.');
            }

            if (isset($responseData['error']) && $responseData['error'] === true) {
                return back()->with('error', 'API mengembalikan kesalahan: ' . $responseData['message']);
            }

            if (isset($responseData['data']) && is_array($responseData['data'])) {
                foreach ($responseData['data'] as $product) {
                    $kategoriArray = explode(',', $request->kategori);
                    if ($product['isActive'] === true) {
                        $dataLayanan =  Layanan::all();
                        
                        if($dataLayanan->where('provider_id', $product['code'])->isNotEmpty()){
                                return back()->with('error', 'Data Sudah Ditambahkan');    
                        }
                        
                        $dataGames = Kategori::where('kode', $request->kategori)->first();
                        
                         $buka= fopen(storage_path('logging.txt'), 'w');
                    
                         fwrite($buka,'test '. json_encode($dataGames));

                        if ($dataGames) {
                                $layanan = new Layanan();
                                $layanan->kategori_id = $dataGames->id;
                                $layanan->layanan = $product['name'];
                                $layanan->provider_id = $product['code'];
                                $layanan->harga = $product['price'] + ($product['price'] * $request->profit / 100);
                                $layanan->harga_member = $product['price'] + ($product['price'] * $request->profit_member / 100);
                                $layanan->harga_platinum = $product['price'] + ($product['price'] * $request->profit_platinum / 100);
                                $layanan->harga_gold = $product['price'] + ($product['price'] * $request->profit_gold / 100);
                                $layanan->profit = $request->profit;
                                $layanan->profit_member = $request->profit_member;
                                $layanan->profit_platinum = $request->profit_platinum;
                                $layanan->profit_gold = $request->profit_gold;
                                $layanan->provider = 'topupedia';
                                $layanan->catatan = '';
                                $layanan->status = 'available';
                                $layanan->save();
                        }
                    }
                }
                
                return back()->with('success', 'Berhasil menginput layanan');
            } else {
                return back()->with('error', 'Data layanan tidak valid dari API.');
            }
        }  else if ($request->provider == 'digiflazz') {
           $digi = new DigiFlazzController;
           $data = $digi->harga();
           $profit = \DB::table('setting_webs')->where('id',1)->first();

            if ($data && isset($data['data'])) {
                foreach ($data['data'] as $product) {
                    $kategoriArray = explode(',', $request->kategori);
                    if ($product['buyer_product_status'] == true && in_array($product['brand'], $kategoriArray)) {
                        $dataGames = Kategori::where('nama', $product['brand'])->first();

                        if ($dataGames) {
                            $layanan = new Layanan();
                            $layanan->kategori_id = $dataGames->id;
                            $layanan->layanan = $product['product_name'];
                            $layanan->provider_id = $product['buyer_sku_code'];
                            $layanan->harga = $product['price'] + ($product['price'] * $request->profit / 100);
                            $layanan->harga_member = $product['price'] + ($product['price'] * $request->profit_member / 100);
                            $layanan->harga_platinum = $product['price'] + ($product['price'] * $request->profit_platinum / 100);
                            $layanan->harga_gold = $product['price'] + ($product['price'] * $request->profit_gold / 100);
                            $layanan->profit = $request->profit;
                            $layanan->profit_member = $request->profit_member;
                            $layanan->profit_platinum = $request->profit_platinum;
                            $layanan->profit_gold = $request->profit_gold;
                            $layanan->provider = 'digiflazz';
                            $layanan->catatan = '';
                            $layanan->status = 'available';
                            $layanan->save();
                        }
                    }

                }
                return back()->with('success', 'Berhasil menginput layanan');
            } else {
                return back()->with('error', 'Data layanan tidak valid dari API.');
            }
        }  else if ($request->provider == 'moogold') {
            
            $moo = new MoogoldController;
        
            $categories = $moo->categories();
            $selectedCategory = collect($categories)->firstWhere('post_title', $request->kategori);
        
            if (!$selectedCategory) {
                Log::warning('Kategori tidak ditemukan di API Moogold.', ['requested_name' => $request->kategori]);
                return back()->with('error', 'Kategori tidak ditemukan di API. Silakan coba nama kategori lain.');
            }
        
            $data = $moo->products($selectedCategory['ID']);
        
            if ($data && isset($data['Product_Name']) && isset($data['Variation'])) {
                foreach ($data['Variation'] as $variation) {
                    $dataGames = Kategori::firstOrCreate(
                        ['nama' => $data['Product_Name']],
                        [
                            'url' => Str::slug($data['Product_Name']),
                            'status' => 'active',
                        ]
                    );
                    $layanan = new Layanan();
                    $layanan->kategori_id = $dataGames->id;
                    $layanan->layanan = $variation['variation_name'];
                    $layanan->provider_id = $selectedCategory['ID'] . ',' . $variation['variation_id'];
                    $layanan->harga = $variation['variation_price'] + ($variation['variation_price'] * $request->profit / 100);
                    $layanan->harga_member = $variation['variation_price'] + ($variation['variation_price'] * $request->profit_member / 100);
                    $layanan->harga_platinum = $variation['variation_price'] + ($variation['variation_price'] * $request->profit_platinum / 100);
                    $layanan->harga_gold = $variation['variation_price'] + ($variation['variation_price'] * $request->profit_gold / 100);
                    $layanan->profit = $request->profit;
                    $layanan->profit_member = $request->profit_member;
                    $layanan->profit_platinum = $request->profit_platinum;
                    $layanan->profit_gold = $request->profit_gold;
                    $layanan->provider = 'moogold';
                    $layanan->catatan = '';
                    $layanan->status = 'available';
                    $layanan->save();
                }
        
                return back()->with('success', 'Berhasil menginput layanan Moogold.');
            } else {
                Log::warning('Data produk tidak valid dari API Moogold.', ['data' => $data]);
                return back()->with('error', 'Data produk tidak valid dari API Moogold.');
            }
        }  else if ($request->provider == 'gameshop') {
            
            $gameshop = new GameShopProvider;
        
            $categories = $gameshop->categories();
            $selectedCategory = collect($categories['data']['list'])->firstWhere('title', $request->kategoriSelect);
            
        
            if (!$selectedCategory) {
                Log::warning('Kategori tidak ditemukan di API gameshop.', ['requested_name' => $request->kategoriSelect]);
                return back()->with('error', 'Kategori tidak ditemukan di API. Silakan coba nama kategori lain.');
            }
        
            $data = $gameshop->products($selectedCategory['id']);
        
            if ($data && isset($data['data'])) {
                foreach ($data['data'] as $variation) {
                    $dataGames = Kategori::firstOrCreate(
                        ['nama' => $request->kategori],
                        [
                            'url' => Str::slug($request->kategori),
                            'status' => 'active',
                        ]
                    );
                    $skuName = preg_replace('/[\\\"\\[\\]]/', '', $variation['sku_names']);

                    $layanan = Layanan::where('provider_id', $variation['goods_id'] . '-' . $variation['id'])->first();
                    if ($layanan == null) $layanan = new Layanan();
                    $layanan->kategori_id = $dataGames->id;
                    $layanan->layanan = $layanan->layanan ?? $skuName;
                    $layanan->provider_id = $variation['goods_id'] . '-' . $variation['id'];
                    $layanan->harga = $variation['cost_price'] + ($variation['cost_price'] * $request->profit / 100);
                    $layanan->harga_member = $variation['cost_price'] + ($variation['cost_price'] * $request->profit_member / 100);
                    $layanan->harga_platinum = $variation['cost_price'] + ($variation['cost_price'] * $request->profit_platinum / 100);
                    $layanan->harga_gold = $variation['cost_price'] + ($variation['cost_price'] * $request->profit_gold / 100);
                    $layanan->profit = $request->profit;
                    $layanan->profit_member = $request->profit_member;
                    $layanan->profit_platinum = $request->profit_platinum;
                    $layanan->profit_gold = $request->profit_gold;
                    $layanan->provider = 'gameshop';
                    $layanan->catatan = '';
                    $layanan->status = 'available';
                    $layanan->save();
                }
        
                return back()->with('success', 'Berhasil menginput layanan Gameshop.');
            } else {
                Log::warning('Data produk tidak valid dari API Gameshop.', ['data' => $data]);
                return back()->with('error', 'Data produk tidak valid dari API Gameshop.');
            }
        }  else if ($request->provider == 'strleyashop') {
            
            $strleyashop = new StrleyaShopProvider;
        
            $data = $strleyashop->products($request->kategoriSelect);
            if ($data && isset($data) && is_array($data)) {
                foreach ($data as $variation) {
                    $dataGames = Kategori::firstOrCreate(
                        ['nama' => $request->kategori],
                        [
                            'url' => Str::slug($request->kategori),
                            'status' => 'active',
                        ]
                    );

                    $layanan = Layanan::where('provider_id', $request->kategoriSelect . '-' . $variation['id'])->first();
                    if ($layanan == null) $layanan = new Layanan();
                    $layanan->kategori_id = $dataGames->id;
                    $layanan->layanan = $layanan->layanan ?? $variation['id'];
                    $layanan->provider_id = $request->kategoriSelect . '-' . $variation['id'];
                    $layanan->harga = $variation['price'] + ($variation['price'] * $request->profit / 100);
                    $layanan->harga_member = $variation['price'] + ($variation['price'] * $request->profit_member / 100);
                    $layanan->harga_platinum = $variation['price'] + ($variation['price'] * $request->profit_platinum / 100);
                    $layanan->harga_gold = $variation['price'] + ($variation['price'] * $request->profit_gold / 100);
                    $layanan->profit = $request->profit;
                    $layanan->profit_member = $request->profit_member;
                    $layanan->profit_platinum = $request->profit_platinum;
                    $layanan->profit_gold = $request->profit_gold;
                    $layanan->provider = 'strleyashop';
                    $layanan->catatan = '';
                    $layanan->status = 'available';
                    $layanan->save();
                }
        
                return back()->with('success', 'Berhasil menginput layanan StrleyaShop.');
            } else {
                Log::warning('Data produk tidak valid dari API StrleyaShop.', ['data' => $data]);
                return back()->with('error', 'Data produk tidak valid dari API StrleyaShop.');
            }
        }  else if ($request->provider == 'yezzpay') {
            
            $yezzpay = new YezzpayProvider;
        
        
            $data = $yezzpay->products($request->kategoriSelect);
        
            if ($data && isset($data['data'])) {
                foreach ($data['data'] as $variation) {
                    $dataGames = Kategori::firstOrCreate(
                        ['nama' => $request->kategori],
                        [
                            'url' => Str::slug($request->kategori),
                            'status' => 'active',
                        ]
                    );
                    $skuName = $variation['name'];

                    $layanan = Layanan::where('provider_id', $variation['code'])->where('provider', 'yezzpay')->first();
                    if ($layanan == null) $layanan = new Layanan();
                    $layanan->kategori_id = $dataGames->id;
                    $layanan->layanan = $layanan->layanan ?? $skuName;
                    $layanan->provider_id = $variation['code'];
                    $layanan->harga = $variation['price'] + ($variation['price'] * $request->profit / 100);
                    $layanan->harga_member = $variation['price'] + ($variation['price'] * $request->profit_member / 100);
                    $layanan->harga_platinum = $variation['price'] + ($variation['price'] * $request->profit_platinum / 100);
                    $layanan->harga_gold = $variation['price'] + ($variation['price'] * $request->profit_gold / 100);
                    $layanan->profit = $request->profit;
                    $layanan->profit_member = $request->profit_member;
                    $layanan->profit_platinum = $request->profit_platinum;
                    $layanan->profit_gold = $request->profit_gold;
                    $layanan->provider = 'yezzpay';
                    $layanan->catatan = '';
                    $layanan->status = $variation['status'] == '1' ? 'available' : 'unavailable';
                    $layanan->save();
                }
        
                return back()->with('success', 'Berhasil menginput layanan Yezzpay.');
            } else {
                Log::warning('Data produk tidak valid dari API Yezzpay.', ['data' => $data]);
                return back()->with('error', 'Data produk tidak valid dari API Yezzpay.');
            }
        }  else if ($request->provider == 'elitedias') {
            
            $elitedias = new ElitediasProvider;
        
            $data = $elitedias->products($request->kategoriSelect);

            if (isset($data['code'])) return back()->with('error', 'Kategori tidak ditemukan di API. Silakan coba nama kategori lain.');
        
            if ($data && isset($data) && count($data) > 0) {
                foreach ($data as $var => $variation) {
                    $dataGames = Kategori::firstOrCreate(
                        ['nama' => $request->kategori],
                        [
                            'url' => Str::slug($request->kategori),
                            'status' => 'active',
                        ]
                    );
                    $skuName = $var;
                    $curlCurrency = Http::get('https://open.er-api.com/v6/latest/SGD');
                    $currencyRate = $curlCurrency->json()['rates']['MYR'];
                    $price = $variation * $currencyRate;

                    $layanan = Layanan::where('provider_id', $request->kategoriSelect . '-' . $var)->first();
                    if ($layanan == null) $layanan = new Layanan();
                    $layanan->kategori_id = $dataGames->id;
                    $layanan->layanan = $layanan->layanan ?? $skuName;
                    $layanan->provider_id = $request->kategoriSelect . '-' . $var;
                    $layanan->harga = $price + ($price * $request->profit / 100);
                    $layanan->harga_member = $price + ($price * $request->profit_member / 100);
                    $layanan->harga_platinum = $price + ($price * $request->profit_platinum / 100);
                    $layanan->harga_gold = $price + ($price * $request->profit_gold / 100);
                    $layanan->profit = $request->profit;
                    $layanan->profit_member = $request->profit_member;
                    $layanan->profit_platinum = $request->profit_platinum;
                    $layanan->profit_gold = $request->profit_gold;
                    $layanan->provider = 'elitedias';
                    $layanan->catatan = '';
                    $layanan->status = 'available';
                    $layanan->save();
                }
        
                return back()->with('success', 'Berhasil menginput layanan Elitedias.');
            } else {
                Log::warning('Data produk tidak valid dari API Elitedias.', ['data' => $data]);
                return back()->with('error', 'Data produk tidak valid dari API Elitedias.');
            }
        }


    }
    
 public function sync(){
    $digi = new DigiFlazzController;
    $data = $digi->harga();
        
    if ($data && isset($data['data'])) {
        foreach ($data['data'] as $product) {
            if ($product['buyer_product_status'] == true) {
                $dataGames = Kategori::where('nama', $product['brand'])->first();
                $dataProduct = Layanan::where('provider_id', $product['buyer_sku_code'])->first();

                if ($dataGames && $dataProduct) {
                    $profit = $dataProduct->profit;
                    $profit_member = $dataProduct->profit_member;
                    $profit_platinum = $dataProduct->profit_platinum;
                    $profit_gold = $dataProduct->profit_gold;

                    $harga = $product['price'];
                    $dataProduct->harga = $harga + ($harga * $profit / 100);
                    $dataProduct->harga_member = $harga + ($harga * $profit_member / 100);
                    $dataProduct->harga_platinum = $harga + ($harga * $profit_platinum / 100);
                    $dataProduct->harga_gold = $harga + ($harga * $profit_gold / 100);
                    $dataProduct->save();
                }
            }
        }
        return back()->with('success', 'Berhasil Update Harga produk Digiflazz!');
    } else {
        return back()->with('error', 'Data Layanan Tidak Valid Dari API!');
    }
}


public function synctopupedia(Request $request) {
    $aoshi = new TopupediaController;
    $data = $aoshi->listVariant($request->kategori);
    
    // Mengosongkan file logging.txt sebelum menulis informasi baru
    file_put_contents(storage_path('logging.txt'), '');

    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $product) {
            if ($product['isActive'] === true) {
                $dataGames = Kategori::where('kode', $request->kategori)->first();
                $dataProduct = Layanan::where('provider_id', $product['code'])->first();

                if ($dataProduct) {
                    // Ambil profit dari Layanan
                    $profit = $dataProduct->profit;
                    $profit_member = $dataProduct->profit_member;
                    $profit_platinum = $dataProduct->profit_platinum;
                    $profit_gold = $dataProduct->profit_gold;

                    // Hitung harga baru berdasarkan price dari API dan profit dari Layanan
                    $newHarga = $product['price'] + ($product['price'] * $profit / 100);
                    $newHargaMember = $product['price'] + ($product['price'] * $profit_member / 100);
                    $newHargaPlatinum = $product['price'] + ($product['price'] * $profit_platinum / 100);
                    $newHargaGold = $product['price'] + ($product['price'] * $profit_gold / 100);

                    // Update data produk
                    $dataProduct->update([
                        'provider_id' => $product['code'],
                        'harga' => $newHarga,
                        'harga_member' => $newHargaMember,
                        'harga_platinum' => $newHargaPlatinum,
                        'harga_gold' => $newHargaGold,
                    ]);

                    // Tulis ke file logging.txt untuk debugging
                    $logMessage = "Produk: {$product['code']}, Harga API: {$product['price']}, " .
                                  "Profit: {$profit}%, Harga Lama: {$dataProduct->harga}, " .
                                  "Harga Baru: {$newHarga}, Harga Member Baru: {$newHargaMember}, " .
                                  "Harga Platinum Baru: {$newHargaPlatinum}, Harga Gold Baru: {$newHargaGold}" . PHP_EOL;

                    file_put_contents(storage_path('logging.txt'), $logMessage, FILE_APPEND);
                } else {
                    // Jika $dataProduct null, tulis ke logging.txt
                    $logMessage = "dataProduct is null for product code: {$product['code']}" . PHP_EOL;
                    file_put_contents(storage_path('logging.txt'), $logMessage, FILE_APPEND);
                }
            }
        }
        return back()->with('success', 'Berhasil Update Harga Produk API Topupedia');
    } else {
        return redirect('/layanan')->with('error', 'Data Layanan Tidak Valid Dari API!');
    }
}

public function syncmoogold()
{
    $moogold = new MoogoldController;

    // Ambil daftar provider_id dari database
    $providerIds = Layanan::pluck('provider_id')->toArray();

    // Ambil daftar kategori dari API, tapi batasi kategori yang relevan
    $categories = $moogold->categories();

    if (!empty($categories)) {
        foreach ($categories as $category) {
            if (isset($category['ID'])) {
                $categoryId = $category['ID']; // ID kategori

                // Cek apakah kategori ini relevan dengan provider_id yang ada di database
                // Format provider_id: categoryId,variationId
                $relevantProviderIds = array_filter($providerIds, function ($providerId) use ($categoryId) {
                    // Cek jika categoryId ada dalam provider_id
                    return strpos($providerId, (string)$categoryId) !== false;
                });

                // Jika tidak ada provider_id yang relevan, skip kategori ini
                if (empty($relevantProviderIds)) {
                    continue;
                }

                // Ambil produk berdasarkan kategori
                $data = $moogold->products($categoryId);

                if ($data && isset($data['Variation'])) {
                    foreach ($data['Variation'] as $variation) {
                        if (isset($variation['variation_price_idr']) && $variation['variation_price_idr'] > 0) {
                            // Format provider_id
                            $providerId = $categoryId . ',' . $variation['variation_id'];

                            // Cek apakah provider_id tersedia di database
                            if (in_array($providerId, $relevantProviderIds)) {
                                // Ambil produk dari database
                                $dataProduct = Layanan::where('provider_id', $providerId)->first();
                                if ($dataProduct) {
                                    $price = $variation['variation_price_idr'];

                                    // Hitung harga dengan profit
                                    $profit = $dataProduct->profit;
                                    $profit_member = $dataProduct->profit_member;
                                    $profit_platinum = $dataProduct->profit_platinum;
                                    $profit_gold = $dataProduct->profit_gold;

                                    // Perbarui harga
                                    $dataProduct->harga = $price + ($price * $profit / 100);
                                    $dataProduct->harga_member = $price + ($price * $profit_member / 100);
                                    $dataProduct->harga_platinum = $price + ($price * $profit_platinum / 100);
                                    $dataProduct->harga_gold = $price + ($price * $profit_gold / 100);
                                    $dataProduct->save();
                                }
                            }
                        }
                    }
                }
            }
        }

        return back()->with('success', 'Berhasil Update Harga produk Moogold!');
    }

    return back()->with('error', 'Data Kategori Tidak Valid Dari API!');
}


public function detail($id)
{
    $categories = \DB::table('kategoris')->select('id', 'nama')->get();

    $send = "
            <form action='".route("detail.produk.get.update", [$id])."' method='POST' enctype='multipart/form-data'>
                <input type='hidden' name='_token' value='".csrf_token()."'>
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label' for='category-select'>Kategori</label>
                    <div class='col-lg-10'>
                        <select class='form-control' id='category-select' name='category_id'>
                            <option value=''>Pilih Kategori</option>";

    foreach ($categories as $category) {
        $send .= "<option value='".$category->id."'>".$category->nama."</option>";
    }

    $send .= "        </select>
                    </div>
                </div>
                
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label' for='profit'>Profit Public</label>
                    <div class='col-lg-10'>
                        <input type='text' class='form-control' name='profit' required>
                    </div>
                </div>
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label' for='profit_member'>Profit Member</label>
                    <div class='col-lg-10'>
                        <input type='text' class='form-control' name='profit_member' required>
                    </div>
                </div>
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label' for='profit_platinum'>Profit Platinum</label>
                    <div class='col-lg-10'>
                        <input type='text' class='form-control' name='profit_platinum' required>
                    </div>
                </div>
                <div class='mb-3 row'>
                    <label class='col-lg-2 col-form-label' for='profit_gold'>Profit Gold</label>
                    <div class='col-lg-10'>
                        <input type='text' class='form-control' name='profit_gold' required>
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
    $category_id = $request->category_id;

    \DB::table('layanans')
        ->where('kategori_id', $category_id)
        ->update([
            'profit' => $request->profit,
            'profit_member' => $request->profit_member,
            'profit_platinum' => $request->profit_platinum,
            'profit_gold' => $request->profit_gold,
        ]);

    $kategori = \DB::table('kategoris')->where('id', $category_id)->value('nama');

    return redirect()->back()->with('success', 'Profit berhasil diperbarui untuk kategori: ' . $kategori. ' Silahkan klik sync untuk merubah harga');
}


}