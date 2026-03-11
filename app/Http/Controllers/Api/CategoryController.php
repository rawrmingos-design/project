<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Paket;
use App\Models\PaketLayanan;
use App\Models\Method;
;use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function show($kode)
    {
        $kategori = Kategori::where('kode', $kode)->firstOrFail();
        
        $role = Auth::guard('sanctum')->check() ? Auth::guard('sanctum')->user()->role : 'Guest';
        $cacheKey = "api_category_show:{$kategori->kode}:{$role}";
        $ttl = 300; // 5 minutes

        $data = Cache::remember($cacheKey, $ttl, function () use ($kategori, $role) {
            
            if (in_array($kategori->tipe, ['game', 'voucher', 'pulsa', 'app', 'populer'])) {
                $categoryData = Kategori::where('kode', $kategori->kode)
                    ->join('custom_inputs', 'kategoris.id', 'custom_inputs.kategori_id')
                    ->select('custom_inputs.field_1 AS field_1', 'custom_inputs.field_2 AS field_2', 'custom_inputs.field_select_title AS field_select_title', 'custom_inputs.field_select AS field_select', 'nama', 'sub_nama', 'server_id', 'thumbnail', 'kategoris.id AS id', 'kode',  'deskripsi_game', 'deskripsi_field', 'banner', 'tipe', 'meta_title', 'meta_description', 'schema_markup')
                    ->first();
            } else {
                $categoryData = Kategori::where('kode', $kategori->kode)
                    ->select('nama', 'sub_nama', 'server_id', 'thumbnail', 'kategoris.id AS id', 'kode', 'deskripsi_game', 'deskripsi_field', 'banner', 'tipe', 'meta_title', 'meta_description', 'schema_markup')
                    ->first();
            }
            
            if (!$categoryData) return null;

            // Layanan Query based on Role
            $query = Layanan::where('kategori_id', $categoryData->id)->where('status', 'available');
            
            if ($role == "Member") {
                $query->select('id', 'layanan', 'harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo');
            } else if ($role == "Platinum") {
                $query->select('id', 'layanan', 'harga_platinum AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo');
            } else if ($role == "Gold" || $role == "Admin") {
                $query->select('id', 'layanan', 'harga_gold AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo');
            } else { // Guest
                $query->select('id', 'layanan', 'product_logo', 'harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale');
            }
            $products = $query->orderBy('harga', 'asc')->get();

            $ratings = DB::table('ratings')
                ->join('pembelians', 'ratings.rating_id', '=', 'pembelians.order_id')
                ->join('pembayarans', 'ratings.rating_id', '=', 'pembayarans.order_id')
                ->select('ratings.bintang', 'ratings.comment', 'ratings.id', 'ratings.created_at', 'pembelians.username', 'pembelians.layanan', 'pembayarans.no_pembeli')
                ->orderByDesc('ratings.id')
                ->limit(10)
                ->get();

            // Pakets Logic
            $pakets = [];
            foreach (Paket::all() as $paket) {
                $layananIds = $paket->layanan->pluck('id')->toArray();
                $layananData = Layanan::whereIn('id', $layananIds)
                    ->where('kategori_id', $categoryData->id)
                    ->where(function ($query) use ($role) {
                        if ($role == 'Member') $query->where('harga_member', '>', 0);
                        elseif ($role == 'Platinum') $query->where('harga_platinum', '>', 0);
                        elseif ($role == 'Gold' || $role == 'Admin') $query->where('harga_gold', '>', 0);
                        else $query->where('harga', '>', 0);
                    })->get();

                $l = [];
                foreach ($layananData as $lyn) {
                    $paketLayanan = PaketLayanan::where('paket_id', $paket->id)->where('layanan_id', $lyn->id)->first();
                    if ($paketLayanan) {
                        if ($role == 'Member') $harga = $lyn->harga_member;
                        elseif ($role == 'Platinum') $harga = $lyn->harga_platinum;
                        elseif ($role == 'Gold' || $role == 'Admin') $harga = $lyn->harga_gold;
                        else $harga = $lyn->harga;

                        $l[] = [
                            'id' => $lyn->id,
                            'layanan' => $lyn->layanan,
                            'product_logo' => $paketLayanan->product_logo,
                            'harga' => $harga,
                            'is_flash_sale' => $lyn->is_flash_sale,
                            'expired_flash_sale' => $lyn->expired_flash_sale,
                            'harga_flash_sale' => $lyn->harga_flash_sale,
                            'updated_at' => $lyn->updated_at,
                        ];
                    }
                }
                if (!empty($l)) {
                    $pakets[] = ['nama' => $paket->nama, 'layanan' => $l];
                }
            }

            return [
                'category' => $categoryData,
                'products' => $products,
                'ratings' => $ratings,
                'pakets' => $pakets,
            ];
        });

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (empty($query)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $categories = Kategori::where('nama', 'LIKE', '%' . $query . '%')
            ->where('status', 'active')
            ->limit(6)
            ->get(['id', 'nama', 'kode', 'thumbnail']);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}
