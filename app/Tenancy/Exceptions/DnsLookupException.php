<?php

namespace App\Tenancy\Exceptions;

class DnsLookupException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $domain,
        public readonly string $failureType, // 'timeout', 'network', 'nxdomain'
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
