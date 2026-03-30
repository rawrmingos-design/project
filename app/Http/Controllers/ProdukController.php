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
use App\Services\ProductPricingService;
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
        $pricing = app(ProductPricingService::class);

        $rules = [
            'provider' => 'required|string',
            'kategori' => 'required|string',
        ];

        $messages = [
            'provider.required' => 'Provider is required',
            'kategori.required' => 'Kategori is required.',
        ];

        $validatedData = $request->validate($rules, $messages);

        if ($request->provider == "vip") {
            $data = (new VipResellerController())->services();

            if (($data['result'] ?? false) === true) {
                foreach ($data['data'] as $product) {
                    $kategoriArray = explode(',', $request->kategori);
                    if ($product['status'] === 'available' && in_array($product['game'], $kategoriArray)) {
                        $dataGames = Kategori::where('nama', $product['game'])->first();

                        if ($dataGames) {
                            $layanan = new Layanan();
                            $layanan->kategori_id = $dataGames->id;
                            $layanan->layanan = $product['name'];
                            $layanan->provider_id = $product['code'];
                            $pricing->seedFromBaseCostWithDefaultMarkup($layanan, $product['price']['basic']);
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
                                $pricing->seedFromBaseCostWithDefaultMarkup($layanan, $product['price']);
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
            $responseData = (new BangJeffController())->listVariant($request->kategori);

            if (($responseData['rc'] ?? null) !== null && ($responseData['rc'] !== '00')) {
                return back()->with('error', 'API mengembalikan kesalahan: ' . ($responseData['message'] ?? 'Unknown error'));
            }

            if (isset($responseData['error']) && $responseData['error'] === true) {
                return back()->with('error', 'API mengembalikan kesalahan: ' . ($responseData['message'] ?? 'Unknown error'));
            }

            if (isset($responseData['data']) && is_array($responseData['data'])) {
                foreach ($responseData['data'] as $product) {
                    $isActive = ($product['isActive'] ?? null) === true || strtoupper((string) ($product['status'] ?? '')) === 'ACTIVE';

                    if (! $isActive) {
                        continue;
                    }

                    $dataLayanan = Layanan::all();

                    if ($dataLayanan->where('provider_id', $product['code'])->isNotEmpty()) {
                        return back()->with('error', 'Data Sudah Ditambahkan');
                    }

                    $dataGames = Kategori::where('kode', $request->kategori)->first();

                    if ($dataGames) {
                        $layanan = new Layanan();
                        $layanan->kategori_id = $dataGames->id;
                        $layanan->layanan = $product['name'];
                        $layanan->provider_id = $product['code'];
                        $priceValue = $product['price']['value'] ?? $product['price'] ?? 0;
                        $pricing->seedFromBaseCostWithDefaultMarkup($layanan, $priceValue);
                        $layanan->provider = 'bangjeff';
                        $layanan->catatan = '';
                        $layanan->status = 'available';
                        $layanan->save();
                    }
                }

                return back()->with('success', 'Berhasil menginput layanan');
            } else {
                return back()->with('error', 'Data layanan tidak valid dari API.');
            }
        }  else if ($request->provider == 'digiflazz') {
           $digi = new DigiFlazzController;
           $data = $digi->harga();
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
                            $pricing->seedFromBaseCostWithDefaultMarkup($layanan, $product['price']);
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
                    $pricing->seedFromBaseCostWithDefaultMarkup($layanan, $variation['variation_price']);
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
                    $pricing->seedFromBaseCostWithDefaultMarkup($layanan, $variation['cost_price']);
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
                    $pricing->seedFromBaseCostWithDefaultMarkup($layanan, $variation['price']);
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
                    $pricing->seedFromBaseCostWithDefaultMarkup($layanan, $variation['price']);
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
                    $pricing->seedFromBaseCostWithDefaultMarkup($layanan, $price);
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
                    $pricing->rebaseFromNewBaseCostKeepingMargins($dataProduct, $product['price']);
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
                    $oldHarga = $dataProduct->harga;
                    $oldHargaMember = $dataProduct->harga_member;
                    $oldHargaPlatinum = $dataProduct->harga_platinum;
                    $oldHargaGold = $dataProduct->harga_gold;
                    $pricing->rebaseFromNewBaseCostKeepingMargins($dataProduct, $product['price']);

                    // Update data produk
                    $dataProduct->update([
                        'provider_id' => $product['code'],
                        'harga' => $dataProduct->harga,
                        'harga_member' => $dataProduct->harga_member,
                        'harga_platinum' => $dataProduct->harga_platinum,
                        'harga_gold' => $dataProduct->harga_gold,
                        'profit_member' => $dataProduct->profit_member,
                        'profit_platinum' => $dataProduct->profit_platinum,
                        'profit_gold' => $dataProduct->profit_gold,
                    ]);

                    // Tulis ke file logging.txt untuk debugging
                    $logMessage = "Produk: {$product['code']}, Harga API: {$product['price']}, " .
                                  "Harga Lama Modal: {$oldHarga}, Harga Member Lama: {$oldHargaMember}, " .
                                  "Harga Platinum Lama: {$oldHargaPlatinum}, Harga Gold Lama: {$oldHargaGold}, " .
                                  "Harga Baru Modal: {$dataProduct->harga}, Harga Member Baru: {$dataProduct->harga_member}, " .
                                  "Harga Platinum Baru: {$dataProduct->harga_platinum}, Harga Gold Baru: {$dataProduct->harga_gold}" . PHP_EOL;

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

                                    $pricing->rebaseFromNewBaseCostKeepingMargins($dataProduct, $price);
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
                
                <div class='alert alert-info'>
                    Harga modal setiap produk di kategori terpilih akan dipertahankan. Harga Member / Publik, Platinum, dan Gold akan disusun ulang memakai markup default dari menu Settings.
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Close</button>
                    <button type='submit' class='btn btn-primary'>Terapkan</button>
                </div>
            </form>
    ";

    return $send;        
}

public function patch(Request $request, $id)
{
    $pricing = app(ProductPricingService::class);
    $category_id = $request->category_id;

    Layanan::where('kategori_id', $category_id)
        ->get()
        ->each(function (Layanan $layanan) use ($pricing) {
            $pricing->seedFromBaseCostWithDefaultMarkup($layanan, $layanan->harga);
            $layanan->save();
        });

    $kategori = \DB::table('kategoris')->where('id', $category_id)->value('nama');

    return redirect()->back()->with('success', 'Harga default berhasil diterapkan ulang untuk kategori: ' . $kategori);
}


}
