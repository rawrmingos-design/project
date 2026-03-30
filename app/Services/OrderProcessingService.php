<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\Layanan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\DigiFlazzController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Http\Controllers\provider\BangJeffController;

class OrderProcessingService
{
    protected $routingService;

    public function __construct(ProviderRoutingService $routingService)
    {
        $this->routingService = $routingService;
    }

    /**
     * Process the order to the provider.
     * 
     * @param Pembelian $pembelian
     * @return array ['success' => bool, 'transaction_id' => string|null, 'message' => string]
     */
    public function process(Pembelian $pembelian): array
    {
        Log::info("OrderProcessingService: Processing order {$pembelian->order_id} ({$pembelian->layanan})");

        $layanan = $this->resolveLayanan($pembelian);

        if (!$layanan) {
            return [
                'success' => false,
                'message' => $pembelian->active_layanan_id
                    ? 'Active layanan not found in database: ' . $pembelian->active_layanan_id
                    : 'Layanan not found in database: ' . $pembelian->layanan
            ];
        }

        $providerRoute = $this->resolveProviderRoute($pembelian, $layanan);

        if (!$providerRoute) {
            return [
                'success' => false,
                'message' => 'No provider route found for this service.'
            ];
        }

        $providerCode = $providerRoute['provider_code'];
        $sku = $providerRoute['sku'];
        $credentials = $providerRoute['credentials'] ?? [];
        
        // Prepare parameters
        $uid = $pembelian->user_id;
        $zone = $pembelian->zone;
        $providerReference = $this->resolveProviderReference($pembelian);

        Log::info("OrderProcessingService: Routed to {$providerCode} with SKU {$sku}", [
            'canonical_order_id' => $pembelian->order_id,
            'provider_reference' => $providerReference,
            'active_layanan_id' => $pembelian->active_layanan_id,
        ]);

        $result = [
            'success' => false,
            'order_status' => 'Pending', // Default
            'transaction_id' => null,
            'message' => '',
            'sn' => null,
            'provider' => $providerCode
        ];

        try {
            $result = array_merge(
                $result,
                $this->dispatchToProvider($providerCode, $credentials, $uid, $zone, $sku, $providerReference)
            );

        } catch (\Exception $e) {
            Log::error("OrderProcessingService Exception: " . $e->getMessage());
            $result['message'] = "Exception: " . $e->getMessage();
        }

        return $result;
    }

    protected function resolveLayanan(Pembelian $pembelian): ?Layanan
    {
        if ($pembelian->active_layanan_id) {
            return Layanan::query()->find($pembelian->active_layanan_id);
        }

        return Layanan::query()
            ->where('layanan', $pembelian->layanan)
            ->first();
    }

    protected function resolveProviderReference(Pembelian $pembelian): string
    {
        $reference = trim((string) ($pembelian->active_attempt_reference ?: $pembelian->display_order_id));

        return $reference !== '' ? $reference : $pembelian->order_id;
    }

    protected function resolveProviderRoute(Pembelian $pembelian, Layanan $layanan): ?array
    {
        $activeProviderCode = strtolower(trim((string) $pembelian->active_provider_code));
        $activeProviderSku = trim((string) $pembelian->active_provider_sku);

        if ($activeProviderCode !== '' && $activeProviderSku !== '') {
            return $this->routingService->resolveExplicitProvider($activeProviderCode, $activeProviderSku);
        }

        return $this->routingService->findBestProvider($layanan);
    }

