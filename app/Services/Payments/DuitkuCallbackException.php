<?php

namespace App\Services\Payments;

use RuntimeException;

class DuitkuCallbackException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 400,
        public readonly string $reason = 'invalid_callback',
    ) {
        parent::__construct($message);
    }
}
