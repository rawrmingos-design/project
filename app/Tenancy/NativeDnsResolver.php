<?php

namespace App\Tenancy;

use App\Tenancy\Contracts\DnsResolverInterface;
use App\Tenancy\Exceptions\DnsLookupException;

class NativeDnsResolver implements DnsResolverInterface
{
    /**
     * Retrieve TXT records for a domain using PHP's native dns_get_record().
     *
     * @param string $domain The domain to query
     * @param int $timeout Timeout in seconds (note: PHP's dns_get_record does not natively support timeout)
     * @return array<string> Array of TXT record values
     * @throws DnsLookupException On network/lookup failure
     */
    public function getTxtRecords(string $domain, int $timeout = 10): array
    {
        $records = @dns_get_record($domain, DNS_TXT);

        if ($records === false) {
            throw new DnsLookupException(
                "DNS lookup failed for domain: {$domain}",
                $domain,
                'network',
            );
        }

        $txtValues = [];

        foreach ($records as $record) {
            if (isset($record['txt'])) {
                $txtValues[] = $record['txt'];
            }
        }

        return $txtValues;
    }
}
