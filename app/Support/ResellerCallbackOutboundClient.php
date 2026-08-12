<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class ResellerCallbackOutboundClient
{
    /**
     * @param array<string, string> $headers
     * @return array{response: Response|null, error: string|null}
     */
    public function post(string $url, string $mode, array $headers, string $body, int $timeoutMs): array
    {
        $validationError = ResellerCallbackUrlValidator::failureReason($url, $mode);
        if ($validationError !== null) {
            return ['response' => null, 'error' => 'destination_blocked'];
        }

        if (config('reseller_callbacks.dns_resolution', true)
            && ! $this->hostResolvesToPublicAddresses($url)
            && ! app()->environment('testing')) {
            return ['response' => null, 'error' => 'destination_blocked'];
        }

        try {
            $response = Http::connectTimeout(max(1, (int) ceil(max(1000, $timeoutMs) / 1000)))
                ->timeout(max(1, (int) ceil(max(1000, $timeoutMs) / 1000)))
                ->withOptions([
                    'allow_redirects' => false,
                    'http_errors' => false,
                    'max_content_length' => (int) config('reseller_callbacks.max_response_bytes', 65536),
                ])
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($url);

            if (strlen($response->body()) > (int) config('reseller_callbacks.max_response_bytes', 65536)) {
                return ['response' => null, 'error' => 'response_too_large'];
            }

            return ['response' => $response, 'error' => null];
        } catch (Throwable) {
            return ['response' => null, 'error' => 'connection_failed'];
        }
    }

    private function hostResolvesToPublicAddresses(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return ResellerCallbackUrlValidator::isPublicIp($host);
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (! is_array($records) || $records === []) {
            return false;
        }

        $addresses = [];
        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($address)) {
                $addresses[] = $address;
            }
        }

        return $addresses !== [] && array_reduce(
            $addresses,
            static fn (bool $valid, string $address): bool => $valid && ResellerCallbackUrlValidator::isPublicIp($address),
            true,
        );
    }
}
