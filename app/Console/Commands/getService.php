<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kategori;
use App\Models\Layanan;
use Illuminate\Support\Facades\Log;
// use App\Http\Controllers\DigiFlazzController;
use App\Http\Controllers\provider\moogold\MoogoldController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class getService extends Command
{
    protected $signature = 'Service';
    protected $description = 'Command to fetch services from DigiFlazz and MooGold';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // $this->getDigiFlazzServices();
        $this->getMoogoldServices();
    }

    // private function getDigiFlazzServices()
    // {
    //     $digiFlazz = new DigiFlazzController;
    //     $res = $digiFlazz->harga();

    //     if (isset($res['data']) && is_array($res['data'])) {
    //         foreach (Kategori::get() as $kategori) {
    //             foreach ($res['data'] as $data) {
    //                 if (is_array($data) && isset($data['buyer_product_status']) && isset($data['brand'])) {
    //                     if ($data['buyer_product_status'] == true && Str::upper($data['brand']) == Str::upper($kategori->nama)) {
    //                         if (in_array($data['category'], ["Games", "E-Money", "Pulsa", "Voucher", "PLN", "Data", "Gas", "TV"])) {
    //                             $processedData = [
    //                                 'id' => $data['buyer_sku_code'],
    //                                 'nama_layanan' => $data['product_name'],
    //                                 'harga' => $data['price'] ?? $data['harga'],
    //                                 'status' => $data['buyer_product_status'],
    //                             ];
    //                             $this->processService($processedData, $kategori, 'digiflazz');
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //     } else {
    //         Log::error('Invalid or empty response data from DigiFlazz API.', ['response' => $res]);
    //     }
    // }

    private function getMoogoldServices()
    {
        $moogold = new MoogoldController();
        $categories = $moogold->categories();
    
        if (!is_array($categories) || empty($categories)) {
            Log::error('Moogold API returned invalid categories', ['response' => $categories]);
            return;
        }
    
        $kategoriList = Kategori::pluck('id', 'nama')->toArray();
    
        foreach ($categories as $category) {
            if (!isset($kategoriList[$category['post_title']])) {
                // Log::warning('Category not found for MooGold: ' . $category['post_title']);
                continue;
            }
            $kategori = Kategori::where('nama', $category['post_title'])->first();
    
            if (!$kategori) {
                Log::warning('Kategori not found for MooGold category: ' . $category['post_title']);
                continue;
            }
            $products = $moogold->products($category['ID']);
    
            if (!isset($products['Variation']) || !is_array($products['Variation'])) {
                // Log::error('Moogold API returned invalid product variations', ['category' => $category, 'response' => $products]);
                continue;
            }
    
            foreach ($products['Variation'] as $variation) {
                $processedData = [
                    'id' => $category['ID'] . ',' . $variation['variation_id'],
                    'nama_layanan' => $variation['variation_name'],
                    'harga' => $variation['variation_price'],
                    'status' => 'active',
                ];
                $this->processService($processedData, $kategori, 'moogold');
            }
        }
    }


    private function processService($data, $kategori, $provider)
    {
        if (is_object($kategori)) {
            $kategoriId = $kategori->id;
        } else {
            Log::error('Unexpected type for $kategori, expected object.', ['kategori' => $kategori]);
            return; 
        }

        $cekgame = Layanan::where('provider_id', $data['id'])->first();

        $cekprofits = \DB::table('layanans')
                        ->where('kategori_id', $kategoriId)
                        ->select('profit', 'profit_member', 'profit_platinum', 'profit_gold')
                        ->first();

        $harga = isset($data['harga']) && is_numeric($data['harga']) ? $data['harga'] : 0;
        
        $profit = $cekprofits->profit ?? 5;
        $member = $cekprofits->profit_member ?? 5;
        $platinum = $cekprofits->profit_platinum ?? 4;
        $gold = $cekprofits->profit_gold ?? 3;

        if (!$cekgame) {
            $layanan = new Layanan();
            $layanan->fill([
                'layanan' => $data['nama_layanan'],
                'kategori_id' => $kategoriId,
                'provider_id' => $data['id'],
                'harga' => $harga + ($harga * $profit / 100),
                'harga_member' => $harga + ($harga * $member / 100),
                'harga_platinum' => $harga + ($harga * $platinum / 100),
                'harga_gold' => $harga + ($harga * $gold / 100),
                'profit' => $profit,
                'profit_member' => $member,
                'profit_platinum' => $platinum,
                'profit_gold' => $gold,
                'catatan' => '',
                'status' => $data['status'] ? "available" : "unavailable",
                'provider' => $provider,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $layanan->save();
        } else {
            $cekgame->update([
                'harga' => $harga + ($harga * $profit / 100),
                'harga_member' => $harga + ($harga * $member / 100),
                'harga_platinum' => $harga + ($harga * $platinum / 100),
                'harga_gold' => $harga + ($harga * $gold / 100),
                'profit' => $profit,
                'profit_member' => $member,
                'profit_platinum' => $platinum,
                'profit_gold' => $gold,
                'status' => $data['status'] ? "available" : "unavailable",
            ]);
        }
    }
}
