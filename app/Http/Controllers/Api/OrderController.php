<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiCheckController;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Voucher;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Models\User;
use App\Services\Checkout\CheckoutOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function price(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $role = $user ? $user->role : 'Guest';

        $layanan = Layanan::where('id', $request->nominal)
            ->where('status', 'available')
            ->first();
        if (!$layanan) {
            return response()->json([
                'status' => false,
                'message' => 'Layanan tidak ditemukan atau tidak tersedia.',
                'error_code' => 'SERVICE_NOT_FOUND',
            ], 404);
        }

        $harga = match($role) {
            'Member' => $layanan->harga_member,
            'Platinum' => $layanan->harga_platinum,
            'Gold', 'Admin' => $layanan->harga_gold,
            default => $layanan->harga_member
        };

        if ($layanan->is_flash_sale == 1 && $layanan->expired_flash_sale >= now() && $layanan->stock_flash_sale > 0) {
            $harga = $layanan->harga_flash_sale;
        }

        if (in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'])) {
            $qty = max(1, (int)$request->qty);
            $harga *= $qty;
        }

        $potongan = 0;
        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            if ($voucher && $voucher->isUsable()) {
                $potongan = $harga * ($voucher->promo / 100);
                if ($potongan > $voucher->max_potongan) {
                    $potongan = $voucher->max_potongan;
                }
                $harga -= $potongan;
            }
        }

        $methods = Cache::remember('payment_methods_price_calc_v1:' . \App\Support\PaymentCatalogAccess::currentTenantId(), 3600, function () {
            return app(\App\Services\PaymentMethodCatalogService::class)->getVisibleMethods()->keyBy('code');
        });

        $pointInfo = null;
        if ($user) {
            $pointService = app(\App\Services\PointService::class);
            $redeemInfo = $pointService->calculateMaxRedeemable($harga, $user->point_balance);
            $pointInfo = [
                'balance'      => $user->point_balance,
                'max_points'   => $redeemInfo['max_points'],
                'max_discount' => $redeemInfo['max_discount'],
                'point_value'  => $redeemInfo['point_value'],
            ];
        }

        return response()->json([
            'status'     => true,
            'harga'      => $harga,
            'potongan'   => $potongan,
            'methods'    => $methods,
            'point_info' => $pointInfo,
        ]);
    }

    public function validateVoucher(Request $request)
    {
        $request->validate(['voucher' => 'required', 'service' => 'required|numeric']);
        
        $voucher = Voucher::where('kode', $request->voucher)->first();
        if (!$voucher) return response()->json(['status' => false, 'message' => 'Voucher tidak ditemukan']);
        if ($voucher->stock <= 0) return response()->json(['status' => false, 'message' => 'Voucher sudah habis']);
        if ($voucher->isExpired()) return response()->json(['status' => false, 'message' => 'Voucher sudah kadaluarsa']);

        $user = Auth::guard('sanctum')->user();
        $role = $user ? $user->role : 'Guest';
        $layanan = Layanan::find($request->service);
        
        $harga = match($role) {
            'Member' => $layanan->harga_member,
            'Platinum' => $layanan->harga_platinum,
            'Gold', 'Admin' => $layanan->harga_gold,
            default => $layanan->harga_member
        };

        if ($voucher->mintrx && $harga < $voucher->mintrx) {
            return response()->json(['status' => false, 'message' => 'Minimal transaksi Rp ' . number_format($voucher->mintrx, 0, ',', '.')]);
        }

        return response()->json([
            'status' => true,
            'promo' => $voucher->promo,
            'max_potongan' => $voucher->max_potongan
        ]);
    }

    public function confirm(Request $request)
    {
        $rules = [
            'service' => 'required|numeric',
            'payment_method' => 'required',
            'nomor' => 'required|numeric',
        ];

        if (in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'])) {
             $rules = array_merge($rules, ['nickname_joki' => 'required', 'loginvia_joki' => 'required']);
        } else {
            $rules['uid'] = 'required|max:25';
        }

        $request->validate($rules);

        $item = Layanan::findOrFail($request->service);
        $produk = Kategori::findOrFail($item->kategori_id);
        $user = Auth::guard('sanctum')->user();
        $role = $user ? $user->role : 'Guest';

        $harga = match($role) {
            'Member' => $item->harga_member,
            'Platinum' => $item->harga_platinum,
            'Gold', 'Admin' => $item->harga_gold,
            default => $item->harga_member
        };

        if ($item->is_flash_sale == 1 && $item->expired_flash_sale >= now() && $item->stock_flash_sale > 0) {
            $harga = $item->harga_flash_sale;
        }

        if (in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'])) {
            $harga *= max(1, (int)$request->qty);
        }

        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            if ($voucher && $voucher->isUsable()) {
                $potongan = $harga * ($voucher->promo / 100);
                $harga -= min($potongan, $voucher->max_potongan);
            }
        }

        $username = "Anonim";
        $daftarGameValidasi = ['mobile-legends', 'free-fire', 'valorant', 'genshin-impact', 'pubg-mobile-tp']; 
        
        if (in_array($produk->kode, $daftarGameValidasi)) {
            $apicheck = new ApiCheckController();
            $checkData = $apicheck->check($request->uid, $request->zone, $produk->nama);
            if (isset($checkData['status']['code']) && $checkData['status']['code'] === 200 && !empty($checkData['data']['username'])) {
                $username = $checkData['data']['username'];
            } else {
                 return response()->json(['status' => false, 'message' => 'User ID tidak ditemukan.'], 400);
            }
        }

        $dataMethod = app(\App\Services\PaymentMethodCatalogService::class)->findVisibleByCode((string) $request->payment_method);
        if ($dataMethod) {
            $fee = $dataMethod->fix_fee + ($harga * ($dataMethod->fee_percent / 100));
            $harga += $fee;
        }

        return response()->json([
            'status' => true,
            'data' => [
                'username' => $username,
                'product' => $item->layanan,
                'category' => $produk->nama,
                'price' => $harga,
                'payment_method' => $dataMethod ? $dataMethod->name : $request->payment_method,
                'uid' => $request->uid,
                'zone' => $request->zone,
                'nomor' => $request->nomor
            ]
        ]);
    }

    public function store(Request $request, CheckoutOrderService $checkout)
    {
        $result = $checkout->createFromApi($request, Auth::guard('sanctum')->user());

        return response()->json($result);
    }

    public function show($order_id, CheckoutOrderService $checkout, \App\Tenancy\TenantContext $context)
    {
        $pembelian = Pembelian::query()
            ->where('order_id', $order_id)
            ->where('tenant_id', $context->id())
            ->with('pembayaran')
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => $checkout->statusPayload($pembelian),
        ]);
    }
}
