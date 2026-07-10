<?php

namespace App\Services\Payments;

use Duitku\Config;
use Duitku\Pop;
use RuntimeException;

class DuitkuPopClient
{
    public function createInvoice(array $params, Config $config): array
    {
        $response = Pop::createInvoice($params, $config);
        $payload = json_decode((string) $response, true);

        if (! is_array($payload)) {
            throw new RuntimeException('Invalid Duitku response.');
        }

        return $payload;
    }
}
