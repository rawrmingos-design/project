<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TriPayController extends Controller
{
    public function request($idOrder, $jumlah, $method, $dataUser, $nohp)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();
        $data = [
            'method'         => $method,
            'merchant_ref'   => $idOrder,
            'amount'         => $jumlah,
            'customer_name'  => env("APP_NAME"),
            'customer_email' => $dataUser,
            'customer_phone' => $nohp,
            'order_items'    => [
                [
                    'name'        => 'Pembayaran ' . $method . ' ' . $idOrder,
                    'price'       => $jumlah,
                    'quantity'    => 1,
                ]
            ],
            'expired_time' => (time() + (24 * 60 * 60)), // 24 jam
            'signature'    => hash_hmac('sha256', $api->tripay_merchant_code . $idOrder . $jumlah, $api->tripay_private_key)
        ];

        $curl = curl_init();

        $url = "";
        Log::info(env('APP_ENV'));
        if (env("APP_ENV") == "local") {
            $url = 'https://tripay.co.id/api-sandbox/transaction/create';
        } else {
            $url = 'https://tripay.co.id/api/transaction/create';
        }

        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $api->tripay_api],
            CURLOPT_FAILONERROR    => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
        ]);

        $response = json_decode(curl_exec($curl));
        // dd($response);
        $error = curl_error($curl);
        $paymentNumber = '';
        // if ($method == "QRISC" || $method == "QRISD" || $method == "QRIS" || $method == "QRISOP") {
        //     $paymentNumber = $response->data->qr_url;
        // } else if ($response->data->pay_code == null) {
        //     $paymentNumber = $response->data->checkout_url;
        // } else {
        //     $paymentNumber = $response->data->pay_code;
        // }


        if ($response->success == true) {


            if (empty($response->data->pay_code)) {

                if (empty($response->data->pay_url)) {

                    $paymentNumber = $response->data->qr_url;
                } else {

                    $paymentNumber = $response->data->pay_url;
                }
            } else {

                $paymentNumber = $response->data->pay_code;
            }

            return array('success' => $response->success, 'amount' => $response->data->amount, 'no_pembayaran' => $paymentNumber, 'reference' => $response->data->reference);
        } else {

            $err = strtolower($response->message);

            $msg = '';

            if (str_contains($err, 'minimum')) {

                $pch = explode('rp', $err);

                $msg = 'Minimum jumlah pembayaran untuk metode pembayaran ini adalah Rp ' . $pch[1] . ' ';
            } elseif (str_contains($err, 'maximum')) {

                $pch = explode('rp', $err);

                $msg = 'Maksimal jumlah pembayaran untuk metode pembayaran ini adalah Rp ' . $pch[1] . ' ';
            } else {

                $msg = 'Metode pembayaran ini sedang tidak dapat digunakan';
            }

            return array('success' => false, 'msg' => $msg);
        }
    }

    public function fee($jumlah, $code)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $payload = [
            'code'    => $code,
            'amount'    => $jumlah
        ];

        $curl = curl_init();
        $url = "";
        if (env("APP_ENV") == "local") {
            $url = 'https://tripay.co.id/api-sandbox/merchant/fee-calculator';
        } else {
            $url = 'https://tripay.co.id/api/merchant/fee-calculator';
        }

        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => $url . '?' . http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $api->tripay_api],
            CURLOPT_FAILONERROR    => false
        ]);

        $response = json_decode(curl_exec($curl));
        $error = curl_error($curl);

        curl_close($curl);

        return $response->data['0']->total_fee->customer + $response->data['0']->total_fee->merchant;
    }

    public function channel()
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $curl = curl_init();
        if (env("APP_ENV") == "local") {
            $url = 'https://tripay.co.id/api-sandbox/merchant/payment-channel';
        } else {
            $url = 'https://tripay.co.id/api/merchant/payment-channel';
        }
        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $api->tripay_api],
            CURLOPT_FAILONERROR    => false
        ]);

        $response = json_decode(curl_exec($curl));
        $error = curl_error($curl);
        Log::info('info', ['response' => $response, 'error' => $error]);
        curl_close($curl);

        return $response;
    }

    public function detail($reference)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $curl = curl_init();
        $url = "";
        if (env("APP_ENV") == "local") {
            $url = 'https://tripay.co.id/api-sandbox/transaction/detail?reference=';
        } else {
            $url = 'https://tripay.co.id/api/transaction/detail?reference=';
        }
        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => $url . $reference,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $api->tripay_api],
            CURLOPT_FAILONERROR    => false
        ]);

        $response = json_decode(curl_exec($curl));
        $error = curl_error($curl);

        curl_close($curl);

        return $response;
    }
}
