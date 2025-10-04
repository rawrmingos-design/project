<?php

namespace App\Libraries\PaymentGateway;

class RazerPaymentGateway
{
    protected $merchantId;
    protected $verifyKey;
    protected $secretKey;
    protected $environment;
    protected $baseUrl;

    public function __construct(?string $merchantId, ?string $verifyKey, ?string $secretKey, ?string $environment = null)
    {
        $this->merchantId = $merchantId;
        $this->verifyKey = $verifyKey;
        $this->secretKey = $secretKey;
        $this->environment = $environment;
        $this->baseUrl = ($environment === 'sandbox') ? 'https://sandbox.merchant.razer.com/RMS/pay/'.$merchantId : 'https://pay.fiuu.com/RMS/pay/'.$merchantId;
    }

    public function getPaymentUrl(
        $data = []
    ) {
        $params = [
            'orderid' => $data['order_id'],
            'amount' => $data['amount'],
            'bill_name' => $data['bill_name'],
            'bill_email' => $data['bill_email'],
            'bill_mobile' => $data['bill_mobile'],
            'bill_desc' => $data['bill_desc'] ?? '-',
            'channel' => $data['channel'],
            'country' => $data['country'] ?? 'MY',
            'currency' => $data['currency'] ?? 'RUB',
            'returnurl' => $data['returnurl'] ?? null,
            'callbackurl' => $data['callbackurl'] ?? null,
            'cancelurl' => $data['cancelurl'] ?? null,
            'vcode' => md5($data['amount'] . $this->merchantId . $data['order_id'] . $this->verifyKey),
        ];

        $url = $this->baseUrl . '/' . $data['channel'] . '.php';

        return [
            'status' => true,
            'data' => $params,
            'url' => $url . '?' . http_build_query($params)
        ];
    }

    public function verifySignature($paydate, $domain, $key, $appcode, $skey)
    {
        $checkVcode = md5($paydate . $domain . $key . $appcode . $this->secretKey);
        return $checkVcode === $skey;
    }
}
