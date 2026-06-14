<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DigiFlazzController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Http\Controllers\provider\TopupediaController;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\MoogoldController;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;
use App\Libraries\Provider\ElitediasProvider;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Models\ProviderPath;
use App\Models\ResellerIntegration;
use App\Models\User;
use App\Support\PembelianStatus;
use App\Support\ResellerApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class OrderApiController extends Controller
{   
    

    
   public function balance(Request $request)
   {
        $user = $this->resolveApiUser($request);

        if (! $user instanceof User) {
            return $this->unauthenticatedResponse($request);
        }
        
        return response()->json([
              'error' => false,
              'code' => 200,
              'message' => "Success",
              'data' => [
                "name" => $user->name,
                "telp" => $user->no_wa,
                'membership' => $user->role,
                'balance' => $user->balance
              ]
        ], 200);
        
   }
   
   public function category(Request $request)
   {
        $user = $this->resolveApiUser($request);

        if (! $user instanceof User) {
            return $this->unauthenticatedResponse($request);
        }

       $product = Kategori::query()->orderBy('nama')->get();
       $list = [];

       foreach($product as $p){
           $list[] = [
              "code"      => $p->kode,
              "name"      => $p->nama,
              "type"      => $p->tipe,
              "is_active" => ($p->status == 'active'),
           ];
       }
       return response()->json([
           'error'   => false,
           'code'    => 200,
           'message' => 'Success',
           'data'    => $list,
      ], 200);
   }
   
   
   public function listVariant(Request $request)
   {
        $user = $this->resolveApiUser($request);

        if (! $user instanceof User) {
            return $this->unauthenticatedResponse($request);
        }

       $payload = $this->parseJsonPayload($request);

       if ($payload instanceof JsonResponse) {
           return $payload;
       }

       if ($validation = $this->validatePayload($payload, [
           'code' => ['required', 'string'],
       ])) {
           return $validation;
       }

       $product = Kategori::query()->where('kode', trim((string) $payload['code']))->first();
       
       if(!$product){
           return ResellerApiResponse::error(
              'Code Not Found',
              ResellerApiResponse::CODE_NOT_FOUND,
              404,
          );
           
       }
       
       $service = Layanan::query()
           ->where('kategori_id', $product->id)
           ->orderBy('layanan')
           ->get();
       
        $list = [];
       foreach($service as $s){
           /** @var \App\Models\Layanan $s */
           $route = app(\App\Services\ProviderRoutingService::class)->findBestProvider($s);
           $variantCode = trim((string) ($route['sku'] ?? $s->provider_id));

            if ($user->role == 'Platinum') {
                $harga = $s->harga_platinum;
            } elseif ($user->role == 'Gold') {
                $harga = $s->harga_gold;
            } elseif ($user->role == 'Member') {
                $harga = $s->harga_member;
            } else {
                $harga = $s->harga;
            }

           $list[] = [
                'code'      => $variantCode,
                'name'      => $s->layanan,
                'is_active' => ($s->status == 'available' && $variantCode !== ''),
                'price'     => $harga,
                'provider'  => $s->provider,
            ];
       }
       return response()->json([
           'error'   => false,
           'code'    => 200,
           'message' => 'Success',
           'data'    => $list,
      ], 200);
   }
   
   
   public function order(Request $request)
   {
        // Phase 5 — Task 5.4: capture start time for provider duration_ms logging
        $orderStartTime = hrtime(true);

        $user = $this->resolveApiUser($request);

        if (! $user instanceof User) {
            return $this->unauthenticatedResponse($request);
        }

       $payload = $this->parseJsonPayload($request);

       if ($payload instanceof JsonResponse) {
           return $payload;
       }

       if ($validation = $this->validatePayload($payload, [
           'code'            => ['required', 'string'],
           'referenceNumber' => ['required', 'string'],
           'user_id'         => ['required', 'string', 'max:100'],
           'zone_id'         => ['nullable', 'string', 'max:100'],
       ])) {
           return $validation;
       }

       $referenceNumber = trim((string) $payload['referenceNumber']);
       $existingOrder = $this->findExistingOrderByReference($user, $referenceNumber, 'live');

       if ($existingOrder instanceof Pembelian) {
           return response()->json([
               'error' => false,
               'code' => 200,
               'message' => 'Success',
               'data' => [
                   'invoiceNumber' => $existingOrder->order_id,
                   'status' => PembelianStatus::apiStatusCode($existingOrder->status),
                   'isDuplicate' => true,
               ],
           ], 200);
       }
       
       
       $target = $this->resolveOrderTargetByExternalCode(trim((string) $payload['code']));
       $service = $target['service'];
       $providerRoute = $target['route'];
       $modalPrice = $target['modal_price'];
       
       if(!$service){
           return ResellerApiResponse::error(
              'Code Not Found',
              ResellerApiResponse::CODE_NOT_FOUND,
              404,
          );
           
       }
       
       
        if ($user->role == 'Platinum') {
           $harga = $service->harga_platinum;
        } elseif ($user->role == 'Gold') {
            $harga = $service->harga_gold;
        } elseif ($user->role == 'Member') {
            $harga = $service->harga_member;
        } else {
            $harga = $service->harga;
        }
       
       
       if($user->balance < $harga){
           return ResellerApiResponse::error(
              'Your Balance is Insufficient',
              ResellerApiResponse::INSUFFICIENT_BALANCE,
              400,
          );
           
       }
      
       // Phase 5 — explicit named fields replace the ambiguous pipe-separated `data` field.
       $targetUserId = trim((string) $payload['user_id']);
       $zoneId = isset($payload['zone_id']) && $payload['zone_id'] !== null
           ? trim((string) $payload['zone_id'])
           : null;
       $order_id = $this->generateOrderId();
       $providerCode = strtolower(trim((string) ($providerRoute['provider_code'] ?? $service->provider)));
       $providerSku = trim((string) ($providerRoute['sku'] ?? $service->provider_id));
       $credentials = $providerRoute['credentials'] ?? [];

       // ── Status label constants — avoid raw string literals scattered below ─
       $STATUS_PENDING = PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING);
       $STATUS_SUCCESS = PembelianStatus::preferredDatabaseLabel(PembelianStatus::SUCCESS);
       $STATUS_FAILED  = PembelianStatus::preferredDatabaseLabel(PembelianStatus::FAILED);

       $resolvedOrderStatus = $STATUS_PENDING;
       $provider_order_id = '';
       $providerReference = $order_id;
       $order = ['status' => false];

       if ($providerCode == "digiflazz") {
            $digi = new DigiFlazzController($credentials);
            $random_part = mt_rand(100000, 999999);
            $provider_order_id = 'WEJIZY-RAPID' . $random_part;
            $order = $digi->order($targetUserId, $zoneId, $providerSku, $provider_order_id);

            if ($order['data']['status'] == "Pending" || $order['data']['status'] == "Sukses") {
                $order['status'] = true;
            } else {
                $order['status'] = false;
            }
            $resolvedOrderStatus = PembelianStatus::preferredDatabaseLabel($order['data']['status'] ?? PembelianStatus::PENDING);
        } else if ($providerCode == "moogold") {
            $moo = new MoogoldController();
            $random_part = mt_rand(100000, 999999);
            $provider_order_id = 'WJMG-RAPID' . $random_part;

            $order = $moo->order($targetUserId, $providerSku, $provider_order_id, $zoneId);

            if (isset($order['status'])) {
                $provider_order_id = $order['order_id'];
                $order['status'] = true;
            } else {
                $order['status'] = false;
            }
            $resolvedOrderStatus = $STATUS_PENDING;
        } else if ($providerCode == "vip" || $providerCode == "vip_reseller") {
            $vip = new VipResellerController($credentials);
            $order = $vip->order($targetUserId, $zoneId, $providerSku);

            if (($order['result'] ?? false) === true) {
                $statusMeta = VipResellerController::normalizeStatusMeta($order['data']['status'] ?? null);
                $order['data']['status'] = $order['result'];
                $order['transactionId'] = $order['data']['trxid'] ?? null;
                $order['provider_status'] = $statusMeta['internal_status'];
                $provider_order_id = $order['data']['trxid'] ?? '';
                $resolvedOrderStatus = PembelianStatus::preferredDatabaseLabel($statusMeta['internal_status']);
            } else {
                $order['data']['status'] = false;
            }
        } else if ($providerCode == "apigames") {
            $apigames = new ApiGamesController($credentials);
            $provider_order_id = $providerReference;
            $order = $apigames->order($targetUserId, $zoneId, $providerSku, $providerReference);

            $statusMeta = ApiGamesController::normalizeStatusMeta($order['data']['status'] ?? null);

            if (($order['result'] ?? false) === true) {
                $order['transactionId'] = $order['data']['trx_id'] ?? $providerReference;
                $order['provider_status'] = $statusMeta['internal_status'];
                $order['provider_order_id'] = $order['data']['trx_id'] ?? null;
                $provider_order_id = $order['data']['trx_id'] ?? $providerReference;
                $order['data']['status'] = true;
                $resolvedOrderStatus = PembelianStatus::preferredDatabaseLabel($statusMeta['internal_status']);
            } else if (($order['transport_error'] ?? false) === true) {
                $order['provider_status'] = PembelianStatus::PENDING;
                $order['data']['status'] = true;
                $resolvedOrderStatus = $STATUS_PENDING;
            } else {
                $order['data']['status'] = false;
            }
        } else if ($providerCode == "bangjeff") {
            $bangjeff = new BangJeffController($credentials);
            $requestData = [['name' => 'ID', 'value' => $targetUserId]];
            if (!empty($zoneId)) {
                $requestData[] = ['name' => 'Server', 'value' => $zoneId];
            }

            $order = $bangjeff->order($providerSku, $providerReference, 1, $requestData);
            $isSuccess = (($order['error'] ?? null) === false) || (($order['rc'] ?? null) === '00');
            $statusCode = strtoupper((string) ($order['data']['statusCode'] ?? 'PROCESSING'));

            if ($isSuccess) {
                $provider_order_id = $order['data']['invoiceNumber'] ?? $providerReference;
                $order['status'] = true;
                $resolvedOrderStatus = match ($statusCode) {
                    'SUCCESS'  => $STATUS_SUCCESS,
                    'REFUNDED' => $STATUS_FAILED,
                    default    => $STATUS_PENDING,
                };
            } else {
                $order['status'] = false;
            }
        } else if ($providerCode == "topupedia") {
            $topupedia = new TopupediaController($credentials);
            $requestData = [['name' => 'ID', 'value' => $targetUserId]];
            if (!empty($zoneId)) {
                $requestData[] = ['name' => 'Server', 'value' => $zoneId];
            }

            $order = $topupedia->order($providerSku, $providerReference, 1, $requestData);

            if (($order['error'] ?? true) === false) {
                $provider_order_id = $order['data']['invoiceNumber'] ?? $providerReference;
                $order['status'] = true;
                $resolvedOrderStatus = $STATUS_PENDING;
            } else {
                $order['status'] = false;
            }
        } else if ($providerCode == "gameshop") {
            $gameshop = new GameShopProvider;
            $random_part = mt_rand(100000, 999999);
            $provider_order_id = 'WJGS-RAPI' . $random_part;
            $order = $gameshop->order($targetUserId, $providerSku, $provider_order_id, $zoneId);
            Log::info('callback gameshop ' . json_encode($order));
            if (isset($order['data']['order_no'])) {
                $provider_order_id = $order['data']['order_no'];
                $order['status'] = true;
            } else {
                $order['status'] = false;
            }
            $resolvedOrderStatus = $STATUS_PENDING;
        } else if ($providerCode == "strleyashop") {
            $strleyashop = new StrleyaShopProvider;
            $random_part = mt_rand(100000, 999999);
            $provider_order_id = 'WJSS-RAPI' . $random_part;
            $order = $strleyashop->order($targetUserId, $providerSku, $provider_order_id, $zoneId);
            Log::info('callback strleyashop ' . json_encode($order));
            if (isset($order['order_details']['bot_order_id'])) {
                $provider_order_id = $order['order_details']['bot_order_id'];
                $order['status'] = true;
            } else {
                $order['status'] = false;
            }
            $resolvedOrderStatus = $STATUS_PENDING;
        } else if ($providerCode == "yezzpay") {
            $yezzpay = new YezzpayProvider;
            $provider_order_id = strtoupper(str_replace('.', '', uniqid('ACID-YEZZPAY', true)));
            $order = $yezzpay->order($targetUserId, $providerSku, $provider_order_id, $zoneId);
            Log::info('callback yezzpay ' . json_encode($order));
            if (isset($order['data']['trx_id'])) {
                $order['status'] = true;
            } else {
                $order['status'] = false;
            }
            $resolvedOrderStatus = $STATUS_PENDING;
        } else if ($providerCode == "elitedias") {
            $elitedias = new EliteDiasProvider;
            $random_part = mt_rand(100000, 999999);
            $provider_order_id = 'WJED-RAPI' . $random_part;
            $order = $elitedias->order($targetUserId, $providerSku, $provider_order_id, $zoneId);
            Log::info('callback elitedias ' . json_encode($order));
            if (isset($order['order_id'])) {
                $provider_order_id = $order['order_id'];
                $order['status'] = true;
            } else {
                $order['status'] = false;
            }
            $resolvedOrderStatus = $STATUS_PENDING;
        } else if ($providerCode == "joki" || $providerCode == "manual") {
            $order['status'] = true;
            $resolvedOrderStatus = $STATUS_SUCCESS;
        }
        

        
        if((bool) ($order['status'] ?? false)){
            $integration = $request->attributes->get('live_reseller_integration');

            if (! $integration instanceof ResellerIntegration) {
                $integration = null;
            }

            // Snapshot balance BEFORE deduction so we can report buyer_last_saldo accurately
            $balanceBefore = (float) $user->balance;

            DB::transaction(function () use (
                $user,
                $harga,
                $order_id,
                $targetUserId,
                $zoneId,
                $service,
                $modalPrice,
                $resolvedOrderStatus,
                $providerCode,
                $providerSku,
                $provider_order_id,
                $order,
                $referenceNumber,
                $integration
            ): void {
                $user->update([
                    'balance' => $user->balance - $harga
                ]);

                $pembelian = new Pembelian();
                $pembelian->username = $user->username;
                $pembelian->reseller_integration_id = $integration?->getKey();
                $pembelian->order_id = $order_id;
                $pembelian->user_id = $targetUserId;
                $pembelian->zone = $zoneId;
                $pembelian->layanan = $service->layanan;
                $pembelian->harga = $harga;
                $pembelian->profit = is_numeric($modalPrice)
                    ? max(0, (int) round($harga - (float) $modalPrice))
                    : (int) round($harga * ENV("MARGIN_PROFIT"));
                $pembelian->status = $resolvedOrderStatus;
                $pembelian->active_layanan_id = $service->id;
                $pembelian->active_provider_code = $providerCode;
                $pembelian->active_provider_sku = $providerSku;
                $pembelian->provider_order_id = $provider_order_id ? $provider_order_id : "";
                $pembelian->log = json_encode($order);
                $pembelian->traffic_source = $integration ? 'reseller_h2h' : $pembelian->traffic_source;
                $pembelian->tipe_transaksi = 'game';
                $pembelian->environment = $integration ? 'live' : $pembelian->environment;
                $pembelian->is_sandbox = false;
                $pembelian->save();

                $pembayaran = new Pembayaran();
                $pembayaran->order_id = $order_id;
                $pembayaran->harga = $harga;
                $pembayaran->no_pembayaran = "SALDO";
                $pembayaran->no_pembeli = $user->no_wa;
                $pembayaran->status = 'Lunas';
                $pembayaran->metode = 'SALDO';
                $pembayaran->reference = $referenceNumber;
                $pembayaran->expired_at = null;
                $pembayaran->save();
            });

            $buyerLastSaldo = (float) ($balanceBefore - $harga);

            return response()->json([
              'error'   => false,
              'code'    => 200,
              'message' => 'Success',
              'data'    => [
                'invoiceNumber'    => $order_id,
                'referenceNumber'  => $referenceNumber,
                'code'             => trim((string) $payload['code']),
                'user_id'          => $targetUserId,
                'zone_id'          => $zoneId,
                'price'            => $harga,
                'buyer_last_saldo' => $buyerLastSaldo,
                'status'           => PembelianStatus::apiStatusCode($resolvedOrderStatus),
                'message'          => null,
              ]
            ], 200);
        }else{
            // Structured warning log for provider failures — includes duration_ms to detect flaky providers.
            $durationMs = (int) round((hrtime(true) - $orderStartTime) / 1_000_000);
            Log::warning('reseller.provider_failure', [
                'provider'    => $providerCode ?? 'unknown',
                'layanan'     => $service->layanan ?? 'unknown',
                'username'    => $user->username,
                'code'        => trim((string) ($payload['code'] ?? '')),
                'duration_ms' => $durationMs,
                'reason'      => $order['message'] ?? ($order['data']['message'] ?? null),
            ]);

            // Saldo tidak terpotong — DB transaction tidak commit karena order gagal.
            // Reseller bisa retry dengan referenceNumber yang sama.
            $providerMessage = $this->sanitizeOrderFailedReason(
                $order['message'] ?? ($order['data']['message'] ?? null)
            );

            return response()->json([
                'error'   => true,
                'code'    => 400,
                'message' => 'Order Failed',
                'data'    => [
                    'invoiceNumber'    => null,
                    'referenceNumber'  => $referenceNumber,
                    'code'             => trim((string) $payload['code']),
                    'user_id'          => $targetUserId,
                    'zone_id'          => $zoneId,
                    'price'            => $harga,
                    'buyer_last_saldo' => (float) $user->balance,
                    'status'           => 'failed',
                    'message'          => $providerMessage,
                ],
            ], 400);
        }
   }
   
   
   public function statusOrder(Request $request, $invoice)
    {
        $user = $this->resolveApiUser($request);

        if (! $user instanceof User) {
            return $this->unauthenticatedResponse($request);
        }

        $cek = Pembelian::query()
            ->where('order_id', trim((string) $invoice))
            ->where('username', $user->username)
            ->where(function ($query): void {
                // Exclude sandbox orders: live endpoint only returns live/legacy records.
                // Legacy orders pre-dating the environment column have NULL on both fields.
                $query->whereNull('is_sandbox')->orWhere('is_sandbox', false);
            })
            ->where(function ($query): void {
                $query->whereNull('environment')->orWhere('environment', 'live');
            })
            ->first();
    
        if (!$cek) {
            return ResellerApiResponse::error(
                'Invoice Not Found',
                ResellerApiResponse::INVOICE_NOT_FOUND,
                404,
            );
        }
    
        $statusCode = PembelianStatus::apiStatusCode($cek->status);
    
        return response()->json([
            "error"   => false,
            "code"    => 200,
            "message" => "Success",
            "data"    => $this->buildStatusPayload($cek),
        ], 200);
    }


    protected function resolveOrderTargetByExternalCode(string $requestedCode): array
    {
        $requestedCode = trim($requestedCode);
        $routingService = app(\App\Services\ProviderRoutingService::class);

        $service = Layanan::where('provider_id', $requestedCode)->first();

        if ($service) {
            $route = $routingService->resolveExplicitProvider(
                strtolower(trim((string) $service->provider)),
                trim((string) $service->provider_id)
            );

            return [
                'service' => $service,
                'route' => $route,
                'modal_price' => is_numeric($service->harga ?? null) ? (float) $service->harga : null,
            ];
        }

        $path = ProviderPath::query()
            ->with('layanan')
            ->where('provider_sku', $requestedCode)
            ->where('status', 'available')
            ->orderBy('priority')
            ->orderBy('modal_price')
            ->first();

        if ($path && $path->layanan) {
            return [
                'service' => $path->layanan,
                'route' => $routingService->resolveExplicitProvider(
                    strtolower(trim((string) $path->provider_code)),
                    trim((string) $path->provider_sku)
                ),
                'modal_price' => is_numeric($path->modal_price) ? (float) $path->modal_price : null,
            ];
        }

        return [
            'service' => null,
            'route' => null,
            'modal_price' => null,
        ];
    }

    /**
     * Generate a unique order ID with low collision probability.
     *
     * Format: TRX-{YYMMDDHHmmss}{8-char-random}
     * Example: TRX-2606021435AB3XYZ8
     * 
     * Uses hardcoded 'TRX' prefix for performance (avoids DB query on every order).
     * The dash separator distinguishes reseller API orders from customer orders.
     *
     * Old format was WEJIZY-H2H-{...} which is now deprecated.
     * New format has >2 trillion combinations per second — effectively unique.
     * A retry loop is included as defense-in-depth (essentially unreachable in practice).
     */
    private function generateOrderId(): string
    {
        $maxAttempts = 5;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $candidate = 'TRX-' . now()->format('ymdHis') . Str::upper(Str::random(8));

            if (! Pembelian::where('order_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Extremely unlikely to reach here; log a warning so we can investigate
        Log::warning('OrderApiController: order ID generation exhausted retries', [
            'last_candidate' => $candidate ?? 'none',
        ]);

        // Final fallback: add microseconds to guarantee uniqueness
        return 'TRX-' . now()->format('ymdHisu') . Str::upper(Str::random(4));
    }

    protected function resolveApiUser(Request $request): ?User
    {
        // api_user di-set oleh AuthenticateApi middleware sebelum controller dieksekusi.
        // Tidak ada DB fallback di sini — controller tidak boleh duplikat logika middleware.
        // Jika api_user tidak ada di attributes, berarti middleware dilewati (tidak seharusnya).
        $resolved = $request->attributes->get('api_user');

        return $resolved instanceof User ? $resolved : null;
    }

    protected function unauthenticatedResponse(Request $request): JsonResponse
    {
        $message = trim((string) $request->bearerToken()) === ''
            ? 'Access Token is required'
            : 'Invalid Token';

        return ResellerApiResponse::error(
            $message,
            $message === 'Access Token is required'
                ? ResellerApiResponse::ACCESS_TOKEN_REQUIRED
                : ResellerApiResponse::INVALID_TOKEN,
            403,
        );
    }

    protected function parseJsonPayload(Request $request): array|JsonResponse
    {
        $content = trim((string) $request->getContent());

        if ($content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return ResellerApiResponse::error(
                'Invalid JSON payload',
                ResellerApiResponse::INVALID_JSON_PAYLOAD,
                400,
            );
        }

        return $decoded;
    }

    protected function validatePayload(array $payload, array $rules): ?JsonResponse
    {
        $validator = Validator::make($payload, $rules, [
            'code.required'            => 'The code field is required.',
            'code.string'              => 'The code field must be a string.',
            'referenceNumber.required' => 'The referenceNumber field is required.',
            'referenceNumber.string'   => 'The referenceNumber field must be a string.',
            'user_id.required'         => 'The user_id field is required.',
            'user_id.string'           => 'The user_id field must be a string.',
        ]);

        if (! $validator->fails()) {
            return null;
        }

        return ResellerApiResponse::error(
            'Validation failed',
            ResellerApiResponse::VALIDATION_FAILED,
            422,
            $validator->errors()->toArray(),
        );
    }

    protected function findExistingOrderByReference(User $user, string $referenceNumber, ?string $environment = null): ?Pembelian
    {
        if ($referenceNumber === '') {
            return null;
        }

        return Pembayaran::query()
            ->with('pembelian')
            ->where('reference', $referenceNumber)
            ->whereHas('pembelian', function ($query) use ($user, $environment): void {
                $query->where('username', $user->username);

                if ($environment === 'sandbox') {
                    $query->where(function ($sandboxQuery): void {
                        $sandboxQuery->where('is_sandbox', true)
                            ->orWhere('environment', 'sandbox');
                    });
                }

                if ($environment === 'live') {
                    $query->where(function ($liveQuery): void {
                        $liveQuery->whereNull('is_sandbox')
                            ->orWhere('is_sandbox', false);
                    })->where(function ($liveQuery): void {
                        $liveQuery->whereNull('environment')
                            ->orWhere('environment', 'live');
                    });
                }
            })
            ->latest('id')
            ->first()
            ?->pembelian;
    }

    protected function buildStatusPayload(Pembelian $pembelian): array
    {
        $zone = trim((string) $pembelian->zone);

        return [
            'invoiceNumber'  => $pembelian->order_id,
            'productName'    => $pembelian->layanan,
            'user_id'        => (string) $pembelian->user_id,
            'zone_id'        => $zone !== '' ? $zone : null,
            'statusCode'     => PembelianStatus::apiStatusCode($pembelian->status),
            'sn'             => $pembelian->keterangan_sn,
            'keteranganSn'   => $pembelian->keterangan_sn,
        ];
    }

    /**
     * Sanitize provider error messages for order failure responses.
     * Strips sensitive keywords and truncates verbose stack traces.
     */
    protected function sanitizeOrderFailedReason(?string $reason): ?string
    {
        if ($reason === null || $reason === '') {
            return null;
        }

        $sensitivePatterns = [
            '/api[_\-]?key/i',
            '/secret/i',
            '/token/i',
            '/password/i',
            '/credential/i',
            '/authorization/i',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $reason)) {
                return 'Provider returned an error. Please contact support if this persists.';
            }
        }

        return mb_substr(trim($reason), 0, 200);
    }
}
