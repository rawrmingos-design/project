<?php

namespace App\Tenancy;

use App\Models\Tenant;
use App\Tenancy\Contracts\DnsResolverInterface;
use App\Tenancy\Exceptions\DnsLookupException;
use Carbon\Carbon;

class TenantDomainService
{
    public function __construct(
        private readonly DnsResolverInterface $dnsResolver,
    ) {}

    /**
     * Normalize a domain string to a canonical lowercase host.
     * Strips scheme, path, port, whitespace. Returns empty string for invalid input.
     * Result never exceeds 253 characters.
     */
    public function normalizeDomain(?string $domain): string
    {
        if ($domain === null || trim($domain) === '') {
            return '';
        }

        $domain = trim($domain);

        // If no scheme is present but has "://", parse_url may still work.
        // Add a scheme if missing so parse_url can extract the host properly.
        $hasSchemeSeparator = str_contains($domain, '://');
        $toParse = $hasSchemeSeparator ? $domain : 'https://' . $domain;

        $parsed = parse_url($toParse);

        // Extract the host component
        $host = $parsed['host'] ?? '';

        // If host is empty after parsing, return empty string
        if ($host === '') {
            return '';
        }

        // Lowercase the host
        $host = strtolower($host);

        // Truncate to 253 characters (DNS max label length)
        if (strlen($host) > 253) {
            $host = substr($host, 0, 253);
        }

        return $host;
    }

    /**
     * Generate a verification token for a tenant's custom domain.
     * Format: topupengine-verify-{32 hex chars}
     * Idempotent: retains existing token if domain unchanged.
     *
     * @throws \RuntimeException if a unique token cannot be generated after 3 attempts
     */
    public function generateVerificationToken(Tenant $tenant): string
    {
        // Idempotent: if tenant already has a token, return existing one
        if ($tenant->custom_domain_verification_token !== null) {
            return $tenant->custom_domain_verification_token;
        }

        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $token = 'topupengine-verify-' . bin2hex(random_bytes(16));

            // Check for collision with other tenants
            $collision = Tenant::where('custom_domain_verification_token', $token)
                ->where('id', '!=', $tenant->id)
                ->exists();

            if (! $collision) {
                $tenant->custom_domain_verification_token = $token;
                $tenant->custom_domain_status = Tenant::DOMAIN_STATUS_PENDING;
                $tenant->save();

                return $token;
            }
        }

