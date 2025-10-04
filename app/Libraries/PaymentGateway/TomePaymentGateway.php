<?php

namespace App\Libraries\PaymentGateway;

class TomePaymentGateway
{
    public static $merchantId;
    public static $merchantKey;

    static function format_uuidv4($data)
    {
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function createPayment(array $data = [])
    {
        $post_data = json_encode([
            'amount' => [
                'value' => $data['amount'],
                'currency' => 'RUB'
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $data['return_url'] // payment Succes/Cancele
            ],
            'customer' => [
                'settlement_method' => $data['settlement_method']
            ],
            'description' => $data['description'] ?? '',
            'metadata' => ['oid' => $data['oid']]
        ]);
        $request = self::request('https://tome.ge/api/v1/payments', $post_data, 'POST');
        $response =  json_decode($request, true);

        

        if (isset($response['type']) AND $response['type'] == 'error') {
            return false;
        }
        return [
            'status' => true,
            'json' => $request,
            'data' => $response
        ];
    }

    public static function request($url, $post_data, $method){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, '' );
        curl_setopt($ch, CURLOPT_COOKIE, '' );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Length:'.strlen($post_data),
            'Content-Type: application/json',
            'Authorization: Basic '.base64_encode(self::$merchantId.':'.self::$merchantKey),
            'Idempotency-Key: '.self::format_uuidv4(random_bytes(16))
        ));
        // curl_setopt($ch, CURLOPT_VERBOSE,1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}

?>
