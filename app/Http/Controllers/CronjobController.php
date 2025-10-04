<?php

namespace App\Http\Controllers;

use App\Libraries\Provider\ElitediasProvider;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;
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
                            'provider' => 'gameshop',
                            'order_id' => $provider_order_id,
                            'status' => $newStatus,
                            'response' => $response
                        ], JSON_PRETTY_PRINT)
                    ]);
                }

                print "Order ID: $provider_order_id, Status: $newStatus\n";
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

    public function updateYezzpay()
    {
        $yezzpay =  new YezzpayProvider;
        $orders = Pembelian::where('status', 'Proses')
            ->where('provider', 'yezzpay')
            ->get();
            

        foreach ($orders as $order) {
            $provider_order_id = $order->provider_order_id;

            $response = $yezzpay->status($provider_order_id);
            
            if (isset($response['data']['order_status'])) {
                $status = strtolower($response['data']['order_status']); 
                $newStatus = null;

                if ($status == 'success') {
                    $newStatus = 'Sukses';
                } elseif ($status == 'failed') {
                    $newStatus = 'Gagal';
                } elseif ($status == 'pending' || $status == 'processing') {
                    $newStatus = 'Proses';
                }

                if ($newStatus) {
                    $order->update([
                        'status' => $newStatus,
                        'log' => json_encode([
                            'provider' => 'yezzpay',
                            'order_id' => $provider_order_id,
                            'status' => $newStatus,
                            'response' => $response
                        ], JSON_PRETTY_PRINT)
                    ]);
                }

                print "Order ID: $provider_order_id, Status: $newStatus\n";
            } else {
                Log::warning("Gagal mengambil status yezzpay untuk order_id: $provider_order_id");

                $order->update([
                    'log' => json_encode([
                        'provider' => 'yezzpay',
                        'order_id' => $provider_order_id,
                        'error' => 'Gagal mengambil status dari yezzpay',
                        'response' => $response
                    ], JSON_PRETTY_PRINT)
                ]);
            }
        }
    }
    
    public function updateElitedias()
    {
        $elitedias =  new ElitediasProvider;
        $orders = Pembelian::where('status', 'Proses')
            ->where('provider', 'elitedias')
            ->get();
            

        foreach ($orders as $order) {
            $provider_order_id = $order->provider_order_id;

            $response = $elitedias->status($provider_order_id);
            
            if (isset($response['order_status'])) {
                $status = strtolower($response['order_status']); 
                $newStatus = null;

                if ($status == 'successful' || $status == 'success') {
                    $newStatus = 'Sukses';
                } elseif ($status == 'failed' || $status == 'error' || $status == 'cancelled') {
                    $newStatus = 'Gagal';
                } elseif ($status == 'pending' || $status == 'processing') {
                    $newStatus = 'Proses';
                }

                if ($newStatus) {
                    $order->update([
                        'status' => $newStatus,
                        'log' => json_encode([
                            'provider' => 'elitedias',
                            'order_id' => $provider_order_id,
                            'status' => $newStatus,
                            'response' => $response
                        ], JSON_PRETTY_PRINT)
                    ]);
                }

                print "Order ID: $provider_order_id, Status: $newStatus\n";
            } else {
                Log::warning("Gagal mengambil status elitedias untuk order_id: $provider_order_id");

                $order->update([
                    'log' => json_encode([
                        'provider' => 'elitedias',
                        'order_id' => $provider_order_id,
                        'error' => 'Gagal mengambil status dari elitedias',
                        'response' => $response
                    ], JSON_PRETTY_PRINT)
                ]);
            }
        }
    }

    
}