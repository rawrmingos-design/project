<?php

namespace App\Services\Payments;

use App\Support\DuitkuPaymentChannels;
use Duitku\Api;
use Duitku\Config;
use Duitku\Pop;
use RuntimeException;

class DuitkuPopClient
{
    public function createInvoice(array $params, Config $config): array
    {
        return $this->decode(Pop::createInvoice($params, $config));
    }

    public function createDirectInvoice(array $params, Config $config): array
    {
        return $this->decode(Api::createInvoice($params, $config));
    }

    public function transactionStatus(string $merchantOrderId, Config $config, string $apiMode = 'pop'): array
    {
        $response = strtolower(trim($apiMode)) === 'direct'
            ? Api::transactionStatus($merchantOrderId, $config)
            : Pop::transactionStatus($merchantOrderId, $config);

        return $this->decode($response);
    }

    public function transactionStatusForPayment(string $merchantOrderId, Config $config, ?string $apiMode, ?string $paymentCode): array
    {
        $mode = in_array(strtolower(trim((string) $apiMode)), ['direct', 'pop'], true)
            ? strtolower(trim((string) $apiMode))
            : DuitkuPaymentChannels::apiMode($paymentCode);

        return $this->transactionStatus($merchantOrderId, $config, $mode);
    }

    public function getPaymentMethod(string $amount, Config $config): array
    {
        return $this->decode(Pop::getPaymentMethod($amount, $config));
    }

    private function decode(mixed $response): array
    {
        $payload = json_decode((string) $response, true);

        if (! is_array($payload)) {
            throw new RuntimeException('Invalid Duitku response.');
        }

        return $payload;
    }
}