    protected function dispatchToProvider(
        string $providerCode,
        array $credentials,
        mixed $uid,
        mixed $zone,
        mixed $sku,
        string $providerReference
    ): array {
        $result = [
            'success' => false,
            'order_status' => 'Pending',
            'transaction_id' => null,
            'message' => '',
            'sn' => null,
            'provider' => $providerCode,
        ];

        switch ($providerCode) {
            case 'digiflazz':
                $digiflazz = new DigiFlazzController($credentials);
                $response = $digiflazz->order($uid, $zone, $sku, $providerReference);

                Log::info("Digiflazz Response for {$providerReference}: " . json_encode($response));

                $responseData = $response['data'] ?? [];
                $providerStatus = $responseData['status'] ?? null;
                $providerRefId = $responseData['ref_id'] ?? $providerReference;
                $providerMessage = trim((string) ($responseData['message'] ?? ''));
                $providerSn = trim((string) ($responseData['sn'] ?? ''));

                if (in_array($providerStatus, ['Pending', 'Sukses', 'Success'], true)) {
                    $normalizedStatus = $providerStatus === 'Success' ? 'Sukses' : $providerStatus;

                    $result['success'] = true;
                    $result['order_status'] = $normalizedStatus;
                    $result['transaction_id'] = $providerRefId;
                    $result['sn'] = $providerSn !== '' ? $providerSn : ($normalizedStatus === 'Pending' ? 'Sedang Diproses' : null);
                    $result['message'] = $providerMessage !== ''
                        ? $providerMessage
                        : 'Order accepted by Digiflazz status: ' . $normalizedStatus;
                } else {
                    $result['message'] = $providerMessage !== ''
                        ? $providerMessage
                        : 'Unknown error from Digiflazz';
                }

                return $result;

            case 'vip':
            case 'vip_reseller':
                $vip = new VipResellerController($credentials);
                $response = $vip->order($uid, $zone, $sku);

                if (($response['result'] ?? false) === true) {
                    $statusMeta = VipResellerController::normalizeStatusMeta($response['data']['status'] ?? null);
                    $note = trim((string) ($response['data']['note'] ?? ''));

                    if (($statusMeta['is_partial'] ?? false) === true) {
                        $note = trim(($note !== '' ? $note . ' | ' : '') . 'VIP partial: cek refund/penyelesaian manual di provider.');
                    }

                    $result['success'] = true;
                    $result['order_status'] = $statusMeta['internal_status'];
                    $result['transaction_id'] = $response['data']['trxid'] ?? $providerReference;
                    $result['sn'] = $note !== '' ? $note : (($statusMeta['internal_status'] === 'Pending' || $statusMeta['internal_status'] === 'Processing') ? 'Sedang Diproses' : null);
                    $result['message'] = $response['message'] ?? 'VIP Reseller order processed.';
                } else {
                    $result['message'] = $response['message'] ?? 'VIP Reseller failed';
                }

                return $result;

            case 'apigames':
                $apigames = new ApiGamesController($credentials);
                $response = $apigames->order($uid, $zone, $sku, $providerReference);

                if (isset($response['data']['status']) && $response['data']['status'] == 'Sukses') {
                    $result['success'] = true;
                    $result['order_status'] = 'Sukses';
                    $result['transaction_id'] = $response['data']['trx_id'] ?? $providerReference;
                    $result['message'] = 'Order successful';
                } elseif (isset($response['data']['status']) && $response['data']['status'] == 'Pending') {
                    $result['success'] = true;
                    $result['order_status'] = 'Pending';
                    $result['message'] = 'Order pending';
                } else {
                    $result['message'] = $response['error_msg'] ?? 'ApiGames failed';
                }

                return $result;

            case 'bangjeff':
                $bangjeff = new BangJeffController($credentials);
                $requestData = [['name' => 'ID', 'value' => $uid]];
                if (!empty($zone)) {
                    $requestData[] = ['name' => 'Server', 'value' => $zone];
                }

                $response = $bangjeff->order($sku, $providerReference, 1, $requestData);
                $isSuccess = (($response['error'] ?? null) === false) || (($response['rc'] ?? null) === '00');
                $statusCode = strtoupper((string) ($response['data']['statusCode'] ?? 'PROCESSING'));

                if ($isSuccess) {
                    $result['success'] = true;
                    $result['order_status'] = $statusCode === 'SUCCESS' ? 'Sukses' : ($statusCode === 'REFUNDED' ? 'Gagal' : 'Pending');
                    $result['transaction_id'] = $response['data']['invoiceNumber'] ?? $providerReference;
                    $result['message'] = $response['data']['statusDesc'] ?? ($response['message'] ?? 'BangJeff order accepted');
                } else {
                    $result['message'] = $response['message'] ?? 'BangJeff Failed';
                }

                return $result;

            case 'manual':
            case 'joki':
                $result['success'] = true;
                $result['order_status'] = 'Sukses';
                $result['message'] = 'Manual/Joki order marked as processing.';

                return $result;

            default:
                $result['message'] = "Provider {$providerCode} logic not implemented in service.";

                return $result;
        }
    }
}
