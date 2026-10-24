<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Voucher;
use App\Models\Pembelian;
use App\Services\CheckId\CheckIdResolver;
use App\Services\Checkout\CheckoutOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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

        // Satu sumber harga: `TenantPricingService::priceForRole()`. Sebelumnya
        // `match($role)` di sini memilih harga jual platform, lalu blok tenant di
        // bawahnya menaruhnya ke `$layanan->harga` yang dibaca
        // `applyToLayanan()` sebagai ANCHOR MODAL — harga jual diperlakukan
        // sebagai modal, lalu dimarkup lagi. Terukur: 116.600 padahal etalase
        // 110.000 untuk produk yang sama.
        $price = app(\App\Tenancy\TenantPricingService::class)->priceForRole(
            $layanan,
            $role,
            app()->bound(\App\Tenancy\TenantContext::class)
                ? app(\App\Tenancy\TenantContext::class)->get()
                : null,
            in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'], true)
                ? max(1, (int) $request->qty)
                : 1,
        );

        $harga = $price['amount'];
        $layanan->modal_harga = $price['modal'];
        $layanan->tenant_modal_harga = $price['modal_tenant'];
        $layanan->tenant_profit = $price['profit'];

        $flashUnit = app(\App\Tenancy\TenantPricingService::class)->flashSalePrice($layanan);
        if ($flashUnit !== null) {
            $flashAmount = $flashUnit * max(1, in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'], true) ? (int) $request->qty : 1);

            if ($price['is_tenant']) {
                $flashAmount = max($flashAmount, $price['modal_tenant']);
            }

            $harga = $flashAmount;
        }

        $potongan = 0;
        if ($request->voucher) {
            // `$layanan->id` diteruskan supaya kupon yang dibuat untuk produk
            // lain tidak ikut memotong di sini.
            $voucher = app(\App\Services\Voucher\VoucherResolver::class)
                ->resolve(\App\Tenancy\TenantContext::class !== '' ? (app()->bound(\App\Tenancy\TenantContext::class) ? app(\App\Tenancy\TenantContext::class)->id() : null) : null, $request->voucher, (int) ($layanan->id ?? 0) ?: null);
            if ($voucher && $voucher->isUsable()) {
                $potongan = $harga * ($voucher->promo / 100);
                if ($voucher->max_potongan > 0 && $potongan > $voucher->max_potongan) {
                    $potongan = $voucher->max_potongan;
                }
                $harga -= $potongan;
            }
        }

        // Lantai harga: kalau diskon membuat harga jatuh di bawah harga jual
        // platform (harga beli tenant), transaksi GAGAL.
        //
        // Sengaja gagal, bukan dinaikkan diam-diam ke lantai: pembeli melihat
        // angka di layar, dan angka itu harus angka yang benar-benar ditagih.
        // Jalur web sudah menolak kasus ini, jadi dulu bot bisa memotong lebih
        // dalam daripada website untuk kupon yang sama.
        $floorViolation = app(\App\Tenancy\TenantPricingService::class)
            ->floorViolation($layanan, $harga, in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'], true) ? max(1, (int) $request->qty) : 1);

        if ($floorViolation !== null) {
            return response()->json([
                'status' => false,
                'harga' => null,
                'potongan' => $potongan,
                'message' => $floorViolation,
                'error_code' => 'VOUCHER_BELOW_TENANT_MARGIN',
            ]);
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

        // Cakupan produk ikut dinilai di sini. Tanpa `layanan_id`, kupon yang
        // dibuat untuk produk lain dinyatakan sah di bot, lalu baru ditolak
        // (atau lebih buruk: tetap memotong) saat checkout — pembeli melihat
        // dua jawaban berbeda untuk kupon yang sama.
        $voucher = app(\App\Services\Voucher\VoucherResolver::class)
            ->resolve(app()->bound(\App\Tenancy\TenantContext::class) ? app(\App\Tenancy\TenantContext::class)->id() : null, $request->voucher, (int) $request->service);
        if (!$voucher) return response()->json(['status' => false, 'message' => 'Voucher tidak ditemukan atau tidak berlaku untuk produk ini']);
        if ($voucher->stock <= 0) return response()->json(['status' => false, 'message' => 'Voucher sudah habis']);
        if ($voucher->isExpired()) return response()->json(['status' => false, 'message' => 'Voucher sudah kadaluarsa']);

        $user = Auth::guard('sanctum')->user();
        $role = $user ? $user->role : 'Guest';
        $layanan = Layanan::find($request->service);
        
        // `mintrx` harus dibandingkan dengan harga yang BENAR-BENAR ditagih.
        // Kalau di sini dipakai harga platform sementara checkout menagih harga
        // tenant, kupon yang sama bisa lolos di bot lalu ditolak (atau lebih
        // buruk, memotong) saat checkout.
        $harga = app(\App\Tenancy\TenantPricingService::class)->priceForRole(
            $layanan,
            $role,
            app()->bound(\App\Tenancy\TenantContext::class)
                ? app(\App\Tenancy\TenantContext::class)->get()
                : null,
            1,
        )['amount'];

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
        $normalizedPhone = trim((string) $request->input('nomor'));

        if ($normalizedPhone === '' && $request->filled('whatsapp')) {
            $normalizedPhone = trim((string) $request->input('whatsapp'));
        }

        $request->merge(['nomor' => $normalizedPhone !== '' ? $normalizedPhone : null]);

        $rules = [
            'service' => 'required|numeric',
            'payment_method' => 'required',
            'nomor' => 'nullable|regex:/^[0-9]{9,16}$/|required_without:email',
            'whatsapp' => 'nullable|regex:/^[0-9]{9,16}$/',
            'email' => 'nullable|email|max:255|required_without:nomor',
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

        // Sama seperti `price()`: satu sumber harga, supaya angka di konfirmasi
        // dan angka di tagihan tidak pernah berbeda.
        $qty = in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'], true)
            ? max(1, (int) $request->qty)
            : 1;

        $price = app(\App\Tenancy\TenantPricingService::class)->priceForRole(
            $item,
            $role,
            app()->bound(\App\Tenancy\TenantContext::class)
                ? app(\App\Tenancy\TenantContext::class)->get()
                : null,
            $qty,
        );

        $harga = $price['amount'];
        $item->modal_harga = $price['modal'];
        $item->tenant_modal_harga = $price['modal_tenant'];
        $item->tenant_profit = $price['profit'];

        $flashUnit = app(\App\Tenancy\TenantPricingService::class)->flashSalePrice($item);
        if ($flashUnit !== null) {
            $flashAmount = $flashUnit * $qty;

            if ($price['is_tenant']) {
                $flashAmount = max($flashAmount, $price['modal_tenant']);
            }

            $harga = $flashAmount;
        }

        if ($request->voucher) {
            // `$item->id` diteruskan supaya cakupan produk kupon ditegakkan.
            $voucher = app(\App\Services\Voucher\VoucherResolver::class)
                ->resolve(\App\Tenancy\TenantContext::class !== '' ? (app()->bound(\App\Tenancy\TenantContext::class) ? app(\App\Tenancy\TenantContext::class)->id() : null) : null, $request->voucher, (int) ($item->id ?? 0) ?: null);
            if ($voucher && $voucher->isUsable()) {
                $potongan = $harga * ($voucher->promo / 100);
                if ($voucher->max_potongan > 0 && $potongan > $voucher->max_potongan) {
                    $potongan = $voucher->max_potongan;
                }
                $harga -= $potongan;
            }
        }

        $username = "Anonim";
        $checkData = app(CheckIdResolver::class)->resolveForCategory(
            $produk,
            (string) $request->uid,
            $request->zone !== null ? (string) $request->zone : null,
            $item,
        );

        if (($checkData['skip_check'] ?? false) !== true) {
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
