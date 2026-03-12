<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\Layanan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\DigiFlazzController;
use App\Http\Controllers\VipResellerController;
use App\Http\Controllers\ApiGamesController;
use App\Http\Controllers\BangJeffController;
use App\Http\Controllers\TopupediaController;
use App\Http\Controllers\MoogoldController;

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

        // 1. Find Layanan
        $layanan = Layanan::where('layanan', $pembelian->layanan)->first();
        
        if (!$layanan) {
            return [
                'success' => false,
                'message' => 'Layanan not found in database: ' . $pembelian->layanan
            ];
        }

        // 2. Find Best Route
        $bestRoute = $this->routingService->findBestProvider($layanan);

        if (!$bestRoute) {
            return [
                'success' => false,
                'message' => 'No provider route found for this service.'
            ];
        }

        $providerCode = $bestRoute['provider_code'];
        $sku = $bestRoute['sku'];
        $credentials = $bestRoute['credentials'] ?? [];
        
        // Prepare parameters
        $uid = $pembelian->user_id;
        $zone = $pembelian->zone;
        $orderId = $pembelian->order_id; // Use Invoice ID as Ref ID where possible
        
        Log::info("OrderProcessingService: Routed to {$providerCode} with SKU {$sku}");

        $result = [
            'success' => false,
            'order_status' => 'Pending', // Default
            'transaction_id' => null,
            'message' => '',
            'provider' => $providerCode
        ];

        try {
            switch ($providerCode) {
                case 'digiflazz':
                    // Use existing logic or standard params
                    // DigiflazzController::order($uid, $zone, $sku, $ref_id)
                    $digiflazz = new DigiFlazzController($credentials);
                    $response = $digiflazz->order($uid, $zone, $sku, $orderId);
                    
                    Log::info("Digiflazz Response for {$orderId}: " . json_encode($response));

                    if (isset($response['data']['status']) && in_array($response['data']['status'], ['Pending', 'Sukses'])) {
                        $result['success'] = true;
                        $result['order_status'] = $response['data']['status']; // 'Pending' or 'Sukses'
                        $result['transaction_id'] = $orderId; // Digiflazz uses our RefID usually, or we capture theirs? 
                        // Actually Digiflazz might return 'sn' or nothing if pending. 
                        // We use our RefID as the handle.
                        $result['message'] = 'Order accepted by Digiflazz status: ' . $response['data']['status'];
                    } else {
                        $result['message'] = $response['data']['message'] ?? 'Unknown error from Digiflazz';
                        // Special case: check if "Saldo tidak cukup" etc
                    }
                    break;

                case 'vip':
                case 'vip_reseller':
                    $vip = new VipResellerController($credentials);
                    $response = $vip->order($uid, $zone, $sku);
                    
                    if ($response['result']) {
                        $result['success'] = true;
                        $result['order_status'] = $response['data']['status'] == 'success' ? 'Sukses' : 'Pending';
                        $result['transaction_id'] = $response['data']['trxid'];
                        $result['message'] = $response['message'];
                    } else {
                        $result['message'] = $response['message'];
                    }
                    break;

                case 'apigames':
                    $apigames = new ApiGamesController($credentials);
                    $response = $apigames->order($uid, $zone, $sku, $orderId); // passing orderId as ref_id logic might need verification in controller

                    if (isset($response['data']['status']) && $response['data']['status'] == 'Sukses') {
                        $result['success'] = true;
                        $result['order_status'] = 'Sukses';
                        $result['transaction_id'] = $response['data']['trx_id'] ?? $orderId;
                        $result['message'] = 'Order successful';
                    } elseif (isset($response['data']['status']) && $response['data']['status'] == 'Pending') {
                        $result['success'] = true;
                        $result['order_status'] = 'Pending';
                        $result['message'] = 'Order pending';
                    } else {
                        $result['message'] = $response['error_msg'] ?? 'ApiGames failed';
                    }
                    break;
                
                case 'bangjeff':
                    $bangjeff = new BangJeffController($credentials);
                    $requestData = [['name' => 'ID', 'value' => $uid]];
                    if (!empty($zone)) $requestData[] = ['name' => 'Server', 'value' => $zone];

                    $response = $bangjeff->order($sku, $orderId, 1, $requestData);
                    
                    if ($response['error'] == false) {
                        $result['success'] = true;
                        $result['order_status'] = 'Pending';
                        $result['transaction_id'] = $response['data']['invoiceNumber'];
                        $result['message'] = 'BangJeff Order Success';
                    } else {
                        $result['message'] = $response['message'] ?? 'BangJeff Failed';
                    }
                    break;

                case 'manual':
                case 'joki':
                    // Auto-success for manual/joki types if that's the desired flow
                    $result['success'] = true;
                    $result['order_status'] = 'Sukses';
                    $result['message'] = 'Manual/Joki order marked as processing.';
                    break;

                default:
                    // Try to handle dynamic generic providers needed?
                    // For now, fail safe.
                    $result['message'] = "Provider {$providerCode} logic not implemented in service.";
                    break;
            }

        } catch (\Exception $e) {
            Log::error("OrderProcessingService Exception: " . $e->getMessage());
            $result['message'] = "Exception: " . $e->getMessage();
        }

        return $result;
    }
}