        throw new \RuntimeException(
            "Unable to generate a unique verification token after {$maxAttempts} attempts."
        );
    }

    /**
     * Perform DNS TXT record verification for a tenant's domain.
     * Updates tenant status to verified or failed.
     * Returns true on success, false on failure.
     */
    public function verifyDomain(Tenant $tenant): bool
    {
        // Check prerequisites: domain and token must exist
        if (empty($tenant->custom_domain)) {
            $tenant->custom_domain_status = Tenant::DOMAIN_STATUS_FAILED;
            $tenant->custom_domain_last_error = 'Cannot verify domain: no custom domain is set.';
            $tenant->save();

            return false;
        }

        if (empty($tenant->custom_domain_verification_token)) {
            $tenant->custom_domain_status = Tenant::DOMAIN_STATUS_FAILED;
            $tenant->custom_domain_last_error = 'Cannot verify domain: no verification token is set.';
            $tenant->save();

            return false;
        }

        $domain = $tenant->custom_domain;
        $token = $tenant->custom_domain_verification_token;

        // Query DNS TXT records, handling exceptions gracefully
        try {
            $txtRecords = $this->dnsResolver->getTxtRecords($domain, 10);
        } catch (DnsLookupException $e) {
            $tenant->custom_domain_status = Tenant::DOMAIN_STATUS_FAILED;
            $tenant->custom_domain_last_error = "DNS lookup failed ({$e->failureType}) for domain {$e->domain}.";
            $tenant->save();

            return false;
        }

        // Exact case-sensitive match of token against TXT record values
        if (in_array($token, $txtRecords, true)) {
            $tenant->custom_domain_status = Tenant::DOMAIN_STATUS_VERIFIED;
            $tenant->custom_domain_verified_at = Carbon::now();
            $tenant->custom_domain_last_error = null;
            $tenant->save();

            return true;
        }

        // Token not found in TXT records
        $tenant->custom_domain_status = Tenant::DOMAIN_STATUS_FAILED;
        $tenant->custom_domain_last_error = "TXT record not found for domain {$domain}. Expected: {$token}";
        $tenant->save();

        return false;
    }

    /**
     * Handle domain change: reset verification state or clear all fields.
     * Called when custom_domain value is updated.
     */
    public function handleDomainChange(Tenant $tenant, ?string $newDomain): void
    {
        $normalized = $this->normalizeDomain($newDomain);
        $currentDomain = $tenant->custom_domain ?? '';
        $currentNormalized = $this->normalizeDomain($currentDomain);

        // If normalized is empty, domain was removed — clear all fields
        if ($normalized === '') {
            $tenant->custom_domain = null;
            $tenant->custom_domain_status = null;
            $tenant->custom_domain_verification_token = null;
            $tenant->custom_domain_verified_at = null;
            $tenant->custom_domain_last_error = null;
            $tenant->save();

            return;
        }

        // If same normalized value: no-op
        if ($normalized === $currentNormalized) {
            return;
        }

        // Different domain: reset verification state
        $tenant->custom_domain = $normalized;
        $tenant->custom_domain_verification_token = null;
        $tenant->custom_domain_verified_at = null;
        $tenant->custom_domain_last_error = null;
        $tenant->save();

        // Generate new token (which also sets status to pending)
        $this->generateVerificationToken($tenant);
    }

    /**
     * Validate that a domain is not reserved by the platform.
     * Returns array of validation errors (empty = valid).
     */
    public function filterDomain(string $normalizedDomain, ?int $excludeTenantId = null): array
    {
        $errors = [];

        // Get platform host from config('app.url')
        $appUrl = config('app.url');
        $platformHost = $appUrl ? strtolower(parse_url($appUrl, PHP_URL_HOST) ?? '') : '';

        // Get admin domain
        $adminDomain = strtolower(trim((string) config('app.filament_admin_domain')));

        // Get docs domain
        $docsDomain = strtolower(trim((string) env('DOCS_DOMAIN')));

        // Check 1: Reject if matches app URL host
        if ($platformHost !== '' && $normalizedDomain === $platformHost) {
            $errors[] = "The domain \"{$normalizedDomain}\" is reserved by the platform.";
        }

        // Check 2: Reject if matches Filament admin domain
        if ($adminDomain !== '' && $normalizedDomain === $adminDomain) {
            $errors[] = "The domain \"{$normalizedDomain}\" is reserved by the platform.";
        }

        // Check 3: Reject if matches docs domain
        if ($docsDomain !== '' && $normalizedDomain === $docsDomain) {
            $errors[] = "The domain \"{$normalizedDomain}\" is reserved by the platform.";
        }

        // Check 4: Reject localhost
        if ($normalizedDomain === 'localhost') {
            $errors[] = "The domain \"localhost\" is reserved by the platform.";
        }

        // Check 5: Reject IP addresses (IPv4/IPv6)
        if (filter_var($normalizedDomain, FILTER_VALIDATE_IP) !== false) {
            $errors[] = "IP addresses are not allowed as custom domains.";
        }

        // Check 6: Reject subdomains of the platform host
        if ($platformHost !== '' && $normalizedDomain !== $platformHost) {
            $suffix = '.' . $platformHost;
            if (str_ends_with($normalizedDomain, $suffix)) {
                $errors[] = "Subdomains of the platform host \"{$platformHost}\" are not allowed.";
            }
        }

        // Check 7: Reject reserved subdomains of the platform host
        if ($platformHost !== '') {
            foreach (Tenant::RESERVED_SUBDOMAINS as $reserved) {
                $reservedFqdn = strtolower($reserved) . '.' . $platformHost;
                if ($normalizedDomain === $reservedFqdn) {
                    $errors[] = "The subdomain \"{$reserved}\" is reserved by the platform.";
                    break;
                }
            }
        }

        // Check 8: Reject domains already assigned to another tenant
        $query = Tenant::where('custom_domain', $normalizedDomain);
        if ($excludeTenantId !== null) {
            $query->where('id', '!=', $excludeTenantId);
        }
        if ($query->exists()) {
            $errors[] = "The domain \"{$normalizedDomain}\" is already in use by another tenant.";
        }

        return $errors;
    }
}
