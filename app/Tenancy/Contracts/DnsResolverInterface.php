<?php

namespace App\Tenancy\Contracts;

interface DnsResolverInterface
{
    /**
     * Retrieve TXT records for a domain.
     *
     * @param string $domain The domain to query
     * @param int $timeout Timeout in seconds
     * @return array<string> Array of TXT record values
     * @throws \App\Tenancy\Exceptions\DnsLookupException On network/timeout failure
     */
    public function getTxtRecords(string $domain, int $timeout = 10): array;
}
