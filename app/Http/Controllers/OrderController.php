<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Voucher;
use App\Models\Pembelian;
use App\Models\Rating;
use App\Models\Paket;
use App\Models\PaketLayanan;
use App\Models\User;
use App\Models\Method;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DigiFlazzController;
use App\Http\Controllers\ApiCheckController;
use App\Http\Controllers\PaydisiniController;
use App\Http\Controllers\TokoPayController;
use App\Http\Controllers\TriPayController;
use App\Http\Controllers\DuitkuPaymentController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\TopupediaController;
use App\Http\Controllers\provider\MoogoldController;
use App\Http\Controllers\Public\TransactionLookupPageController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Support\GtmDataLayerBuilder;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;
use App\Libraries\Provider\ElitediasProvider;
use App\Support\CustomInputDefaults;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    private const ORDER_IDEMPOTENCY_LOCK_SECONDS = 180;
    private const ORDER_IDEMPOTENCY_RESULT_SECONDS = 600;

    public function create(Kategori $kategori)
    {
        app(CustomInputDefaults::class)->ensureExists($kategori);

        $role = Auth::check() ? Auth::user()->role : 'Guest';
        $cacheKey = "order_page:{$kategori->kode}:{$role}";
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
        $pay_method = Cache::remember('payment_methods_all', 300, function() {
            return \App\Models\Method::all();
        });

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
            'gtmViewItemPayload' => $gtmViewItemPayload,
            'gtmOrderItemCatalog' => $gtmOrderItemCatalog,
            'gtmPaymentMethodCatalog' => $gtmPaymentMethodCatalog,
        ]);
    }

    public function price(Request $request)
    {
        if (Auth::check()) {
            if (Auth::user()->role == "Member") {
                $data = Layanan::where('id', $request->nominal)
                    ->select('harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                    ->first();
            } elseif (Auth::user()->role == "Platinum") {
                $data = Layanan::where('id', $request->nominal)
                    ->select('harga_platinum AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                    ->first();
            } elseif (Auth::user()->role == "Gold" || Auth::user()->role == "Admin") {
                $data = Layanan::where('id', $request->nominal)
                    ->select('harga_gold AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                    ->first();
            }
        } else {
            $data = Layanan::where('id', $request->nominal)
                ->select('harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                ->first();
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


        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            $data->harga -= $this->calculateVoucherDiscountAmount($data->harga, $voucher);
        }

        // OPTIMIZATION: Cache methods query for 60 minutes to reduce DB load
        $methods = \Illuminate\Support\Facades\Cache::remember('payment_methods_price_calc', 60 * 60, function () {
            return Method::select('code', 'fee_percent', 'fix_fee', 'min_pembelian', 'max_pembelian')
                ->get()
                ->keyBy('code');
        });

        $selectedMethod = null;
        if ($request->filled('payment_method')) {
            $selectedMethod = Method::select('code', 'payment', 'fee_percent', 'fix_fee', 'min_pembelian', 'max_pembelian')
                ->where('code', $request->payment_method)
                ->first();
        }

        $amountBeforePoint = (int) round($data->harga + $this->calculateMethodFeeAmount($data->harga, $selectedMethod));
        $pointUsage = $this->resolvePointUsage($amountBeforePoint, (int) $request->use_point);
        $selectedFinalPrice = max(1000, $amountBeforePoint - $pointUsage['discount']);

        return response()->json([
            'status'         => true,
            'harga'          => $data->harga,
            'methods'        => $methods,
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

    private function calculateVoucherDiscountAmount(float|int $basePrice, ?Voucher $voucher): int
    {
        if (!$voucher || (int) $voucher->stock <= 0) {
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
        $rules = [
            'service' => 'required|integer|exists:layanans,id',
            'payment_method' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $code = trim((string) $value);
                    if (Str::upper($code) === 'SALDO') {
                        return;
                    }

                    if (!Method::query()->where('code', $code)->exists()) {
                        $fail('The selected payment method is invalid.');
                    }
                },
            ],
            'nomor' => ['required', 'regex:/^[0-9]{9,16}$/'],
            'voucher' => 'nullable|string|max:100',
            'ktg_tipe' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'idempotency_key' => 'nullable|string|max:120',
        ];

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
            $rules += [
                'email_joki' => 'required|string|max:255',
                'password_joki' => 'required|string|max:255',
                'loginvia_joki' => 'required|string|max:255',
                'nickname_joki' => 'required|string|max:255',
                'request_joki' => 'required|string|max:255',
                'catatan_joki' => 'required|string|max:255',
                'qty' => ($forConfirmation ? 'nullable' : 'required') . '|integer|min:1|max:30',
            ];
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
        if (Auth::check()) {
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
        // voucher
        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            $dataLayanan->harga -= $this->calculateVoucherDiscountAmount($dataLayanan->harga, $voucher);
        }


        $dataKategori = Kategori::where('id', $dataLayanan->kategori_id)->select('kode')->first();
        $apicheck = new ApiCheckController();

        $daftarGameValidasi = [
            'arena-breakout',
            'mobile-legends',
            'free-fire',
            '8-ball-pool',
            'point-blank',
            'arena-of-valor',
            'genshin-impact',
            'dragon-raja',
            'valorant',
            'metal-slug-awakening',
            'sausage-man',
            'ea-sports-fc-mobile',
            'undawn',
            'call-of-duty-mobile',
            'pubg-mobile-tp',
            'honor-of-kings-tp',
            'honkai-star-rail',
            'steam-wallet-code-indonesia',
            'free-fire-max',
            'astra-knights-of-veda ',
            'au2-mobile',
            'advent-of-godlegends',
            'aether-gazer',
            'among-heroes-fantasy-samkok',
            'angel-squad-dg',
            'aov-dg',
            'arcane-saga',
            'arena-breakout',
            'arena-mania-magic-heroes',
            'asphalt-9-legends',
            'atlantica-online-dg',
            'astral-guardians-cyber-fantasy',
            'auto-chess',
            'azur-lane',
            'bleach-mobile-3d',
            'badlanders',
            'barbarq',
            'battlenet-dg',
            'be-the-king-judge-destiny',
            'bermuda',
            'bigo-live',
            'bigo-live-voucher',
            'Bilibili-dg',
            'bioskop-online',
            'blade-x-odyssey-of-heroes',
            'bleach-mobile-3d-dg',
            'blizzard-gift-card-dg',
            'blood-strike',
            'boxing-star-dg',
            'captain-tsubasa-ace',
            'captain-tsubasa-dream-team',
            'city-of-crime-gang-wars',
            'clash-royale',
            'clash-of-clans',
            'cooking-adventure',
            'crasher-origin',
            'dead-target-zombie-games-3d',
            'dg-mini-games-dg',
            'dark-continent-mist',
            'diablo-immortal',
            'garena-dg',
            'ragnarok-m-eternal-love-big-cat-coin',
            'laplace-m',
            'speed-drifters',
            'era-of-celestials',
            'higgs-domino',
            'heroes-evolved',
            'lifeafter',
            'marvel-snap',
            'hago',
            'tom-and-jerry-chase',
            'one-punch-man-the-strongest',
            'dragon-raja',
            'ludo-club',
            'league-of-legends',
            'league-of-legends-wild-rift-dg',
            'state-of-survival',
            'ys-6-mobile-vng',
            'tower-of-fantasy-a',
            'stumble-guys',
            'honkai-impact-3',
            'goddes-victory-nikke-tp',
            'ragnarok-x-next-generation',
            'revelation-infinite-journey',
            'lita',
            'teen-patti-gold',
            'hay-day',
            'zepeto',
            'kings-choice',
            'harry-potter-magic-awakened',
            'life-makeover',
            'brawl-stars',
            'growtopia',
            'identity-v',
            'farlight-84',
            'football-master-2',
            'eos-red',
            'eggy-party',
            'snowbreak-containment-zone',
            'rhythm-hive',
            'asphalt-9-legends',
            'teamfight-tactics-mobile',
            'blood-strike',
            'punishing-gray-raven',
            'octopath-traveler-cotc',
            'love-and-deepspace',
            'pixel-gun-3d',
            'the-legend-of-neverland-dg',
            'heroic-uncle-kim-idle-rpg',
            'world-war-heroes',
            'moonlight-blade-m',
            'king-of-avalon'
        ];

        if (in_array($dataKategori->kode, $daftarGameValidasi)) {
            $data = [];
            if ($dataKategori->kode == 'mobile-legends') {
                $data = $apicheck->check($request->uid, $request->zone, 'Mobile Legends');
            } else if ($dataKategori->kode == "free-fire") {
                $data = $apicheck->check($request->uid, null, 'Free Fire');
            } else if ($dataKategori->kode == "free-fire-max") {
                $data = $apicheck->check($request->uid, null, 'Free Fire MAX');
            } else if ($dataKategori->kode == "honkai-star-rail") {
                $data = $apicheck->check($request->uid, $request->zone, 'Honkai: Star Rail');
            } else if ($dataKategori->kode == "pubg-mobile-tp") {
                $data = $apicheck->check($request->uid, null, 'PUBG Mobile');
            } else if ($dataKategori->kode == "honor-of-kings-tp") {
                $data = $apicheck->check($request->uid, null, 'Honor of Kings');
            } else if ($dataKategori->kode == "point-blank") {
                $data = $apicheck->check($request->uid, null, 'Point Blank');
            } else if ($dataKategori->kode == "arena-of-valor") {
                $data = $apicheck->check($request->uid, null, 'Arena of Valor');
            } else if ($dataKategori->kode == "genshin-impact") {
                $data = $apicheck->check($request->uid, null, 'Genshin Impact');
            } else if ($dataKategori->kode == "dragon-raja") {
                $data = $apicheck->check($request->uid, null, 'Dragon Raja');
            } else if ($dataKategori->kode == "super-sus") {
                $data = $apicheck->check($request->uid, null, 'Super Sus');
            } elseif ($dataKategori->kode == "call-of-duty-mobile") {
                $data = $apicheck->check($request->uid, null, 'Call of Duty Mobile');
            } elseif ($dataKategori->kode == "8-ball-pool") {
                $data = $apicheck->check($request->uid, null, '8 Ball Pool');
            } elseif ($dataKategori->kode == "valorant") {
                $data = $apicheck->check($request->uid, null, 'Valorant');
            } elseif ($dataKategori->kode == "metal-slug-awakening") {
                $data = $apicheck->check($request->uid, null, 'Metal Slug Awakening');
            } elseif ($dataKategori->kode == "sausage-man") {
                $data = $apicheck->check($request->uid, null, 'Sausage Man');
            } elseif ($dataKategori->kode == "ea-sports-fc-mobile") {
                $data = $apicheck->check($request->uid, null, 'FC Mobile');
            } elseif ($dataKategori->kode == "undawn") {
                $data = $apicheck->check($request->uid, null, 'Undawn');
            } elseif ($dataKategori->kode == "steam-wallet-code-indonesia") {
                $data = $apicheck->check($request->uid, null, 'Steam Wallet Code - Indonesia');
            } elseif ($dataKategori->kode == "astra-knights-of-veda") {
                $data = $apicheck->check($request->uid, $request->zone, 'ASTRA: Knights of Veda');
            } elseif ($dataKategori->kode == "au2-mobile") {
                $data = $apicheck->check($request->uid, null, 'AU2 Mobile');
            } elseif ($dataKategori->kode == "advent-of-godlegends") {
                $data = $apicheck->check($request->uid, $request->zone, 'Advent of God:Legends');
            } elseif ($dataKategori->kode == "aether-gazer") {
                $data = $apicheck->check($request->uid, null, 'Aether Gazer');
            } elseif ($dataKategori->kode == "among-heroes-fantasy-samkok") {
                $data = $apicheck->check($request->uid, $request->zone, 'Among Heroes: Fantasy Samkok');
            } elseif ($dataKategori->kode == "angel-squad-dg") {
                $data = $apicheck->check($request->uid, null, 'Angel Squad (DG)');
            } elseif ($dataKategori->kode == "aov-dg") {
                $data = $apicheck->check($request->uid, null, 'AoV (DG)');
            } elseif ($dataKategori->kode == "arcane-saga") {
                $data = $apicheck->check($request->uid, null, 'Arcane Saga');
            } elseif ($dataKategori->kode == "arena-breakout") {
                $data = $apicheck->check($request->uid, null, 'Arena Breakout');
            } elseif ($dataKategori->kode == "arena-mania-magic-heroes") {
                $data = $apicheck->check($request->uid, $request->zone, 'Arena Mania: Magic Heroes');
            } elseif ($dataKategori->kode == "asphalt-9-legends") {
                $data = $apicheck->check($request->uid, $request->zone, 'Asphalt 9: Legends');
            } elseif ($dataKategori->kode == "astral-guardians-cyber-fantasy") {
                $data = $apicheck->check($request->uid, $request->zone, 'Astral Guardians: Cyber Fantasy');
            } elseif ($dataKategori->kode == "atlantica-online-dg") {
                $data = $apicheck->check($request->uid, null, 'Atlantica Online (DG)');
            } elseif ($dataKategori->kode == "auto-chess") {
                $data = $apicheck->check($request->uid, null, 'Auto Chess ');
            } elseif ($dataKategori->kode == "azur-lane") {
                $data = $apicheck->check($request->uid, $request->zone, 'Azur Lane');
            } elseif ($dataKategori->kode == "bleach-mobile-3d") {
                $data = $apicheck->check($request->uid, $request->zpne, 'BLEACH Mobile 3D');
            } elseif ($dataKategori->kode == "badlanders") {
                $data = $apicheck->check($request->uid, $request->zone, 'Badlanders');
            } elseif ($dataKategori->kode == "barbarq") {
                $data = $apicheck->check($request->uid, $request->zone, 'BarbarQ');
            } elseif ($dataKategori->kode == "battlenet-dg") {
                $data = $apicheck->check($request->uid, null, 'Battlenet (DG)');
            } elseif ($dataKategori->kode == "be-the-king-judge-destiny") {
                $data = $apicheck->check($request->uid, $request->zone, 'Be The King: Judge Destiny');
            } elseif ($dataKategori->kode == "bigo-live") {
                $data = $apicheck->check($request->uid, null, 'Bigo Live');
            } elseif ($dataKategori->kode == "bigo-live-voucher") {
                $data = $apicheck->check($request->uid, null, 'Bigo Live Voucher');
            } elseif ($dataKategori->kode == "Bilibili-dg") {
                $data = $apicheck->check($request->uid, null, 'Bilibili (DG)');
            } elseif ($dataKategori->kode == "bioskop-online") {
                $data = $apicheck->check($request->uid, null, 'Bioskop Online');
            } elseif ($dataKategori->kode == "blade-x-odyssey-of-heroes") {
                $data = $apicheck->check($request->uid, null, 'Blade X: Odyssey of Heroes');
            } elseif ($dataKategori->kode == "bleach-mobile-3d-dg") {
                $data = $apicheck->check($request->uid, $request->zone, 'Bleach Mobile 3D (DG)');
            } elseif ($dataKategori->kode == "blizzard-gift-card-dg") {
                $data = $apicheck->check($request->uid, null, 'Blizzard Gift Card (DG)');
            } elseif ($dataKategori->kode == "blood-strike") {
                $data = $apicheck->check($request->uid, $request->zone == 1 ? 1 : null, 'Blood Strike');
            } elseif ($dataKategori->kode == "boxing-star-dg") {
                $data = $apicheck->check($request->uid, null, 'Boxing Star (DG)');
            } elseif ($dataKategori->kode == "brawl-stars") {
                $data = $apicheck->check($request->uid, null, 'Brawl Stars');
            } elseif ($dataKategori->kode == "captain-tsubasa-ace") {
                $data = $apicheck->check($request->uid, null, 'Captain Tsubasa: Ace');
            } elseif ($dataKategori->kode == "captain-tsubasa-dream-team") {
                $data = $apicheck->check($request->uid, null, 'Captain Tsubasa: Dream Team');
            } elseif ($dataKategori->kode == "city-of-crime-gang-wars") {
                $data = $apicheck->check($request->uid, null, 'City of Crime: Gang Wars');
            } elseif ($dataKategori->kode == "clash-royale") {
                $data = $apicheck->check($request->uid, null, 'Clash Royale');
            } elseif ($dataKategori->kode == "clash-of-clans") {
                $data = $apicheck->check($request->uid, null, 'Clash of Clans');
            } elseif ($dataKategori->kode == "cloud-song-saga-of-skywalkers") {
                $data = $apicheck->check($request->uid, null, 'Cloud Song: Saga of Skywalkers');
            } elseif ($dataKategori->kode == "cooking-adventure") {
                $data = $apicheck->check($request->uid, $request->zone, 'Cooking Adventure');
            } elseif ($dataKategori->kode == "crasher-origin") {
                $data = $apicheck->check($request->uid, $request->zone, 'Crasher Origin');
            } elseif ($dataKategori->kode == "dead-target-zombie-games-3d") {
                $data = $apicheck->check($request->uid, null, 'DEAD TARGET: Zombie Games 3D');
            } elseif ($dataKategori->kode == "dg-mini-games-dg") {
                $data = $apicheck->check($request->uid, null, 'DG Mini Games (DG)');
            } elseif ($dataKategori->kode == "dark-continent-mist") {
                $data = $apicheck->check($request->uid, $request->zone, 'Dark Continent: Mist');
            } elseif ($dataKategori->kode == "diablo-immortal") {
                $data = $apicheck->check($request->uid, null, 'Diablo: Immortal');
            } elseif ($dataKategori->kode == "discord-subscription") {
                $data = $apicheck->check($request->uid, null, 'Discord Subscription');
            } elseif ($dataKategori->kode == "garena-dg") {
                $data = $apicheck->check($request->uid, null, 'Top Up Garena Shell (DG)');
            } elseif ($dataKategori->kode == "ragnarok-m-eternal-love-big-cat-coin") {
                $data = $apicheck->check($request->uid, null, 'Ragnarok M: Eternal Love Big Cat Coin');
            } elseif ($dataKategori->kode == "laplace-m") {
                $data = $apicheck->check($request->uid, null, 'Laplace M');
            } elseif ($dataKategori->kode == "speed-drifters") {
                $data = $apicheck->check($request->uid, null, 'Speed Drifters');
            } elseif ($dataKategori->kode == "era-of-celestials") {
                $data = $apicheck->check($request->uid, $request->zone, 'Era of Celestials');
            } elseif ($dataKategori->kode == "higgs-domino") {
                $data = $apicheck->check($request->uid, null, 'Higgs Domino');
            } elseif ($dataKategori->kode == "heroes-evolved") {
                $data = $apicheck->check($request->uid, null, 'Heroes Evolved');
            } elseif ($dataKategori->kode == "lifeafter") {
                $data = $apicheck->check($request->uid, $request->zone, 'LifeAfter');
            } elseif ($dataKategori->kode == "scroll-of-onmyoji-sakura-and-sword") {
                $data = $apicheck->check($request->uid, $request->zone, 'Scroll of Onmyoji: Sakura & Sword');
            } elseif ($dataKategori->kode == "marvel-snap") {
                $data = $apicheck->check($request->uid, null, 'MARVEL SNAP');
            } elseif ($dataKategori->kode == "hago") {
                $data = $apicheck->check($request->uid, null, 'Hago');
            } elseif ($dataKategori->kode == "tom-and-jerry-chase") {
                $data = $apicheck->check($request->uid, $request->zone, 'Tom and Jerry: Chase');
            } elseif ($dataKategori->kode == "one-punch-man-the-strongest") {
                $data = $apicheck->check($request->uid, null, 'ONE PUNCH MAN: The Strongest');
            } elseif ($dataKategori->kode == "dragon-raja") {
                $data = $apicheck->check($request->uid, null, 'Dragon Raja');
            } elseif ($dataKategori->kode == "ludo-club") {
                $data = $apicheck->check($request->uid, null, 'Ludo Club');
            } elseif ($dataKategori->kode == "league-of-legends-wild-rift-dg") {
                $data = $apicheck->check($request->uid, null, 'League of Legends : Wild Rift (DG)');
            } elseif ($dataKategori->kode == "league-of-legends") {
                $data = $apicheck->check($request->uid, null, 'League of Legends');
            } elseif ($dataKategori->kode == "state-of-survival") {
                $data = $apicheck->check($request->uid, null, 'State of Survival');
            } elseif ($dataKategori->kode == "ys-6-mobile-vng") {
                $data = $apicheck->check($request->uid, null, 'YS 6 Mobile VNG');
            } elseif ($dataKategori->kode == "tower-of-fantasy-a") {
                $data = $apicheck->check($request->uid, null, 'Tower of Fantasy (Slow)');
            } elseif ($dataKategori->kode == "mu-origin-3") {
                $data = $apicheck->check($request->uid, null, 'MU ORIGIN 3');
            } elseif ($dataKategori->kode == "stumble-guys") {
                $data = $apicheck->check($request->uid, null, 'Stumble Guys');
            } elseif ($dataKategori->kode == "honkai-impact-3") {
                $data = $apicheck->check($request->uid, null, 'Honkai Impact 3');
            } elseif ($dataKategori->kode == "goddes-victory-nikke-tp") {
                $data = $apicheck->check($request->uid, $request->zone, 'Goddes Victory: Nikke (FAST)');
            } elseif ($dataKategori->kode == "ragnarok-retro-dg") {
                $data = $apicheck->check($request->uid, null, 'Ragnarok Retro (DG)');
            } elseif ($dataKategori->kode == "ragnarok-x-next-generation") {
                $data = $apicheck->check($request->uid, $request->zone, 'Ragnarok X: Next Generation');
            } elseif ($dataKategori->kode == "revelation-infinite-journey") {
                $data = $apicheck->check($request->uid, null, 'Revelation: Infinite Journey');
            } elseif ($dataKategori->kode == "lita") {
                $data = $apicheck->check($request->uid, null, 'Lita');
            } elseif ($dataKategori->kode == "teen-patti-gold") {
                $data = $apicheck->check($request->uid, null, 'Teen Patti Gold');
            } elseif ($dataKategori->kode == "hay-day") {
                $data = $apicheck->check($request->uid, null, 'Hay Day');
            } elseif ($dataKategori->kode == "zepeto") {
                $data = $apicheck->check($request->uid, null, 'ZEPETO');
            } elseif ($dataKategori->kode == "kings-choice") {
                $data = $apicheck->check($request->uid, null, 'Kings Choice');
            } elseif ($dataKategori->kode == "harry-potter-magic-awakened") {
                $data = $apicheck->check($request->uid, $request->zone, 'Harry Potter: Magic Awakened');
            } elseif ($dataKategori->kode == "life-makeover") {
                $data = $apicheck->check($request->uid, null, 'Life Makeover');
            } elseif ($dataKategori->kode == "brawl-stars") {
                $data = $apicheck->check($request->uid, null, 'Brawl Stars');
            } elseif ($dataKategori->kode == "growtopia") {
                $data = $apicheck->check($request->uid, null, 'Growtopia');
            } elseif ($dataKategori->kode == "identity-v") {
                $data = $apicheck->check($request->uid, null, 'Identity V');
            } elseif ($dataKategori->kode == "farlight-84") {
                $data = $apicheck->check($request->uid, null, 'Farlight 84');
            } elseif ($dataKategori->kode == "football-master-2") {
                $data = $apicheck->check($request->uid, null, 'Football Master 2');
            } elseif ($dataKategori->kode == "eos-red") {
                $data = $apicheck->check($request->uid, $request->zone, 'EOS RED');
            } elseif ($dataKategori->kode == "eggy-party") {
                $data = $apicheck->check($request->uid, null, 'EGGY PARTY');
            } elseif ($dataKategori->kode == "snowbreak-containment-zone") {
                $data = $apicheck->check($request->uid, $request->zone, 'Snowbreak: Containment Zone');
            } elseif ($dataKategori->kode == "rhythm-hive") {
                $data = $apicheck->check($request->uid, null, 'Rhythm Hive');
            } elseif ($dataKategori->kode == "asphalt-9-legends") {
                $data = $apicheck->check($request->uid, null, 'Asphalt 9: Legends');
            } elseif ($dataKategori->kode == "teamfight-tactics-mobile") {
                $data = $apicheck->check($request->uid, null, 'Teamfight Tactics Mobile');
            } elseif ($dataKategori->kode == "blood-strike") {
                $data = $apicheck->check($request->uid, $request->zone, 'Blood Strike');
            } elseif ($dataKategori->kode == "punishing-gray-raven") {
                $data = $apicheck->check($request->uid, $request->zone, 'Punishing: Gray Raven');
            } elseif ($dataKategori->kode == "octopath-traveler-cotc") {
                $data = $apicheck->check($request->uid, $request->zone, 'OCTOPATH TRAVELER: CotC');
            } elseif ($dataKategori->kode == "love-and-deepspace") {
                $data = $apicheck->check($request->uid, null, 'Love and Deepspace');
            } elseif ($dataKategori->kode == "pixel-gun-3d") {
                $data = $apicheck->check($request->uid, null, 'Pixel Gun 3D');
            } elseif ($dataKategori->kode == "the-legend-of-neverland-dg") {
                $data = $apicheck->check($request->uid, null, 'The Legend of Neverland (DG)');
            } elseif ($dataKategori->kode == "heroic-uncle-kim-idle-rpg") {
                $data = $apicheck->check($request->uid, null, 'Heroic Uncle Kim: Idle RPG');
            } elseif ($dataKategori->kode == "world-war-heroes") {
                $data = $apicheck->check($request->uid, null, 'World War Heroes');
            } elseif ($dataKategori->kode == "moonlight-blade-m") {
                $data = $apicheck->check($request->uid, null, 'Moonlight Blade M');
            } elseif ($dataKategori->kode == "king-of-avalon") {
                $data = $apicheck->check($request->uid, null, 'King of Avalon');
            } else {
            $data = $apicheck->check($request->uid, $request->zone, $dataKategori->kode);
            }
            
            if (!isset($data['status']['code']) || $data['status']['code'] !== 200 || empty($data['data']['username'])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User ID tidak ditemukan atau tidak valid. Silakan periksa kembali.'
                ]);
            }

            $username = isset($data['data']['username']) ? $data['data']['username'] : 'Not Found.';


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
            if (Auth::check()) {
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
                ->select('id', 'layanan', "$column AS harga", 'harga AS modal_harga', 'kategori_id', 'provider_id', 'provider', "$profitCol AS profit", 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                ->first();
            } else {
            $dataLayanan = Layanan::where('id', $request->service)
                ->select('id', 'layanan', 'harga_member AS harga', 'harga AS modal_harga', 'kategori_id', 'provider_id', 'provider', 'profit_member AS profit', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                ->first();
            }

        if (!$dataLayanan) {
            return $this->orderErrorResponse('Layanan tidak ditemukan', 'SERVICE_NOT_FOUND');
        }

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

        // Voucher Logic (Calculation Only)
        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            Log::debug('Voucher found', [
                'voucher_code' => $request->voucher,
                'voucher_id' => $voucher->id ?? null,
                'voucher_stock' => $voucher->stock ?? null,
            ]);
            if ($voucher && $voucher->stock > 0) {
                if ($voucher->mintrx && $dataLayanan->harga < $voucher->mintrx) {
                    return $this->orderErrorResponse(
                        'Minimal transaksi untuk voucher ini adalah Rp ' . number_format($voucher->mintrx, 0, ',', '.'),
                        'VOUCHER_MIN_TRANSACTION'
                    );
                }

                $dataLayanan->harga = round($dataLayanan->harga - $this->calculateVoucherDiscountAmount($dataLayanan->harga, $voucher));
            }
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

        // Generate Order ID — FIX #5: Tambah timestamp lebih panjang + uniqueness guard
        $setting = DB::table('setting_webs')->where('id', 1)->first();
        $prefix   = $setting->order_prefik ?? 'TRX';
        $order_id = $prefix . now()->format('ymdHis') . Str::upper(Str::random(6));
        // Pastikan unik di DB (collision guard)
        while (Pembelian::where('order_id', $order_id)->exists()) {
            $order_id = $prefix . now()->format('ymdHis') . Str::upper(Str::random(6));
        }

        $pointsReserved = false;
        if ($usedPoints > 0 && Auth::check()) {
            $reservedAmount = app(\App\Services\PointService::class)->redeemPoints(
                Auth::user(),
                $usedPoints,
                $order_id,
                $dataLayanan->layanan
            );

            if ($reservedAmount <= 0) {
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
                    if (!$voucher || $voucher->stock <= 0) throw new \Exception('Voucher habis');
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

                $this->createOrderRecord(
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

                DB::commit();
                Cache::forget($userKey);

                // Send Success Message
                $pesanSukses = "*Pembelian Sukses*\n\nNo Invoice: *$order_id*\nLayanan: *$dataLayanan->layanan*\nID : *$request->uid*\nServer : *$request->zone*\nNickname : *$request->nickname*\nHarga: *Rp. " . number_format($dataLayanan->harga, 0, '.', ',') . "*\nStatus Pembelian: *Sukses*\nMetode Pembayaran: *SALDO*\n\n*Invoice* : " . env("APP_URL") . "/id/invoices/$order_id\n\nINI ADALAH PESAN OTOMATIS";
                $this->msg($request->nomor, $pesanSukses);
            } catch (\Exception $e) {
                DB::rollBack();
                Cache::forget($userKey);
                if ($pointsReserved && Auth::check()) {
                    app(\App\Services\PointService::class)->refundPoints(
                        Auth::user(),
                        $usedPoints,
                        $order_id,
                        $dataLayanan->layanan
                    );
                }
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
                    DB::transaction(function () use ($request) {
                        $voucherGw = Voucher::where('kode', $request->voucher)->lockForUpdate()->first();
                        if (!$voucherGw || $voucherGw->stock <= 0) {
                            throw new \Exception('Voucher tidak valid atau sudah habis');
                        }
                        $voucherGw->decrement('stock');
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
                // Parameters: $ref_id, $channel, $jumlah, $nickname, $phone_number, $service
                $res = $tokopay->createAdvanceOrder(
                    $order_id, 
                    $request->payment_method, 
                    $amount, 
                    $request->nickname ?? 'Guest', 
                    $request->nomor, 
                    $dataLayanan->layanan
                );
                
                if (isset($res['status']) && $res['status'] === 'Success') {
                    $gatewayResult = [
                        'status' => true,
                        'no_pembayaran' => $res['data']['nomor_va'] ?? $res['data']['qr_link'] ?? $res['data']['checkout_url'] ?? $res['data']['pay_url'] ?? null,
                        'reference' => $res['data']['trx_id'] ?? null,
                        'amount' => $res['data']['total_bayar'] ?? $amount,
                        'expired_at' => $res['data']['expired_at'] ?? $res['data']['expired_ts'] ?? null,
                    ];
                } else {
                     $gatewayResult['msg'] = $res['error_msg'] ?? 'Gagal membuat pesanan TokoPay';
                }
            } else if ($dataMethod->payment == "tripay") {
                $tripay = app(TriPayController::class);
                $tripayRequestAmount = $this->resolveGatewayRequestAmount($amount, $dataMethod);
                // TriPay requires a valid customer email. Do not rely on app/mail fallback as customer identity.
                $customerEmail = Auth::check() && filter_var(Auth::user()->email, FILTER_VALIDATE_EMAIL)
                    ? Auth::user()->email
                    : trim((string) $request->email);

                if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                    if ($pointsReserved && Auth::check()) {
                        app(\App\Services\PointService::class)->refundPoints(
                            Auth::user(),
                            $usedPoints,
                            $order_id,
                            $dataLayanan->layanan
                        );
                    }

                    return $this->orderErrorResponse(
                        'Email pembeli wajib diisi dengan format yang valid untuk metode pembayaran ini.',
                        'CUSTOMER_EMAIL_REQUIRED'
                    );
                }

                $res = $tripay->request($order_id, $tripayRequestAmount, $request->payment_method, $customerEmail, $request->nomor);

                if ($res['success']) {
                    $gatewayResult = [
                        'status' => true,
                        'no_pembayaran' => $res['no_pembayaran'],
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
                $tempOrder->username = auth()->user()->username ?? 'guest';
                $tempOrder->harga = $amount;
                $tempOrder->profit = $dataLayanan->profit ?? 0; // Required field
                $tempOrder->status = 'Pending'; // Will be updated after payment

                $duitku = app(DuitkuPaymentController::class);
                // Pass payment_method from request (Duitku method code: VC, BC, I1, etc)
                $duitkuMethodCode = $request->payment_method ?? ''; // Empty = user chooses at Duitku page
                $res = $duitku->createInvoice($tempOrder, $duitkuMethodCode);
                
                if ($res['success']) {
                    $gatewayResult = [
                        'status' => true,
                        // Priority: VA number > QR string > Payment URL > Reference
                        'no_pembayaran' => $res['vaNumber'] ?? $res['qrString'] ?? $res['paymentUrl'] ?? $res['reference'],
                        'reference' => $res['reference'],
                        'amount' => $amount,
                        'merchant_order_id' => $res['merchantOrderId'] ?? ('DUITKU-' . $order_id),
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
                if ($pointsReserved && Auth::check()) {
                    app(\App\Services\PointService::class)->refundPoints(
                        Auth::user(),
                        $usedPoints,
                        $order_id,
                        $dataLayanan->layanan
                    );
                }
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
            } catch (\Exception $e) {
                if ($pointsReserved && Auth::check()) {
                    app(\App\Services\PointService::class)->refundPoints(
                        Auth::user(),
                        $usedPoints,
                        $order_id,
                        $dataLayanan->layanan
                    );
                }

                Log::error('Order Store Create Record Failed', ['error' => $e->getMessage(), 'order_id' => $order_id]);
                return $this->orderErrorResponse($e->getMessage(), 'ORDER_RECORD_CREATE_FAILED');
            }

            $paymentExpiryAt = $this->resolvePaymentExpiryAt($gatewayResult, $dataMethod, 'Belum Lunas');
            $paymentExpiryLabel = $paymentExpiryAt
                ? $paymentExpiryAt->timezone(config('app.timezone'))->format('d/m/Y H:i')
                : '3 jam dari sekarang';

            // Send Pending Message
            $pesanPending = "*Menunggu Pembayaran*\n\nNo Invoice: *$order_id*\nLayanan: *$dataLayanan->layanan*\nID : *$request->uid*\nServer : *$request->zone*\nNickname : *$request->nickname*\nHarga: *Rp. " . number_format($amount, 0, '.', ',') . "*\nStatus: *Menunggu Pembayaran*\nMetode Pembayaran: *$dataMethod->name*\nKode Bayar / Nomor VA : *" . $no_pembayaran . "*\n\n*Harap Dibayar Sebelum $paymentExpiryLabel!*\n\n*Invoice* : " . env("APP_URL") . "/id/invoices/$order_id\n\nINI ADALAH PESAN OTOMATIS";
            $this->msg($request->nomor, $pesanPending);
        }

            Cache::put($idempotencyResultKey, $order_id, now()->addSeconds(self::ORDER_IDEMPOTENCY_RESULT_SECONDS));

            return $this->orderSuccessResponse($request, $order_id);
        } finally {
            Cache::forget($idempotencyLockKey);
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
            curl_close($curl);

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
            $promo = $request->promo ?? null;

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
        ]);

        $kategori = Kategori::select('kode', 'tipe')
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

        $kategoriKode = $kategori->kode;
        $uid = $request->uid;
        $zone = $request->zone;

        // 2. Define Supported Games
        $daftarGameValidasi = [
            'arena-breakout', 'mobile-legends', 'free-fire', '8-ball-pool', 'point-blank',
            'arena-of-valor', 'genshin-impact', 'dragon-raja', 'valorant', 'metal-slug-awakening',
            'sausage-man', 'ea-sports-fc-mobile', 'undawn', 'call-of-duty-mobile', 'pubg-mobile-tp',
            'honor-of-kings-tp', 'honkai-star-rail', 'steam-wallet-code-indonesia', 'free-fire-max',
            'astra-knights-of-veda ', 'au2-mobile', 'advent-of-godlegends', 'aether-gazer',
            'among-heroes-fantasy-samkok', 'angel-squad-dg', 'aov-dg', 'arcane-saga', 'arena-breakout',
            'arena-mania-magic-heroes', 'asphalt-9-legends', 'atlantica-online-dg',
            'astral-guardians-cyber-fantasy', 'auto-chess', 'azur-lane', 'bleach-mobile-3d',
            'badlanders', 'barbarq', 'battlenet-dg', 'be-the-king-judge-destiny', 'bermuda',
            'bigo-live', 'bigo-live-voucher', 'Bilibili-dg', 'bioskop-online', 'blade-x-odyssey-of-heroes',
            'bleach-mobile-3d-dg', 'blizzard-gift-card-dg', 'blood-strike', 'boxing-star-dg',
            'captain-tsubasa-ace', 'captain-tsubasa-dream-team', 'city-of-crime-gang-wars',
            'clash-royale', 'clash-of-clans', 'cooking-adventure', 'crasher-origin',
            'dead-target-zombie-games-3d', 'dg-mini-games-dg', 'dark-continent-mist',
            'diablo-immortal', 'garena-dg', 'ragnarok-m-eternal-love-big-cat-coin', 'laplace-m',
            'speed-drifters', 'era-of-celestials', 'higgs-domino', 'heroes-evolved', 'lifeafter',
            'marvel-snap', 'hago', 'tom-and-jerry-chase', 'one-punch-man-the-strongest', 'dragon-raja',
            'ludo-club', 'league-of-legends', 'league-of-legends-wild-rift-dg', 'state-of-survival',
            'ys-6-mobile-vng', 'tower-of-fantasy-a', 'stumble-guys', 'honkai-impact-3',
            'goddes-victory-nikke-tp', 'ragnarok-x-next-generation', 'revelation-infinite-journey',
            'lita', 'teen-patti-gold', 'hay-day', 'zepeto', 'kings-choice', 'harry-potter-magic-awakened',
            'life-makeover', 'brawl-stars', 'growtopia', 'identity-v', 'farlight-84', 'football-master-2',
            'eos-red', 'eggy-party', 'snowbreak-containment-zone', 'rhythm-hive', 'asphalt-9-legends',
            'teamfight-tactics-mobile', 'punishing-gray-raven', 'octopath-traveler-cotc',
            'love-and-deepspace', 'pixel-gun-3d', 'the-legend-of-neverland-dg', 'heroic-uncle-kim-idle-rpg',
            'world-war-heroes', 'moonlight-blade-m', 'king-of-avalon'
        ];

        if (!in_array($kategoriKode, $daftarGameValidasi)) {
             return response()->json([
                'status' => ['code' => 400, 'message' => 'Game not supported for validation']
            ]);
        }

        // 3. Map Category Code to Game Name for API
        $apicheck = new ApiCheckController();
        $data = [];

        // Simplified mapping based on common patterns
        switch($kategoriKode) {
            case 'mobile-legends': $gameName = 'Mobile Legends'; break;
            case 'free-fire': $gameName = 'Free Fire'; break;
            case 'free-fire-max': $gameName = 'Free Fire MAX'; break;
            case 'honkai-star-rail': $gameName = 'Honkai Star Rail'; break;
            case 'genshin-impact': $gameName = 'Genshin Impact'; break;
            case 'valorant': $gameName = 'Valorant'; break; 
            case 'pubg-mobile-tp': $gameName = 'PUBG Mobile'; break;
            case 'honor-of-kings-tp': $gameName = 'Honor of Kings'; break;
            case 'garena-dg': $gameName = 'Garena Shell'; break;
            case 'higgs-domino': $gameName = 'Higgs Domino'; break;
            default:
                 // Fallback: Try converting slug to Title Case
                 $gameName = ucwords(str_replace('-', ' ', $kategoriKode));
                 break;
        }

        // Use the API Check
        $data = $apicheck->check($uid, $zone, $gameName);
        
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

    private function createOrderRecord($request, $dataLayanan, $order_id, $amount, $dataMethod, $status_pembayaran, $no_pembayaran, $reference, $order_status, $provider_order_id = '', $order_log = '', $ipAddress, $tipe, $keteranganSn = null, $usedPoints = 0, $usedPointAmount = 0, array $gatewayMeta = [], array $providerContextOverride = []) {
        $user_id = Auth::check() ? Auth::user()->username : "Anonim"; // Consistent with original code
        $providerContext = $this->resolveProviderContextForOrder($dataLayanan, $providerContextOverride);
        
        $pembelian = new Pembelian();
        $pembelian->username = $user_id; 
        $pembelian->order_id = $order_id;
        
        // Define standard values
        $is_joki = in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml']);
        
        $pembelian->user_id = !$is_joki ? $request->uid : '-';
        $pembelian->zone = !$is_joki ? $request->zone : '-';
        $pembelian->nickname = !$is_joki ? $request->nickname : ($request->ktg_tipe !== 'joki' ? $request->nickname_joki : '-');
        
        $pembelian->log = $order_log;
        $pembelian->status = $order_status; // 'Pending' or 'Proses'
        $pembelian->tipe_transaksi = $tipe;
        
        $pembelian->layanan = $dataLayanan->layanan;
        $pembelian->harga = $amount;
        $pembelian->profit = $this->calculateOrderProfitAmount((int) $amount, $dataLayanan, $providerContext, $gatewayMeta, $dataMethod);
        $pembelian->active_layanan_id = $providerContext['layanan_id'];
        $pembelian->active_provider_code = $providerContext['provider_code'];
        $pembelian->active_provider_sku = $providerContext['provider_sku'];
        $pembelian->provider_order_id = $provider_order_id;
        $pembelian->ip_address = $ipAddress;
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
        $pembayaran->no_pembeli = $request->nomor;
        $pembayaran->status = $status_pembayaran; // 'Belum Lunas' or 'Lunas'
        $pembayaran->metode = $request->payment_method;
        $pembayaran->reference = $reference;
        $pembayaran->expired_at = $this->resolvePaymentExpiryAt($gatewayMeta, $dataMethod, $status_pembayaran);

        if (($dataMethod->payment ?? null) === 'duitku') {
            $pembayaran->duitku_reference = $reference;
            $pembayaran->duitku_merchant_order_id = $gatewayMeta['merchant_order_id'] ?? ('DUITKU-' . $order_id);
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
            'duitku' => now()->addMinutes(60),
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
