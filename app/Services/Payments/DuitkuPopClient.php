<?php

namespace App\Services\Payments;

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

    public function transactionStatus(string $merchantOrderId, Config $config): array
    {
        return $this->decode(Pop::transactionStatus($merchantOrderId, $config));
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
