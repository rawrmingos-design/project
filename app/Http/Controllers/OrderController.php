<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Voucher;
use App\Models\Pembelian;
use App\Models\Paket;
use App\Models\PaketLayanan;
use App\Models\User;
use App\Models\Method;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DigiFlazzController;
use App\Services\CheckId\CheckIdResolver;
use App\Http\Controllers\TokoPayController;
use App\Http\Controllers\TriPayController;
use App\Services\Payments\DuitkuInvoiceService;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\TopupediaController;
use App\Http\Controllers\provider\MoogoldController;
use App\Http\Controllers\Public\TransactionLookupPageController;
use App\Jobs\PollSufPaymentStatusJob;
use App\Services\Providers\SufPaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\PublicOrderPushNotificationService;
use App\Support\GtmDataLayerBuilder;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;
use App\Libraries\Provider\ElitediasProvider;
use App\Support\CustomInputDefaults;
use App\Services\PaymentDisplayCategoryService;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantPricingService;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    private const ORDER_IDEMPOTENCY_LOCK_SECONDS = 180;
    private const ORDER_IDEMPOTENCY_RESULT_SECONDS = 600;

    public function create(Kategori $kategori)
    {
        app(CustomInputDefaults::class)->ensureExists($kategori);

        $role = Auth::check() ? Auth::user()->role : 'Guest';
        $tenantId = app(\App\Tenancy\TenantContext::class)->id() ?? 'main';
        $cacheKey = "order_page:{$tenantId}:{$kategori->kode}:{$role}";
        $ttl = 300; // 5 minutes

        // Cache the entire data preparation for the view
        $viewData = Cache::remember($cacheKey, $ttl, function () use ($kategori, $role) {
            $data = Kategori::where('kode', $kategori->kode)
                ->leftJoin('custom_inputs', 'kategoris.id', '=', 'custom_inputs.kategori_id')
                ->select(
                    'custom_inputs.field_1 AS field_1',
                    'custom_inputs.field_2 AS field_2',
                    'custom_inputs.field_select_title AS field_select_title',
                    'custom_inputs.field_select AS field_select',
                    'nama',
                    'sub_nama',
                    'server_id',
                    'require_user_id',
                    'thumbnail',
                    'kategoris.id AS id',
                    'kode',
                    'deskripsi_game',
                    'deskripsi_field',
                    'banner',
                    'tipe',
                    'meta_title',
                    'meta_description',
                    'schema_markup'
                )
                ->first();
            
            if ($data == null) return null;

            // Layanan Query based on Role
            $query = Layanan::where('kategori_id', $data->id)->where('status', 'available');
            
            if ($role == "Member") {
                $query->select('id', 'layanan', 'harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo');
            } else if ($role == "Platinum") {
                $query->select('id', 'layanan', 'harga_platinum AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo');
            } else if ($role == "Gold" || $role == "Admin") {
                $query->select('id', 'layanan', 'harga_gold AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo');
            } else { // Guest
                $query->select('id', 'layanan', 'product_logo', 'harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale');
            }
            $layanan = $query->orderBy('harga', 'asc')->get();

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
                    ->where('kategori_id', $data->id)
                    ->where(function ($query) use ($role) {
                        if ($role == 'Member') $query->where('harga_member', '>', 0);
                        elseif ($role == 'Platinum') $query->where('harga_platinum', '>', 0);
                        elseif ($role == 'Gold' || $role == 'Admin') $query->where('harga_gold', '>', 0);
                        else $query->where('harga_member', '>', 0);
                    })->get();

                $l = [];
                foreach ($layananData as $lyn) {
                    $paketLayanan = PaketLayanan::where('paket_id', $paket->id)->where('layanan_id', $lyn->id)->first();
                    if ($paketLayanan) {
                        if ($role == 'Member') $harga = $lyn->harga_member;
                        elseif ($role == 'Platinum') $harga = $lyn->harga_platinum;
                        elseif ($role == 'Gold' || $role == 'Admin') $harga = $lyn->harga_gold;
                        else $harga = $lyn->harga_member;

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

            $flashsale = Layanan::join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
                ->select('kategoris.thumbnail AS gmr_thumb', 'kategoris.kode AS kode_game', 'layanans.*')
                ->where('layanans.is_flash_sale', 1)
                ->where('layanans.expired_flash_sale', '>=', now())
                ->where('layanans.stock_flash_sale', '>', 0)
                ->get();

            return compact('data', 'layanan', 'ratings', 'pakets', 'flashsale');
        });

        if ($viewData == null) {
            abort(404);
        }

        extract($viewData); // Extract vars: $data, $layanan, $ratings, $pakets, $flashsale

        // Extract SEO Data from Kategori ($data)
        $appName = config('app.name');
        $title = !empty($data->meta_title) ? $data->meta_title : "Top Up {$data->nama} Murah - {$appName}";
        $meta_description = !empty($data->meta_description) ? $data->meta_description : "Top up {$data->nama} termurah dan terpercaya di {$appName}. Proses instan, layanan 24 jam.";
        $keywords = "topup {$data->nama}, beli {$data->nama}, top up {$data->nama} murah, agen {$data->nama}, {$appName}";
        $schema_markup = $data->schema_markup;

        // Payment methods are cached separately in global cache or view composer usually, but here we can cache it short term
        $pay_method = Cache::remember('payment_methods_all_v1:' . \App\Support\PaymentCatalogAccess::currentTenantId(), 300, function() {
            return app(\App\Services\PaymentMethodCatalogService::class)->getVisibleMethods();
        });

        // Dynamic payment display categories (from PaymentDisplayCategoryService)
        $paymentCategories = app(PaymentDisplayCategoryService::class)->getCategoriesForOrderPage();

        $gtmBuilder = app(GtmDataLayerBuilder::class);
        $gtmOrderItemCatalog = $gtmBuilder->buildCatalog($layanan, $data);
        $gtmViewItemPayload = null;

        if (! empty($gtmOrderItemCatalog)) {
            $firstTrackedItem = array_values($gtmOrderItemCatalog)[0];
            $gtmViewItemPayload = $gtmBuilder->buildViewItemPayload($firstTrackedItem);
        }

        $gtmPaymentMethodCatalog = $gtmBuilder->buildPaymentMethods($pay_method);

        return view('template.order', [
            'title' => $title,
            'meta_description' => $meta_description,
            'keywords' => $keywords,
            'schema_markup' => $schema_markup,
            'kategori' => $data,
            'nominal' => $layanan,
            'pakets' => $pakets,
            'harga' => $layanan,
            'ratings' => $ratings,
            'flashsale' => $flashsale,
            'pay_method' => $pay_method,
            'paymentCategories' => $paymentCategories,
            'gtmViewItemPayload' => $gtmViewItemPayload,
            'gtmOrderItemCatalog' => $gtmOrderItemCatalog,
            'gtmPaymentMethodCatalog' => $gtmPaymentMethodCatalog,
        ]);
    }

    private function hasTenantContext(): bool
    {
        return app()->bound(TenantContext::class) && app(TenantContext::class)->has();
    }

    private function tenantPriceQuantity(Request $request): int
    {
        if (! in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'], true)) {
            return 1;
        }

        return max(1, (int) $request->qty);
    }

    private function applyTenantPricing($layanan, Request $request): void
    {
        if (! $this->hasTenantContext() || ! $layanan instanceof Layanan) {
            return;
        }

        app(TenantPricingService::class)->applyToLayanan(
            $layanan,
            app(TenantContext::class)->get(),
            $this->tenantPriceQuantity($request),
        );
    }

    public function price(Request $request)
    {
        $query = Layanan::where('id', $request->nominal)
            ->where('status', 'available');

        if ($this->hasTenantContext()) {
            $query->select('id', 'harga_gold', 'harga_gold AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale');
        } elseif (Auth::check()) {
            if (Auth::user()->role == "Member") {
                $query->select('harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale');
            } elseif (Auth::user()->role == "Platinum") {
                $query->select('harga_platinum AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale');
            } elseif (Auth::user()->role == "Gold" || Auth::user()->role == "Admin") {
                $query->select('harga_gold AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale');
            }
        } else {
            $query->select('harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale');
        }

        $data = $query->first();

        if (! $data) {
            return response()->json([
                'status' => false,
                'message' => 'Layanan tidak ditemukan atau tidak tersedia.',
                'error_code' => 'SERVICE_NOT_FOUND',
            ], 404);
        }

        if ($data->is_flash_sale == 1 && $data->expired_flash_sale >= date('Y-m-d H:i:s') && $data->stock_flash_sale > 0) {
            $data->harga = $data->harga_flash_sale;
        }

        if (in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'])) {
            $qty = $request->qty;
            if ($qty <= 0) {
                $qty = 1;
            }

            $data->harga *= $qty;
        }

        $this->applyTenantPricing($data, $request);

        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            $data->harga -= $this->calculateVoucherDiscountAmount($data->harga, $voucher);
        }

        // OPTIMIZATION: Cache methods query for 60 minutes to reduce DB load
        $methods = \Illuminate\Support\Facades\Cache::remember('payment_methods_price_calc_v1:' . \App\Support\PaymentCatalogAccess::currentTenantId(), 60 * 60, function () {
            return app(\App\Services\PaymentMethodCatalogService::class)->getVisibleMethods()->keyBy('code');
        });

        $selectedMethod = null;
        if ($request->filled('payment_method')) {
            $selectedMethod = Method::select('code', 'payment', 'fee_percent', 'fix_fee', 'min_pembelian', 'max_pembelian')
                ->where('code', $request->payment_method)
                ->first();
        }

        $methodPrices = $methods->mapWithKeys(function (Method $method) use ($data, $request): array {
            $baseAmount = max(0, (int) round((float) $data->harga));
            $feeAmount = $this->calculateMethodFeeAmount($baseAmount, $method);
            $amountBeforePoint = $baseAmount + $feeAmount;
            $pointUsage = $this->resolvePointUsage($amountBeforePoint, (int) $request->use_point);

            return [
                $method->code => [
                    'base_amount' => $baseAmount,
                    'fee_amount' => $feeAmount,
                    'amount_before_point' => $amountBeforePoint,
                    'point_discount' => $pointUsage['discount'],
                    'final_price' => max(1000, $amountBeforePoint - $pointUsage['discount']),
                ],
            ];
        });

        $amountBeforePoint = (int) round($data->harga + $this->calculateMethodFeeAmount($data->harga, $selectedMethod));
        $pointUsage = $this->resolvePointUsage($amountBeforePoint, (int) $request->use_point);
        $selectedFinalPrice = max(1000, $amountBeforePoint - $pointUsage['discount']);

        if ($selectedMethod && $methodPrices->has($selectedMethod->code)) {
            $selectedPrice = $methodPrices->get($selectedMethod->code);
            $selectedFinalPrice = $selectedPrice['final_price'];
            $pointUsage['discount'] = $selectedPrice['point_discount'];
        }

        return response()->json([
            'status'         => true,
            'harga'          => $data->harga,
            'methods'        => $methods,
            'method_prices'  => $methodPrices,
            'point_info'     => $pointUsage['point_info'],
            'point_discount' => $pointUsage['discount'],
            'selected_final_price' => $selectedFinalPrice,
        ]);
    }

    private function calculateMethodFeeAmount(float|int $basePrice, $method): int
    {
        if (!$method) {
            return 0;
        }

        return (int) round(($method->fix_fee ?? 0) + ($basePrice * (($method->fee_percent ?? 0) / 100)));
    }

    private function validatePaymentMethodAmount(int $amount, $method): ?string
    {
        if (!$method) {
            return null;
        }

        $minimum = (int) ($method->min_pembelian ?? 0);
        $maximum = (int) ($method->max_pembelian ?? 0);

        if ($minimum > 0 && $amount < $minimum) {
            return 'Minimal pembayaran untuk metode ini adalah Rp ' . number_format($minimum, 0, ',', '.');
        }

        if ($maximum > 0 && $amount > $maximum) {
            return 'Maksimal pembayaran untuk metode ini adalah Rp ' . number_format($maximum, 0, ',', '.');
        }

        return null;
    }

    private function restoreCheckoutReservations(
        bool $flashSaleReserved,
        bool $voucherReserved,
        bool $pointsReserved,
        bool $orderCreated,
        Request $request,
        $dataLayanan,
        int $usedPoints,
        string $orderId
    ): void {
        if ($orderCreated) {
            return;
        }

        if ($flashSaleReserved) {
            Layanan::where('id', $request->service)->increment('stock_flash_sale');
        }

        if ($voucherReserved && filled($request->voucher)) {
            Voucher::where('kode', $request->voucher)->increment('stock');
        }

        if ($pointsReserved && Auth::check()) {
            app(\App\Services\PointService::class)->refundPoints(
                Auth::user(),
                $usedPoints,
                $orderId,
                $dataLayanan->layanan
            );
        }
    }

    private function calculateVoucherDiscountAmount(float|int $basePrice, ?Voucher $voucher): int
    {
        if (!$voucher || ! $voucher->isUsable()) {
            return 0;
        }

        $discount = (float) $basePrice * ((float) $voucher->promo / 100);
        $maxDiscount = (float) ($voucher->max_potongan ?? 0);

        if ($maxDiscount > 0 && $discount > $maxDiscount) {
            $discount = $maxDiscount;
        }

        return max(0, (int) round($discount));
    }

    private function resolvePointUsage(int $amountBeforePoint, int $requestedPoints = 0): array
    {
        if (!Auth::check()) {
            return [
                'point_info' => null,
                'used_points' => 0,
                'discount' => 0,
            ];
        }

        $pointService = app(\App\Services\PointService::class);
        $user = Auth::user();
        $redeemInfo = $pointService->calculateMaxRedeemable($amountBeforePoint, (int) $user->point_balance);
        $pointInfo = [
            'balance' => (int) $user->point_balance,
            'max_points' => (int) $redeemInfo['max_points'],
            'max_discount' => (int) $redeemInfo['max_discount'],
            'point_value' => (int) $redeemInfo['point_value'],
        ];

        if ($requestedPoints <= 0) {
            return [
                'point_info' => $pointInfo,
                'used_points' => 0,
                'discount' => 0,
            ];
        }

        $pointsToUse = min($requestedPoints, $redeemInfo['max_points']);
        $discount = $pointsToUse > 0
            ? (int) ($pointsToUse * $redeemInfo['point_value'])
            : 0;

        return [
            'point_info' => $pointInfo,
            'used_points' => (int) $pointsToUse,
            'discount' => $discount,
        ];
    }

    private function resolveGatewayRequestAmount(int $targetAmount, $method): int
    {
        $targetAmount = max(1000, (int) round($targetAmount));

        if (!$method || ($method->payment ?? null) !== 'tripay') {
            return $targetAmount;
        }

        try {
            $candidateAmount = $targetAmount;
            $tripay = app(TriPayController::class);

            for ($i = 0; $i < 5; $i++) {
                $cacheKey = sprintf('tripay_customer_fee:%s:%d', $method->code, $candidateAmount);
                $customerFee = Cache::remember($cacheKey, 300, function () use ($candidateAmount, $tripay, $method) {
                    return (int) round($tripay->customerFee($candidateAmount, $method->code));
                });

                if ($customerFee <= 0) {
                    return $targetAmount;
                }

                $nextAmount = max(1000, $targetAmount - $customerFee);
                if (abs($nextAmount - $candidateAmount) <= 1) {
                    return $nextAmount;
                }

                $candidateAmount = $nextAmount;
            }

            return $candidateAmount;
        } catch (\Throwable $e) {
            Log::warning('Tripay request amount resolver failed', [
                'method' => $method->code ?? null,
                'amount' => $targetAmount,
                'error' => $e->getMessage(),
            ]);

            return $targetAmount;
        }
    }


    private function orderErrorResponse(string $message, string $errorCode = 'ORDER_ERROR', int $statusCode = 200, array $extra = [])
    {
        return response()->json(array_merge([
            'status' => false,
            'data' => $message,
            'message' => $message,
            'error_code' => $errorCode,
        ], $extra), $statusCode);
    }

    private function orderSuccessResponse(Request $request, string $orderId, array $extra = [])
    {
        TransactionLookupPageController::rememberRecentOrderId($request, $orderId);

        return response()->json(array_merge([
            'status' => true,
            'order_id' => $orderId,
            'message' => 'Order berhasil dibuat.',
            'error_code' => null,
        ], $extra));
    }

    private function buildOrderIdempotencyKeys(Request $request): array
    {
        $fingerprint = $this->buildOrderIdempotencyFingerprint($request);

        return [
            'lock' => 'order:idempotency:lock:' . $fingerprint,
            'result' => 'order:idempotency:result:' . $fingerprint,
        ];
    }

    private function buildOrderIdempotencyFingerprint(Request $request): string
    {
        $identity = Auth::check()
            ? 'auth:' . Auth::id()
            : 'session:' . $request->session()->getId();

        $providedIdempotencyKey = trim((string) ($request->header('X-Idempotency-Key') ?: $request->input('idempotency_key')));
        if ($providedIdempotencyKey !== '') {
            $sanitizedKey = preg_replace('/[^a-zA-Z0-9_.:-]/', '', $providedIdempotencyKey);
            if (!empty($sanitizedKey)) {
                return hash('sha256', $identity . ':client:' . Str::limit($sanitizedKey, 120, ''));
            }
        }

        $payload = [
            'service' => (string) $request->input('service'),
            'payment_method' => (string) $request->input('payment_method'),
            'nomor' => (string) $request->input('nomor'),
            'uid' => (string) $request->input('uid'),
            'zone' => (string) $request->input('zone'),
            'ktg_tipe' => (string) $request->input('ktg_tipe'),
            'qty' => (string) $request->input('qty'),
            'voucher' => (string) $request->input('voucher'),
            'use_point' => (string) $request->input('use_point', '0'),
            'email' => (string) $request->input('email'),
        ];

        return hash('sha256', $identity . ':' . json_encode($payload));
    }

    private function resolveSelectedMethod(string $methodCode)
    {
        $normalizedCode = trim($methodCode);
        $method = Method::query()
            ->where('code', $normalizedCode)
            ->first();

        if ($method) {
            return $method;
        }

        if (Str::upper($normalizedCode) === 'SALDO') {
            return (object) [
                'id' => null,
                'name' => 'Saldo Akun',
                'payment' => 'manual',
                'tipe' => 'balance',
                'code' => 'SALDO',
                'fee_percent' => 0,
                'fix_fee' => 0,
                'min_pembelian' => null,
                'max_pembelian' => null,
            ];
        }

        return null;
    }

    private function validateOrderRequest(Request $request, bool $forConfirmation = false): void
    {
        $normalizedPhone = $this->normalizeOrderContactPhone($request);
        $request->merge(['nomor' => $normalizedPhone !== '' ? $normalizedPhone : null]);

        $rules = [
            'service' => 'required|integer|exists:layanans,id',
            'payment_method' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    unset($attribute);

                    $code = trim((string) $value);
                    if (Str::upper($code) === 'SALDO') {
                        // Reject SALDO selection for unauthenticated users
                        if (!Auth::check()) {
                            $fail('SALDO hanya bisa digunakan setelah login.');
                        }
                        return;
                    }

                    if (!app(\App\Services\PaymentMethodCatalogService::class)->findVisibleByCode($code)) {
                        $fail('The selected payment method is invalid or unavailable.');
                    }
                },
            ],
            'nomor' => ['nullable', 'regex:/^[0-9]{9,16}$/', 'required_without:email'],
            'voucher' => 'nullable|string|max:100',
            'ktg_tipe' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|required_without:nomor',
            'use_point' => 'nullable|integer|min:0',
            'idempotency_key' => 'nullable|string|max:120',
        ];

        $serviceCategoryCode = Layanan::query()
            ->join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
            ->where('layanans.id', $request->service)
            ->value('kategoris.kode');
        $isRobloxViaLogin = $serviceCategoryCode === 'roblox-via-login';

        if ($isRobloxViaLogin) {
            $rules['uid'] = 'required|string|max:255';
            $rules['zone'] = 'required|string|max:255';
        }

        $isJokiGendong = $request->ktg_tipe === 'jokigendong';
        $isJokiMode = in_array($request->ktg_tipe, ['joki', 'vilogml'], true);

        if ($isJokiGendong) {
            $rules += [
                'nickname_joki' => 'required|string|max:255',
                'tglmain_joki' => 'required|string|max:255',
                'jambooking_joki' => 'required|string|max:255',
                'loginvia_joki' => 'required',
                'catatan_joki' => 'required',
            ];
        } elseif ($isJokiMode) {
            $serviceCategoryCode = Layanan::query()
                ->join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
                ->where('layanans.id', $request->service)
                ->value('kategoris.kode');
            $isRobloxViaLogin = $serviceCategoryCode === 'roblox-via-login';

            $rules += [
                'email_joki' => 'required|string|max:255',
                'password_joki' => 'required|string|max:255',
                'qty' => ($forConfirmation ? 'nullable' : 'required') . '|integer|min:1|max:30',
            ];

            if (! $isRobloxViaLogin) {
                $rules += [
                    'loginvia_joki' => 'required|string|max:255',
                    'nickname_joki' => 'required|string|max:255',
                    'request_joki' => 'required|string|max:255',
                    'catatan_joki' => 'required|string|max:255',
                ];
            }
        } else {
            $requireUserId = true;
            $layanan = Layanan::query()
                ->select('kategori_id')
                ->find($request->service);

            if ($layanan && $layanan->kategori_id) {
                $kategori = Kategori::query()
                    ->select('require_user_id')
                    ->find($layanan->kategori_id);

                if ($kategori !== null) {
                    $requireUserId = (bool) $kategori->require_user_id;
                }
            }

            $rules['uid'] = $requireUserId ? 'required|string|max:50' : 'nullable|string|max:50';
        }

        $request->validate($rules);
    }

    public function confirm(Request $request)
    {
        $this->validateOrderRequest($request, true);

        $item = Layanan::where('id', $request->service)->first();
        $produk = Kategori::where('id', $item->kategori_id)->first();

        // cek data
        if ($this->hasTenantContext()) {
            $dataLayanan = Layanan::where('id', $request->service)->select('id', 'harga_gold', 'harga_gold AS harga', 'kategori_id', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
        } elseif (Auth::check()) {
            if (Auth::user()->role == "Member") {
                $dataLayanan = Layanan::where('id', $request->service)->select('harga_member AS harga', 'kategori_id', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
            } else if (Auth::user()->role == "Platinum") {
                $dataLayanan = Layanan::where('id', $request->service)->select('harga_platinum AS harga', 'kategori_id', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
            } else if (Auth::user()->role == "Gold" || Auth::user()->role == "Admin") {
                $dataLayanan = Layanan::where('id', $request->service)->select('harga_gold AS harga', 'kategori_id', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
            }
        } else {
            $dataLayanan = Layanan::where('id', $request->service)->select('harga_member AS harga', 'kategori_id', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
        }
        if ($dataLayanan->is_flash_sale == 1 && $dataLayanan->expired_flash_sale >= date('Y-m-d H:i:s') && $dataLayanan->stock_flash_sale > 0) {

            $dataLayanan->harga = $dataLayanan->harga_flash_sale;
        }
        // qty
        if (in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'])) {
            $qty = $request->qty;
            if ($qty <= 0) {
                $qty = 1;
            }

            $dataLayanan->harga *= $qty;
        }
        $this->applyTenantPricing($dataLayanan, $request);

        // voucher
        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            $dataLayanan->harga -= $this->calculateVoucherDiscountAmount($dataLayanan->harga, $voucher);
        }


        $checkResult = app(CheckIdResolver::class)->resolveForCategory(
            $produk,
            (string) $request->uid,
            $request->zone !== null ? (string) $request->zone : null,
            $item,
        );

        if (($checkResult['skip_check'] ?? false) !== true) {
            if (!isset($checkResult['status']['code']) || $checkResult['status']['code'] !== 200 || empty($checkResult['data']['username'])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User ID tidak ditemukan atau tidak valid. Silakan periksa kembali.'
                ]);
            }

            $username = $checkResult['data']['username'];
        }

        // Initialize username if not set (for games not in validation list)
        if (!isset($username)) {
            $username = "Anonim";
        }

        $dataMethod = $this->resolveSelectedMethod((string) $request->payment_method);
        if (!$dataMethod) {
            return $this->orderErrorResponse('Metode pembayaran tidak ditemukan.', 'PAYMENT_METHOD_NOT_FOUND');
        }

        $amountBeforePoint = (int) round($dataLayanan->harga + $this->calculateMethodFeeAmount($dataLayanan->harga, $dataMethod));
        $pointUsage = $this->resolvePointUsage($amountBeforePoint, (int) $request->use_point);
        $dataLayanan->harga = max(1000, $amountBeforePoint - $pointUsage['discount']);

        $sendData = view('template.components.order_confirmation', compact(
            'request', 'dataLayanan', 'dataMethod', 'produk', 'item', 'username'
        ))->render();

        return response()->json([
            'status' => true,
            'data' => $sendData
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validation
        $this->validateOrder($request);

        $idempotencyKeys = $this->buildOrderIdempotencyKeys($request);
        $idempotencyLockKey = $idempotencyKeys['lock'];
        $idempotencyResultKey = $idempotencyKeys['result'];
        $cachedOrderId = Cache::get($idempotencyResultKey);

        if ($cachedOrderId) {
            return $this->orderSuccessResponse($request, $cachedOrderId, [
                'message' => 'Order sudah diproses sebelumnya.',
            ]);
        }

        if (!Cache::add($idempotencyLockKey, true, now()->addSeconds(self::ORDER_IDEMPOTENCY_LOCK_SECONDS))) {
            $cachedOrderId = Cache::get($idempotencyResultKey);
            if ($cachedOrderId) {
                return $this->orderSuccessResponse($request, $cachedOrderId, [
                    'message' => 'Order sudah diproses sebelumnya.',
                ]);
            }

            return $this->orderErrorResponse(
                'Permintaan sedang diproses. Mohon tunggu sebentar.',
                'ORDER_DUPLICATE_REQUEST',
                200,
                ['retry_after_seconds' => 5]
            );
        }

        try {
            // 2. Initial Setup
            if ($this->hasTenantContext()) {
                $dataLayanan = Layanan::where('id', $request->service)
                    ->where('status', 'available')
                    ->select('id', 'layanan', 'harga_gold', 'harga_gold AS harga', 'harga_gold AS modal_harga', 'kategori_id', 'provider_id', 'provider', 'profit_gold AS profit', 'status', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                    ->first();
            } elseif (Auth::check()) {
                $role = Auth::user()->role;
                $column = match($role) {
                    'Member' => 'harga_member',
                    'Platinum' => 'harga_platinum',
                    'Gold', 'Admin' => 'harga_gold',
                    default => 'harga_member'
                };
                $profitCol = match($role) {
                    'Member' => 'profit_member',
                    'Platinum' => 'profit_platinum',
                    'Gold', 'Admin' => 'profit_gold',
                    default => 'profit_member'
                };

                $dataLayanan = Layanan::where('id', $request->service)
                    ->where('status', 'available')
                    ->select('id', 'layanan', "$column AS harga", 'harga AS modal_harga', 'kategori_id', 'provider_id', 'provider', "$profitCol AS profit", 'status', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                    ->first();
            } else {
                $dataLayanan = Layanan::where('id', $request->service)
                    ->where('status', 'available')
                    ->select('id', 'layanan', 'harga_member AS harga', 'harga AS modal_harga', 'kategori_id', 'provider_id', 'provider', 'profit_member AS profit', 'status', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                    ->first();
            }

        if (!$dataLayanan) {
            return $this->orderErrorResponse('Layanan tidak ditemukan atau tidak tersedia.', 'SERVICE_UNAVAILABLE');
        }

        if (! in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'], true)) {
            $orderLayanan = Layanan::query()->find((int) $dataLayanan->id);
            $orderKategori = $orderLayanan?->kategori_id
                ? Kategori::query()->find((int) $orderLayanan->kategori_id)
                : null;

            if ($orderLayanan && $orderKategori) {
                $checkResult = app(CheckIdResolver::class)->resolveForCategory(
                    $orderKategori,
                    (string) $request->uid,
                    $request->zone !== null ? (string) $request->zone : null,
                    $orderLayanan,
                );

                if (($checkResult['skip_check'] ?? false) !== true) {
                    if (($checkResult['status']['code'] ?? null) !== 200
                        || blank($checkResult['data']['username'] ?? null)) {
                        return $this->orderErrorResponse(
                            'User ID tidak ditemukan atau tidak valid. Silakan periksa kembali.',
                            'INVALID_GAME_ACCOUNT'
                        );
                    }

                    // Never persist a client-supplied nickname for validated game orders.
                    $request->merge(['nickname' => $checkResult['data']['username']]);
                }
            }
        }

        $flashSaleReserved = false;
        $voucherReserved = false;
        $orderCreated = false;
        $pointsReserved = false;

        // Flash Sale Logic — FIX #1: Atomic decrement untuk cegah race condition
        // Gunakan satu query UPDATE dengan kondisi WHERE stock > 0 supaya thread-safe
        $isFlashSale = $dataLayanan->is_flash_sale == 1
            && $dataLayanan->expired_flash_sale >= now()
            && $dataLayanan->stock_flash_sale > 0;
        if ($isFlashSale) {
            $decremented = Layanan::where('id', $request->service)
                ->where('is_flash_sale', 1)
                ->where('expired_flash_sale', '>=', now())
                ->where('stock_flash_sale', '>', 0)
                ->decrement('stock_flash_sale');

            if ($decremented) {
                $flashSaleReserved = true;
                $dataLayanan->harga = $dataLayanan->harga_flash_sale;
            } else {
                // Stok habis saat race condition — tolak order
                return $this->orderErrorResponse('Maaf, stok flash sale sudah habis. Silakan coba produk lain.', 'FLASHSALE_STOCK_EMPTY');
            }
        }

        // Joki Quantity Logic
        if (in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'])) {
            $qty = $request->qty > 0 ? $request->qty : 1;
            $dataLayanan->harga *= $qty;
        }

        $this->applyTenantPricing($dataLayanan, $request);

        // Voucher Logic (Calculation Only)
        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            Log::debug('Voucher found', [
                'voucher_code' => $request->voucher,
                'voucher_id' => $voucher->id ?? null,
                'voucher_stock' => $voucher->stock ?? null,
            ]);
            if (! $voucher || ! $voucher->isUsable()) {
                $this->restoreCheckoutReservations($flashSaleReserved, false, false, false, $request, $dataLayanan, 0, '');

                return $this->orderErrorResponse(
                    'Voucher tidak valid atau sudah kadaluarsa',
                    'VOUCHER_INVALID'
                );
            }

            if ($voucher->mintrx && $dataLayanan->harga < $voucher->mintrx) {
                $this->restoreCheckoutReservations($flashSaleReserved, false, false, false, $request, $dataLayanan, 0, '');

                return $this->orderErrorResponse(
                    'Minimal transaksi untuk voucher ini adalah Rp ' . number_format($voucher->mintrx, 0, ',', '.'),
                    'VOUCHER_MIN_TRANSACTION'
                );
            }

            $dataLayanan->harga = round($dataLayanan->harga - $this->calculateVoucherDiscountAmount($dataLayanan->harga, $voucher));
        }

        $dataMethod = $this->resolveSelectedMethod((string) $request->payment_method);
        if (!$dataMethod) {
            return $this->orderErrorResponse('Metode pembayaran tidak ditemukan.', 'PAYMENT_METHOD_NOT_FOUND');
        }

        $baseServiceAmount = max(0, (int) round((float) $dataLayanan->harga));
        $gatewayFeeAmount = $this->calculateMethodFeeAmount($baseServiceAmount, $dataMethod);
        $amountBeforePoint = (int) round($baseServiceAmount + $gatewayFeeAmount);
        $pointUsage = $this->resolvePointUsage($amountBeforePoint, (int) $request->use_point);
        $usedPoints = $pointUsage['used_points'];
        $usedPointAmount = $pointUsage['discount'];
        $dataLayanan->harga = max(1000, $amountBeforePoint - $usedPointAmount);
        $methodLimitMessage = $this->validatePaymentMethodAmount((int) $dataLayanan->harga, $dataMethod);
        if ($methodLimitMessage !== null) {
            $this->restoreCheckoutReservations($flashSaleReserved, $voucherReserved, $pointsReserved, $orderCreated, $request, $dataLayanan, $usedPoints, $order_id ?? '');

            return $this->orderErrorResponse($methodLimitMessage, 'PAYMENT_METHOD_AMOUNT_OUT_OF_RANGE');
        }

        // Generate Order ID — FIX #5: Tambah timestamp lebih panjang + uniqueness guard
        $setting = DB::table('setting_webs')->where('id', 1)->first();
        $prefix   = $setting->order_prefik ?? 'TRX';
        $order_id = $prefix . now()->format('ymdHis') . Str::upper(Str::random(6));
        // Pastikan unik di DB (collision guard)
        while (Pembelian::where('order_id', $order_id)->exists()) {
            $order_id = $prefix . now()->format('ymdHis') . Str::upper(Str::random(6));
        }

        if ($usedPoints > 0 && Auth::check()) {
            $reservedAmount = app(\App\Services\PointService::class)->redeemPoints(
                Auth::user(),
                $usedPoints,
                $order_id,
                $dataLayanan->layanan
            );

            if ($reservedAmount <= 0) {
                $this->restoreCheckoutReservations($flashSaleReserved, $voucherReserved, false, $orderCreated, $request, $dataLayanan, $usedPoints, $order_id);

                return $this->orderErrorResponse('Poin tidak cukup atau gagal dipakai. Silakan refresh lalu coba lagi.', 'POINT_REDEMPTION_FAILED');
            }

            $usedPointAmount = $reservedAmount;
            $pointsReserved = true;
        }

        
        // Payment Method Info
        
        // 3. Process based on Payment Method
        if ($request->payment_method == "SALDO") {
            // --- BALANCE PAYMENT FLOW ---
            if (!Auth::check()) {
                return $this->orderErrorResponse('Harap login terlebih dahulu', 'LOGIN_REQUIRED');
            }

            $userKey = 'user_transaction_' . Auth::id();
            if (Cache::has($userKey)) {
                return $this->orderErrorResponse('Transaksi terlalu cepat, harap tunggu sebentar.', 'BALANCE_RATE_LIMIT');
            }
            Cache::put($userKey, true, now()->addMinutes(1));

            DB::beginTransaction();
            try {
                // Rate Limiting Check (Last transaction < 1 min)
                $lastOrder = Pembelian::where('username', Auth::user()->username)->latest()->first();
                if ($lastOrder && $lastOrder->created_at->diffInMinutes(now()) < 1) {
                    throw new \Exception('Harap tunggu 1 menit sebelum transaksi lagi.');
                }

                $user = User::where('username', Auth::user()->username)->lockForUpdate()->first();
                if ($dataLayanan->harga > $user->balance) {
                    throw new \Exception('Saldo tidak mencukupi');
                }

                // Voucher Stock Decrement
                if ($request->voucher) {
                    $voucher = Voucher::where('kode', $request->voucher)->lockForUpdate()->first();
                    if (!$voucher || ! $voucher->isUsable()) throw new \Exception('Voucher tidak valid atau sudah kadaluarsa');
                    $voucher->decrement('stock');
                }

                // Deduct Balance
                $user->decrement('balance', $dataLayanan->harga);
                // Process Game Provider
                $providerResult = $this->processGameProvider($dataLayanan, $request, $order_id);
                
                // ERROR HANDLING STRATEGY: 
                // Jika provider gagal memproses (misal gangguan/maintenance/koneksi), 
                // jangan buat order Pending yang 'menggantung'. Kembalikan saldo user.
                if (!$providerResult['status']) {
                    $errorMsg = isset($providerResult['order_data']['message']) ? $providerResult['order_data']['message'] : 'Terjadi kesalahan pada Provider';
                    throw new \Exception($errorMsg);
                }

                // Create Record
                $tipe = match($request->ktg_tipe) {
                    'joki' => 'joki', 'voucher' => 'voucher', 'vilogml' => 'vilogml', 'jokigendong' => 'jokigendong', default => 'game'
                };
                
                // IP Address
                $ipController = new IPAddressController();
                $ipAddress = $ipController->getIPAddress($request);

                // Map status from provider to system status
                $status_pembelian = isset($providerResult['order_status']) && $providerResult['order_status'] === 'Sukses' ? 'Success' : 'Proses'; 
                $provider_order_id = $providerResult['provider_order_id'];
                $log_data = json_encode($providerResult['order_data']);
                $providerOrderData = $providerResult['order_data']['data'] ?? [];
                $providerSn = trim((string) ($providerOrderData['sn'] ?? ''));
                $keteranganSn = $providerSn !== '' ? $providerSn : ($providerResult['order_status'] === 'Pending' ? 'Sedang Diproses' : null);

                $pembelian = $this->createOrderRecord(
                    $request, $dataLayanan, $order_id, $dataLayanan->harga, $dataMethod,
                    'Lunas', 'Balance Payment', '', $status_pembelian,
                    $provider_order_id, $log_data, $ipAddress, $tipe, $keteranganSn, $usedPoints, $usedPointAmount, [
                        'gateway_fee_amount' => $gatewayFeeAmount,
                        'base_service_amount' => $baseServiceAmount,
                    ], [
                        'provider_code' => $providerResult['provider_code'] ?? null,
                        'provider_sku' => $providerResult['provider_sku'] ?? null,
                    ]
                );
                $orderCreated = true;

                DB::commit();
                PollSufPaymentStatusJob::dispatchIfNeeded($pembelian, $provider_order_id, $providerResult['order_status'] ?? $status_pembelian);
                Cache::forget($userKey);

                // Send Success Message
                $pesanSukses = "*Pembelian Sukses*\n\nNo Invoice: *$order_id*\nLayanan: *$dataLayanan->layanan*\nID : *$request->uid*\nServer : *$request->zone*\nNickname : *$request->nickname*\nHarga: *Rp. " . number_format($dataLayanan->harga, 0, '.', ',') . "*\nStatus Pembelian: *Sukses*\nMetode Pembayaran: *SALDO*\n\n*Invoice* : " . env("APP_URL") . "/id/invoices/$order_id\n\nINI ADALAH PESAN OTOMATIS";
                if ($request->nomor) {
                    $this->msg($request->nomor, $pesanSukses);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Cache::forget($userKey);
                $this->restoreCheckoutReservations($flashSaleReserved, false, $pointsReserved, $orderCreated, $request, $dataLayanan, $usedPoints, $order_id);
                Log::error('Order Store Exception', ['error' => $e->getMessage()]);
                // Return clear error message to user
                return $this->orderErrorResponse($e->getMessage(), 'ORDER_PROCESSING_FAILED');
            }

        } else {
            // --- EXTERNAL PAYMENT GATEWAY FLOW ---
            $amount = $dataLayanan->harga;
            $no_pembayaran = '';
            $reference = '';

            // FIX #2: Voucher stock decrement di gateway payment (sebelumnya hanya di alur SALDO)
            // Gunakan lockForUpdate di dalam transaksi untuk cegah double-use
            if ($request->voucher) {
                try {
                    DB::transaction(function () use ($request, &$voucherReserved) {
                        $voucherGw = Voucher::where('kode', $request->voucher)->lockForUpdate()->first();
                        if (!$voucherGw || ! $voucherGw->isUsable()) {
                            throw new \Exception('Voucher tidak valid atau sudah kadaluarsa');
                        }
                        $voucherGw->decrement('stock');
                        $voucherReserved = true;
                    });
                } catch (\Exception $e) {
                    if ($pointsReserved && Auth::check()) {
                        app(\App\Services\PointService::class)->refundPoints(
                            Auth::user(),
                            $usedPoints,
                            $order_id,
                            $dataLayanan->layanan
                        );
                    }
                    return $this->orderErrorResponse($e->getMessage(), 'VOUCHER_STOCK_FAILED');
                }
            }

            // Gateway Processing
            $gatewayResult = ['status' => false, 'msg' => 'Metode pembayaran tidak tersedia'];
            
            if ($dataMethod->payment == "tokopay") {
                $tokopay = app(TokoPayController::class);
                $customerPhone = $this->gatewayCustomerPhone($request);
                // Parameters: $ref_id, $channel, $jumlah, $nickname, $phone_number, $service
                $res = $tokopay->createAdvanceOrder(
                    $order_id,
                    $request->payment_method,
                    $amount,
                    $request->nickname ?? 'Guest',
                    $customerPhone,
                    $dataLayanan->layanan
                );
                
                if (isset($res['status']) && $res['status'] === 'Success') {
                    $tokopayData = is_array($res['data'] ?? null) ? $res['data'] : [];
                    $tokopayPaymentCode = $tokopayData['nomor_va']
                        ?? $tokopayData['pay_code']
                        ?? $tokopayData['payment_code']
                        ?? $tokopayData['kode_bayar']
                        ?? null;
                    $tokopayPaymentUrl = $tokopayData['checkout_url'] ?? $tokopayData['pay_url'] ?? null;

                    $gatewayResult = [
                        'status' => true,
                        'no_pembayaran' => $tokopayPaymentCode ?? $tokopayPaymentUrl,
                        'payment_url' => $tokopayPaymentUrl,
                        'qr_image_url' => $tokopayData['qr_link'] ?? null,
                        'qr_payload' => $tokopayData['qr_string'] ?? $tokopayData['qrString'] ?? null,
                        'reference' => $tokopayData['trx_id'] ?? null,
                        'amount' => $tokopayData['total_bayar'] ?? $amount,
                        'expired_at' => $tokopayData['expired_at'] ?? $tokopayData['expired_ts'] ?? null,
                    ];
                } else {
                     $gatewayResult['msg'] = $res['error_msg'] ?? 'Gagal membuat pesanan TokoPay';
                }
            } else if ($dataMethod->payment == "tripay") {
                $tripay = app(TriPayController::class);
                $tripayRequestAmount = $this->resolveGatewayRequestAmount($amount, $dataMethod);
                $customerEmail = $this->gatewayCustomerEmail($request);
                $customerPhone = $this->gatewayCustomerPhone($request);

                $res = $tripay->request($order_id, $tripayRequestAmount, $request->payment_method, $customerEmail, $customerPhone);

                if ($res['success']) {
                    $gatewayResult = [
                        'status' => true,
                        'no_pembayaran' => $res['no_pembayaran'],
                        'payment_url' => $res['pay_url'] ?? null,
                        'qr_image_url' => $res['qr_url'] ?? null,
                        'qr_payload' => $res['qr_payload'] ?? null,
                        'reference' => $res['reference'],
                        'amount' => $res['amount'],
                        'expired_at' => $res['expired_at'] ?? null,
                    ];
                } else {
                     $gatewayResult['msg'] = $res['msg'];
                }
            } else if ($dataMethod->payment == "duitku") {
                // Create temporary order for Duitku invoice
                $tempOrder = new Pembelian();
                $tempOrder->order_id = $order_id;
                $tempOrder->layanan = $dataLayanan->layanan;
                $tempOrder->user_id = $request->uid;
                $tempOrder->zone = $request->zone ?? '';
                $tempOrder->nickname = $request->nickname ?? 'Customer';
                $tempOrder->email_pembeli = $request->email ?? '';
                $tempOrder->username = Auth::check() ? Auth::user()->username : 'guest';
                $tempOrder->harga = $amount;
                $tempOrder->profit = $dataLayanan->profit ?? 0; // Required field
                $tempOrder->status = 'Pending'; // Will be updated after payment

                // Pass payment_method from request (Duitku method code: SP, BC, I1, etc)
                $duitkuMethodCode = $request->payment_method ?? ''; // Empty = user chooses at Duitku page
                $res = app(DuitkuInvoiceService::class)->createForPembelian($tempOrder, $duitkuMethodCode);

                if ($res['success']) {
                    $gatewayResult = [
                        'status' => true,
                        'no_pembayaran' => $res['vaNumber'] ?? $res['qrString'] ?? $res['paymentUrl'] ?? $res['reference'],
                        'payment_url' => $res['paymentUrl'] ?? $res['payment_url'] ?? null,
                        'qr_payload' => $res['qrString'] ?? $res['qr_string'] ?? null,
                        'reference' => $res['reference'],
                        'amount' => $res['amount'] ?? $amount,
                        'merchant_order_id' => $res['merchant_order_id'] ?? $res['merchantOrderId'] ?? ('DUITKU-' . $order_id),
                        'duitku_payment_code' => $res['duitku_payment_method'] ?? $duitkuMethodCode,
                        'expired_at' => $res['expired_at'] ?? null,
                    ];
                } else {
                    $gatewayResult['msg'] = $res['message'] ?? 'Gagal membuat invoice Duitku';
                }
            } else if ($dataMethod->payment == "manual") {
                $gatewayResult = $this->buildManualGatewayResult($order_id, $amount, $dataMethod);
            }

            if (!$gatewayResult['status']) {
                Log::error('Order Store Gateway Failed', [
                    'gateway_status' => $gatewayResult['status'] ?? null,
                    'gateway_message' => $gatewayResult['msg'] ?? null,
                    'gateway_reference' => $gatewayResult['reference'] ?? null,
                ]);
                $this->restoreCheckoutReservations($flashSaleReserved, $voucherReserved, $pointsReserved, $orderCreated, $request, $dataLayanan, $usedPoints, $order_id);

                return $this->orderErrorResponse($gatewayResult['msg'] ?? 'Gagal memproses pembayaran', 'PAYMENT_GATEWAY_FAILED');
            }

            $amount = $gatewayResult['amount'];
            $no_pembayaran = $gatewayResult['no_pembayaran'];
            $reference = $gatewayResult['reference'];
            $gatewayResult['gateway_fee_amount'] = $gatewayFeeAmount;
            $gatewayResult['base_service_amount'] = $baseServiceAmount;

            // Create Record (Pending)
            $tipe = match($request->ktg_tipe) {
                'joki' => 'joki', 'voucher' => 'voucher', 'vilogml' => 'vilogml', 'jokigendong' => 'jokigendong', default => 'game'
            };
            $ipController = new IPAddressController();
            $ipAddress = $ipController->getIPAddress($request);

            try {
                $this->createOrderRecord(
                    $request, $dataLayanan, $order_id, $amount, $dataMethod, 
                    'Belum Lunas', $no_pembayaran, $reference, 'Pending', 
                    '', '', $ipAddress, $tipe, null, $usedPoints, $usedPointAmount, $gatewayResult
                );
                $orderCreated = true;

                $this->sendOrderCreatedPushNotification($request, $order_id);
            } catch (\Exception $e) {
                $this->restoreCheckoutReservations($flashSaleReserved, $voucherReserved, $pointsReserved, $orderCreated, $request, $dataLayanan, $usedPoints, $order_id);

                Log::error('Order Store Create Record Failed', ['error' => $e->getMessage(), 'order_id' => $order_id]);
                return $this->orderErrorResponse($e->getMessage(), 'ORDER_RECORD_CREATE_FAILED');
            }

            $paymentExpiryAt = $this->resolvePaymentExpiryAt($gatewayResult, $dataMethod, 'Belum Lunas');
            $paymentExpiryLabel = $paymentExpiryAt
                ? $paymentExpiryAt->timezone(config('app.timezone'))->format('d/m/Y H:i')
                : '3 jam dari sekarang';

            // Send Pending Message
            $pesanPending = $this->buildPendingPaymentMessage(
                $order_id,
                (string) $dataLayanan->layanan,
                (int) $amount,
                (string) $dataMethod->name,
                (string) $no_pembayaran,
                $paymentExpiryLabel,
            );
            if ($request->nomor) {
                $this->msg($request->nomor, $pesanPending);
            }
        }

            Cache::put($idempotencyResultKey, $order_id, now()->addSeconds(self::ORDER_IDEMPOTENCY_RESULT_SECONDS));

            return $this->orderSuccessResponse($request, $order_id);
        } finally {
            Cache::forget($idempotencyLockKey);
        }
    }

    public function buildPendingPaymentMessage(
        string $orderId,
        string $product,
        int $amount,
        string $method,
        string $paymentCode,
        string $paymentExpiryLabel,
    ): string {
        $paymentCode = trim($paymentCode);
        $isPaymentLink = filter_var($paymentCode, FILTER_VALIDATE_URL) !== false;
        $isQrisPayload = str_starts_with($paymentCode, '000201') && strlen($paymentCode) >= 50;
        $paymentInstruction = $paymentCode === '' || $isPaymentLink || $isQrisPayload
            ? '💳 Pembayaran: *Buka invoice di bawah untuk scan QRIS atau melanjutkan pembayaran.*'
            : "💳 Kode Bayar / VA: *{$paymentCode}*";
        $storeName = trim((string) config('app.name', 'Laravel')) ?: 'Laravel';
        $invoiceUrl = rtrim((string) config('app.url'), '/') . '/id/invoices/' . rawurlencode($orderId);

        return implode("\n", [
            '⏳ *MENUNGGU PEMBAYARAN*',
            '',
            "Terima kasih telah berbelanja di {$storeName}.",
            '',
            '🧾 *RINCIAN TRANSAKSI*',
            "├ Nomor Invoice: *{$orderId}*",
            "├ Produk: *{$product}*",
            '├ Total Tagihan: *Rp ' . number_format($amount, 0, ',', '.') . '*',
            "└ Metode: *{$method}*",
            '',
            $paymentInstruction,
            "⏰ Bayar sebelum: *{$paymentExpiryLabel}*",
            '',
            '⚠️ Selesaikan pembayaran agar pesanan diproses otomatis.',
            "🔗 Invoice: {$invoiceUrl}",
        ]);
    }

    private function sendOrderCreatedPushNotification(Request $request, string $orderId): void
    {
        try {
            $order = Pembelian::query()
                ->where('order_id', $orderId)
                ->with('user')
                ->first();

            if (! $order) {
                return;
            }

            app(PublicOrderPushNotificationService::class)
                ->notifyOrderCreated($order, $request->session()->getId());
        } catch (\Throwable $exception) {
            Log::warning('Order created push notification failed', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function msg($nomor, $msg)
    {
        try {
            $api = DB::table('setting_webs')->where('id', 1)->first();
            
            if (!$api || !$api->wa_key || !$api->nomor_admin) {
                Log::error('WhatsApp API (Fonnte) - Missing configuration.', ['wa_key_exists' => !empty($api->wa_key), 'nomor_admin_exists' => !empty($api->nomor_admin)]);
                return ['success' => false, 'message' => 'Konfigurasi WA belum lengkap.'];
            }

            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => [
                    'target' => $nomor,
                    'message' => $msg,
                ],
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $api->wa_key,
                ],
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            unset($curl);

            if ($error) {
                Log::error('WhatsApp API (Fonnte) - Curl Error', ['error' => $error]);
                return ['success' => false, 'message' => 'Connection Error: ' . $error];
            }

            Log::debug('WhatsApp API (Fonnte) Response', [
                'response_length' => strlen((string) $response),
                'response_preview' => mb_substr((string) $response, 0, 200),
            ]);
            return ['success' => true, 'response' => $response];

        } catch (\Exception $e) {
            Log::error('WhatsApp API (Fonnte) - Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'System Error: ' . $e->getMessage()];
        }
    }

    public function getPrice(Request $request)
    {
        try {
            $layanan = Layanan::where('provider_id', $request->nominal)->first();
            if (!$layanan) {
                throw new \Exception('Layanan tidak ditemukan');
            }
            $qty = $request->qty ? intval($request->qty) : 1;
            $paymentMethod = $request->payment_method;

            // Hitung harga dasar
            $basePrice = $layanan->harga * $qty;

            // FIX #6: Inisialisasi $finalPrice = $basePrice (sebelumnya undefined → hanya berisi fee saja)
            $finalPrice = $basePrice;

            // Tambahkan fee payment method
            $method = Method::where('code', $paymentMethod)->first();
            if ($method) {
                $finalPrice += $method->fix_fee + ($basePrice * ($method->fee_percent / 100));
            }

            // FIX #7: Hapus promo DISKON10 hardcoded — gunakan sistem voucher DB
            // Promo DISKON10 dihapus karena tidak ada expiry, limit penggunaan, dan bisa ditebak

            return response()->json([
                'success' => true,
                'harga' => round($finalPrice),
                'harga_format' => 'Rp. ' . number_format($finalPrice, 0, ',', '.')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function checkAccount(Request $request)
    {
        // 1. Validate Input
        $request->validate([
            'uid' => 'required',
            'kategori_kode' => 'required',
            'service' => 'nullable|integer',
        ]);

        $kategori = Kategori::select('id', 'kode', 'tipe')
            ->where('kode', $request->kategori_kode)
            ->first();

        if (!$kategori) {
            return response()->json([
                'status' => ['code' => 404, 'message' => 'Kategori tidak ditemukan']
            ], 404);
        }

        if (!in_array($kategori->tipe, ['game', 'populer'], true)) {
            return response()->json([
                'status' => ['code' => 204, 'message' => 'Account validation skipped'],
                'skip_check' => true,
            ]);
        }

        $layananContext = null;
        if ($request->filled('service')) {
            $layananContext = Layanan::query()
                ->select('id', 'kategori_id')
                ->find((int) $request->service);

            if (! $layananContext || (int) $layananContext->kategori_id !== (int) $kategori->id) {
                return response()->json([
                    'status' => ['code' => 422, 'message' => 'Layanan tidak sesuai kategori']
                ], 422);
            }
        }

        $data = app(CheckIdResolver::class)->resolveForCategory(
            $kategori,
            (string) $request->uid,
            $request->zone !== null ? (string) $request->zone : null,
            $layananContext,
        );

        return response()->json($data);
    }

    private function validateOrder(Request $request)
    {
        $this->validateOrderRequest($request, false);
    }

    private function processGameProvider($dataLayanan, $request, $order_id)
    {
        $provider_order_id = '';
        $status = false;
        $order_status = 'Pending';
        $order = [];

        // Use ProviderRoutingService to find best provider
        $routingService = app(\App\Services\ProviderRoutingService::class);
        $bestRoute = $routingService->findBestProvider($dataLayanan);

        if (!$bestRoute) {
            Log::error("No provider found for Layanan ID: {$dataLayanan->id}");
            return [
                'status' => false,
                'order_status' => 'Gagal',
                'provider_order_id' => '',
                'provider_code' => null,
                'provider_sku' => null,
                'order_data' => ['message' => 'Layanan sedang gangguan (No Provider)']
            ];
        }

        $providerCode = $bestRoute['provider_code'];
        $sku = $bestRoute['sku'];
        
        // Record which provider was used for this transaction
        Log::debug("Order $order_id routed to $providerCode with SKU $sku");

        $credentials = $bestRoute['credentials'] ?? [];

        try {
            switch ($providerCode) {
                case "digiflazz":
                    $digi = new DigiFlazzController($credentials);
                    $order = $digi->order($request->uid, $request->zone, $sku, $order_id);
                    $orderData = $order['data'] ?? [];
                    $status = in_array(($orderData['status'] ?? null), ["Pending", "Sukses", "Success"], true);
                    $order_status = ($orderData['status'] ?? null) === 'Success' ? 'Sukses' : ($orderData['status'] ?? 'Gagal');
                    $provider_order_id = $orderData['ref_id'] ?? $order_id;
                    Log::debug('Digiflazz Order', [
                        'order_id' => $order_id,
                        'status' => $status,
                        'provider_status' => $orderData['status'] ?? null,
                        'provider_ref_id' => $orderData['ref_id'] ?? null,
                    ]);
                    break;

                case "apigames":
                    $apigames = new ApiGamesController($credentials);
                    $order = $apigames->order($request->uid, $request->zone, $sku, $order_id);
                    $statusMeta = ApiGamesController::normalizeStatusMeta($order['data']['status'] ?? null);

                    if (($order['result'] ?? false) === true) {
                        $order['transactionId'] = $order['data']['trx_id'] ?? $order_id;
                        $provider_order_id = $order['data']['trx_id'] ?? $provider_order_id;
                        $status = true;
                        $order_status = $statusMeta['internal_status'];
                    } elseif (($order['transport_error'] ?? false) === true) {
                        $status = true;
                        $order_status = 'Pending';
                    }
                    break;

                case "sufpayment":
                    $sufpayment = new SufPaymentService($credentials);
                    $provider_order_id = $order_id;
                    $order = $sufpayment->order($request->uid, $request->zone, $sku);

                    if (($order['result'] ?? false) === true) {
                        $orderData = is_array($order['data'] ?? null) ? $order['data'] : [];
                        $statusMeta = SufPaymentService::normalizeStatusMeta($orderData['status'] ?? $orderData['order_status'] ?? null);
                        $provider_order_id = $orderData['id']
                            ?? $orderData['trxid']
                            ?? $orderData['trx_id']
                            ?? $orderData['transaction_id']
                            ?? $order_id;
                        $order['transactionId'] = $provider_order_id;
                        $order['provider_status'] = $statusMeta['internal_status'];
                        $status = true;
                        $order_status = $statusMeta['internal_status'];
                    } elseif (($order['transport_error'] ?? false) === true) {
                        $status = true;
                        $order_status = 'Pending';
                    }
                    break;

                case "vip":
                case "vip_reseller": // Handle alias
                    $vip = new VipResellerController($credentials);
                    $order = $vip->order($request->uid, $request->zone, $sku);
                    if (($order['result'] ?? false) === true) {
                        $statusMeta = VipResellerController::normalizeStatusMeta($order['data']['status'] ?? null);
                        $status = true;
                        $provider_order_id = $order['data']['trxid'] ?? $order_id;
                        $order_status = $statusMeta['internal_status'];
                    }
                    break;

                case "bangjeff":
                    $bangjeffo = new BangJeffController($credentials);
                    $requestData = [['name' => 'ID', 'value' => $request->uid]];
                    if ($request->has('zone')) $requestData[] = ['name' => 'Server', 'value' => $request->zone];
                    $price = [
                        'currency' => 'IDR',
                        'value' => (int) round((float) ($dataLayanan->modal_harga ?? $dataLayanan->harga)),
                    ];
                    
                    $order = $bangjeffo->order($sku, $order_id, 1, $requestData, $price);
                    $isSuccess = (($order['error'] ?? null) === false) || (($order['rc'] ?? null) === '00');
                    $statusCode = strtoupper((string) ($order['data']['statusCode'] ?? 'PROCESSING'));

                    if ($isSuccess) {
                        $provider_order_id = $order['data']['invoiceNumber'] ?? $order_id;
                        $status = true;
                        $order_status = $statusCode === 'SUCCESS' ? 'Sukses' : ($statusCode === 'REFUNDED' ? 'Gagal' : 'Pending');
                    }
                    break;

                case "topupedia":
                    $topupedia = new TopupediaController($credentials);
                    $requestData = [['name' => 'ID', 'value' => $request->uid]];
                    if ($request->has('zone')) $requestData[] = ['name' => 'Server', 'value' => $request->zone];
                    
                    $order = $topupedia->order($sku, $order_id, 1, $requestData);
                    if ($order['error'] == false) {
                        $provider_order_id = $order['data']['invoiceNumber'];
                        $status = true;
                        $order_status = 'Pending';
                    }
                    break;

                case "moogold":
                    $moo = new MoogoldController();
                    $provider_order_id = 'WEJIZY-MG' . mt_rand(100000, 999999);
                    $order = $moo->order($request->uid, $sku, $provider_order_id, $request->zone);
                    if (isset($order['status'])) {
                        $provider_order_id = $order['order_id'];
                        $status = true;
                        $order_status = 'Pending';
                    }
                    break;

                case "gameshop":
                    $gameshop = new GameShopProvider;
                    $provider_order_id = 'WEJIZY-GS' . mt_rand(100000, 999999);
                    $order = $gameshop->order($request->uid, $sku, $provider_order_id, $request->zone);
                    if (isset($order['data']['order_no'])) {
                        $provider_order_id = $order['data']['order_no'];
                        $status = true;
                        $order_status = 'Pending';
                    }
                    break;

                case "strleyashop":
                    $strleyashop = new StrleyaShopProvider;
                    $provider_order_id = 'WEJIZY-SS' . mt_rand(100000, 999999);
                    $order = $strleyashop->order($request->uid, $sku, $provider_order_id, $request->zone);
                    if (isset($order['order_details']['bot_order_id'])) {
                        $provider_order_id = $order['order_details']['bot_order_id'];
                        $status = true;
                        $order_status = 'Pending';
                    }
                    break;

                case "yezzpay":
                    $yezzpay = new YezzpayProvider;
                    $provider_order_id = strtoupper(str_replace('.', '', uniqid('ACID-YEZZPAY', true)));
                    $order = $yezzpay->order($request->uid, $sku, $provider_order_id, $request->zone);
                    if (isset($order['data']['trx_id'])) {
                        $status = true;
                        $order_status = 'Pending';
                    }
                    break;

                case "elitedias":
                    $elitedias = new EliteDiasProvider;
                    $provider_order_id = 'WEJIZY-ED' . mt_rand(100000, 999999);
                    $order = $elitedias->order($request->uid, $sku, $provider_order_id, $request->zone);
                    if (isset($order['order_id'])) {
                        $provider_order_id = $order['order_id'];
                        $status = true;
                        $order_status = 'Pending';
                    }
                    break;

                case "joki":
                case "jokigendong":
                case "vilogml":
                case "manual": // Add manual case
                    $status = true;
                    break;
                
                default:
                    Log::warning("Provider unknown or unhandled: $providerCode");
                    $status = false;
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Provider Order Error: ' . $e->getMessage());
            $status = false;
        }

        return [
            'status' => $status,
            'order_status' => $order_status,
            'provider_order_id' => $provider_order_id,
            'provider_code' => $providerCode,
            'provider_sku' => $sku,
            'order_data' => $order
        ];
    }

    private function createOrderRecord($request, $dataLayanan, $order_id, $amount, $dataMethod, $status_pembayaran, $no_pembayaran, $reference, $order_status, $provider_order_id = '', $order_log = '', $ipAddress, $tipe, $keteranganSn = null, $usedPoints = 0, $usedPointAmount = 0, array $gatewayMeta = [], array $providerContextOverride = []): Pembelian {
        $user_id = Auth::check() ? Auth::user()->username : "Anonim"; // Consistent with original code
        $providerContext = $this->resolveProviderContextForOrder($dataLayanan, $providerContextOverride);
        $normalizedPhone = $this->normalizeOrderContactPhone($request);
        
        $pembelian = new Pembelian();
        $pembelian->username = $user_id; 
        $pembelian->order_id = $order_id;
        
        // Define standard values
        $is_joki = in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml']);
        
        $pembelian->user_id = !$is_joki ? $request->uid : '-';
        $pembelian->zone = !$is_joki ? $request->zone : '-';
        $pembelian->nickname = !$is_joki ? $request->nickname : ($request->ktg_tipe !== 'joki' ? $request->nickname_joki : '-');
        
        $pembelian->log = $this->mergeGatewayPaymentLog($order_log, $gatewayMeta);
        $pembelian->status = $order_status; // 'Pending' or 'Proses'
        $pembelian->tipe_transaksi = $tipe;
        
        $pembelian->layanan = $dataLayanan->layanan;
        $pembelian->harga = $amount;
        $pembelian->profit = $this->calculateOrderProfitAmount((int) $amount, $dataLayanan, $providerContext, $gatewayMeta, $dataMethod);
        $pembelian->active_layanan_id = $providerContext['layanan_id'];
        $pembelian->active_provider_code = $providerContext['provider_code'];
        $pembelian->active_provider_sku = $providerContext['provider_sku'];
        $pembelian->provider_order_id = $provider_order_id;
        $pembelian->active_attempt_token = trim((string) $provider_order_id) !== '' ? $provider_order_id : null;
        $pembelian->ip_address = $ipAddress;
        $pembelian->ttclid = $request->cookie('ttclid');
        $pembelian->ttp = $request->cookie('_ttp');
        $pembelian->client_user_agent = substr((string) $request->userAgent(), 0, 1000);
        $pembelian->voucher = $request->voucher ?? null;
        $pembelian->keterangan_sn = $keteranganSn;
        $pembelian->used_points = $usedPoints;
        $pembelian->used_point_amount = $usedPointAmount;
        $pembelian->traffic_source = $request->session()->get('traffic_source', 'Direct');
        $pembelian->email_pembeli = Auth::check() ? Auth::user()->email : ($request->email ?? $request->email_pembeli ?? null);
        $pembelian->save();

        $pembayaran = new Pembayaran();
        $pembayaran->order_id = $order_id;
        $pembayaran->harga = $amount;
        $pembayaran->no_pembayaran = $no_pembayaran;
        $pembayaran->no_pembeli = $normalizedPhone !== '' ? $normalizedPhone : '-';
        $pembayaran->status = $status_pembayaran; // 'Belum Lunas' or 'Lunas'
        $pembayaran->metode = $request->payment_method;
        $pembayaran->reference = $reference;
        $pembayaran->expired_at = $this->resolvePaymentExpiryAt($gatewayMeta, $dataMethod, $status_pembayaran);

        if (($dataMethod->payment ?? null) === 'duitku') {
            $pembayaran->duitku_reference = $reference;
            $pembayaran->duitku_merchant_order_id = $gatewayMeta['merchant_order_id'] ?? ('DUITKU-' . $order_id);
            $pembayaran->duitku_payment_code = $gatewayMeta['duitku_payment_code'] ?? $request->payment_method;
        }

        $pembayaran->save();

        if ($is_joki) {
            DB::table('data_joki')->insert([
                'order_id' => $order_id,
                'email_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->email_joki : '-',
                'password_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->password_joki : '-',
                'loginvia_joki' => $request->loginvia_joki,
                'nickname_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->nickname_joki : '-',
                'request_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->request_joki : '-',
                'catatan_joki' => $request->catatan_joki,

                'tglmain_joki' => $request->ktg_tipe !== 'jokigendong' ? '-' : $request->tglmain_joki,
                'jambooking_joki' => $request->ktg_tipe !== 'jokigendong' ? '-' : $request->jambooking_joki,
                'qty' => $request->qty ?? 1,
                'status_joki' => $order_status == 'Proses' ? 'Proses' : 'Pending', // Sync with order status
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return $pembelian;
    }

    private function mergeGatewayPaymentLog(string $orderLog, array $gatewayMeta): string
    {
        $metadata = json_decode($orderLog, true);
        $metadata = is_array($metadata) ? $metadata : [];
        $gatewayPayment = array_filter([
            'provider' => $gatewayMeta['provider'] ?? null,
            'payment_url' => $gatewayMeta['payment_url'] ?? null,
            'qr_image_url' => $gatewayMeta['qr_image_url'] ?? null,
            'qr_payload' => $gatewayMeta['qr_payload'] ?? null,
        ], static fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '');

        if ($gatewayPayment !== []) {
            $metadata['gateway_payment'] = $gatewayPayment;
        }

        return $metadata !== [] ? json_encode($metadata, JSON_UNESCAPED_SLASHES) : $orderLog;
    }

    private function normalizeOrderContactPhone(Request $request): string
    {
        $candidates = [
            trim((string) $request->input('nomor')),
            trim((string) $request->input('whatsapp')),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && preg_match('/^[0-9]{9,16}$/', $candidate)) {
                return $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private function gatewayCustomerPhone(Request $request): string
    {
        $phone = $this->normalizeOrderContactPhone($request);

        return $phone !== '' ? $phone : '08000000000';
    }

    private function gatewayCustomerEmail(Request $request): string
    {
        $email = Auth::check() ? trim((string) Auth::user()->email) : '';

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = trim((string) $request->email);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'customer@example.com';
    }

    private function resolveProviderContextForOrder($dataLayanan, array $override = []): array
    {
        $context = [
            'layanan_id' => isset($dataLayanan->id) ? (int) $dataLayanan->id : null,
            'provider_code' => null,
            'provider_sku' => null,
            'modal_price' => null,
        ];

        $overrideCode = strtolower(trim((string) ($override['provider_code'] ?? '')));
        $overrideSku = trim((string) ($override['provider_sku'] ?? ''));

        if ($overrideCode !== '' && $overrideSku !== '') {
            $context['provider_code'] = $overrideCode;
            $context['provider_sku'] = $overrideSku;
        }

        if (array_key_exists('modal_price', $override) && is_numeric($override['modal_price'])) {
            $context['modal_price'] = max(0, (int) round((float) $override['modal_price']));
        }

        $layananId = (int) ($context['layanan_id'] ?? 0);

        if ($layananId > 0) {
            $layanan = Layanan::query()->find($layananId);

            if ($layanan) {
                if (blank($context['provider_code']) || blank($context['provider_sku'])) {
                    $route = app(\App\Services\ProviderRoutingService::class)->findBestProvider($layanan);

                    if ($route) {
                        $context['provider_code'] = strtolower((string) ($route['provider_code'] ?? ''));
                        $context['provider_sku'] = trim((string) ($route['sku'] ?? ''));
                    }
                }

                if (
                    $context['modal_price'] === null &&
                    filled($context['provider_code']) &&
                    filled($context['provider_sku'])
                ) {
                    $matchedPath = $layanan->provider_paths()
                        ->where('provider_code', $context['provider_code'])
                        ->where('provider_sku', $context['provider_sku'])
                        ->orderBy('priority')
                        ->orderBy('modal_price')
                        ->first();

                    if ($matchedPath && $matchedPath->modal_price !== null) {
                        $context['modal_price'] = max(0, (int) round((float) $matchedPath->modal_price));
                    }
                }
            }
        }

        if ($context['modal_price'] === null && isset($dataLayanan->modal_harga) && is_numeric($dataLayanan->modal_harga)) {
            $context['modal_price'] = max(0, (int) round((float) $dataLayanan->modal_harga));
        }

        if (blank($context['provider_code']) && isset($dataLayanan->provider)) {
            $context['provider_code'] = strtolower(trim((string) $dataLayanan->provider));
        }

        if (blank($context['provider_sku']) && isset($dataLayanan->provider_id)) {
            $context['provider_sku'] = trim((string) $dataLayanan->provider_id);
        }

        return $context;
    }

    private function calculateOrderProfitAmount(int $amount, $dataLayanan, array $providerContext, array $gatewayMeta = [], $dataMethod = null): int
    {
        $normalizedAmount = max(0, $amount);

        if ($this->hasTenantContext() && is_numeric($dataLayanan->tenant_profit ?? null)) {
            return max(0, (int) round((float) $dataLayanan->tenant_profit));
        }

        $gatewayFeeAmount = $this->resolveGatewayFeeForProfit($normalizedAmount, $gatewayMeta, $dataMethod);
        $netRevenue = max(0, $normalizedAmount - $gatewayFeeAmount);

        if (is_numeric($providerContext['modal_price'] ?? null)) {
            $modal = max(0, (int) round((float) $providerContext['modal_price']));

            return max(0, $netRevenue - $modal);
        }

        return max(0, (int) round($netRevenue * ((float) ($dataLayanan->profit ?? 0) / 100)));
    }

    private function resolveGatewayFeeForProfit(int $amount, array $gatewayMeta, $dataMethod): int
    {
        if (is_numeric($gatewayMeta['gateway_fee_amount'] ?? null)) {
            return max(0, min($amount, (int) round((float) $gatewayMeta['gateway_fee_amount'])));
        }

        $baseServiceAmount = is_numeric($gatewayMeta['base_service_amount'] ?? null)
            ? max(0, (int) round((float) $gatewayMeta['base_service_amount']))
            : null;

        if ($baseServiceAmount !== null) {
            $configuredFee = $this->calculateMethodFeeAmount($baseServiceAmount, $dataMethod);

            return max(0, min($amount, $configuredFee));
        }

        if (! $dataMethod) {
            return 0;
        }

        $percent = max(0, (float) ($dataMethod->fee_percent ?? 0));
        $fixed = max(0, (float) ($dataMethod->fix_fee ?? 0));

        if ($percent <= 0 && $fixed <= 0) {
            return 0;
        }

        $denominator = 1 + ($percent / 100);
        if ($denominator <= 0) {
            return 0;
        }

        $estimatedBase = max(0, ((float) $amount - $fixed) / $denominator);
        $estimatedFee = (int) round($fixed + ($estimatedBase * ($percent / 100)));

        return max(0, min($amount, $estimatedFee));
    }

    private function resolvePaymentExpiryAt(array $gatewayMeta, $dataMethod, string $paymentStatus): ?Carbon
    {
        if (strtolower($paymentStatus) !== 'belum lunas') {
            return null;
        }

        $candidates = [
            $gatewayMeta['expired_at'] ?? null,
            $gatewayMeta['expires_at'] ?? null,
            $gatewayMeta['expired_time'] ?? null,
            $gatewayMeta['expired_ts'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (blank($candidate)) {
                continue;
            }

            if (is_numeric($candidate)) {
                $timestamp = (int) $candidate;

                // Normalize millisecond epoch values from some gateways.
                if ($timestamp > 9_999_999_999) {
                    $timestamp = (int) floor($timestamp / 1000);
                }

                return Carbon::createFromTimestamp($timestamp, config('app.timezone'));
            }

            try {
                return Carbon::parse($candidate, config('app.timezone'));
            } catch (\Throwable) {
                continue;
            }
        }

        return match (strtolower((string) ($dataMethod->payment ?? ''))) {
            'duitku' => now()->addHours(3),
            'tripay' => now()->addHours(24),
            'tokopay' => now()->addHours(3),
            default => now()->addHours(3),
        };
    }

    private function buildManualGatewayResult(string $orderId, int $amount, $dataMethod): array
    {
        $settings = \App\Models\SettingWeb::query()->first();
        $paymentCode = Str::upper(trim((string) ($dataMethod->code ?? '')));
        $methodName = trim((string) ($dataMethod->name ?? 'Pembayaran Manual'));

        $paymentTarget = match ($paymentCode) {
            'MANUAL_BANK' => trim((string) ($settings->bca_admin ?? '')),
            'MANUAL_EWALLET' => trim((string) ($settings->dana_admin ?? $settings->ovo_admin ?? $settings->gopay_admin ?? '')),
            default => trim((string) ($settings->nomor_admin ?? $settings->wa_number ?? '')),
        };

        if ($paymentTarget === '') {
            $paymentTarget = 'Hubungi admin demo untuk instruksi pembayaran';
        }

        $accountHolder = trim((string) ($settings->judul_web ?? config('app.name', 'Demo Topup')));
        $displayValue = $paymentTarget;

        if (
            ! str_contains(Str::lower($paymentTarget), 'hubungi') &&
            ! str_contains(Str::lower($paymentTarget), 'admin') &&
            $accountHolder !== ''
        ) {
            $displayValue = sprintf('%s a.n. %s', $paymentTarget, $accountHolder);
        }

        return [
            'status' => true,
            'no_pembayaran' => $displayValue,
            'reference' => 'MANUAL-' . $orderId,
            'amount' => $amount,
            'expired_at' => now()->addHours(12),
            'msg' => sprintf('%s siap digunakan.', $methodName),
        ];
    }
}

