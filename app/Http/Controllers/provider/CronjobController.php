<?php

namespace App\Http\Controllers\Provider;

use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Models\Pembelian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CronjobController extends Controller
{
    public function updateGameShop()
    {
        $gameshop =  new GameShopProvider;
        $orders = Pembelian::where('status', 'Proses')
            ->where('provider', 'gameshop')
            ->get();

        foreach ($orders as $order) {
            $provider_order_id = $order->provider_order_id;

            $response = $gameshop->status($provider_order_id);
            
            if (isset($response['data'])) {
                $status = strtolower($response['data']['status']); 
                $newStatus = null;

                if ($status == '5') {
                    $newStatus = 'Sukses';
                } elseif ($status == '6' || $status == '0') {
                    $newStatus = 'Gagal';
                } elseif ($status == '1' || $status == '2' || $status == '3' || $status == '4') {
                    $newStatus = 'Proses';
                }

                if ($newStatus) {
                    $order->update([
                        'status' => $newStatus,
                        'log' => json_encode([
                            'provider' => 'gameshop',
                            'order_id' => $provider_order_id,
                            'status' => $newStatus,
                            'response' => $response
                        ], JSON_PRETTY_PRINT)
                    ]);
                }
            } else {
                Log::warning("Gagal mengambil status gameshop untuk order_id: $provider_order_id");

                $order->update([
                    'log' => json_encode([
                        'provider' => 'gameshop',
                        'order_id' => $provider_order_id,
                        'error' => 'Gagal mengambil status dari gameshop',
                        'response' => $response
                    ], JSON_PRETTY_PRINT)
                ]);
            }
        }
    }

    public function updateStrleyashop()
    {
        $strleyashop =  new StrleyaShopProvider;
        $orders = Pembelian::where('status', 'Proses')
            ->where('provider', 'strleyashop')
            ->get();
            

        foreach ($orders as $order) {
            $provider_order_id = $order->provider_order_id;

            $response = $strleyashop->status($provider_order_id);
            
            if (isset($response['order_status'])) {
                $status = strtolower($response['order_status']); 
                $newStatus = null;

                if ($status == 'successful') {
                    $newStatus = 'Sukses';
                } elseif ($status == 'error') {
                    $newStatus = 'Gagal';
                } elseif ($status == 'pending') {
                    $newStatus = 'Proses';
                }

                if ($newStatus) {
                    $order->update([
                        'status' => $newStatus,
                        'log' => json_encode([
                            'provider' => 'strleyashop',
                            'order_id' => $provider_order_id,
                            'status' => $newStatus,
                            'response' => $response
                        ], JSON_PRETTY_PRINT)
                    ]);
                }

                print "Order ID: $provider_order_id, Status: $newStatus\n";
            } else {
                Log::warning("Gagal mengambil status strleyashop untuk order_id: $provider_order_id");

                $order->update([
                    'log' => json_encode([
                        'provider' => 'gameshop',
                        'order_id' => $provider_order_id,
                        'error' => 'Gagal mengambil status dari strleyashop',
                        'response' => $response
                    ], JSON_PRETTY_PRINT)
                ]);
            }
        }
    }

    
}