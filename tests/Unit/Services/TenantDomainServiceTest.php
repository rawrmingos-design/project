<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Tenancy\Contracts\DnsResolverInterface;
use App\Tenancy\TenantDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TenantDomainServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantDomainService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $dnsResolver = Mockery::mock(DnsResolverInterface::class);
        $this->service = new TenantDomainService($dnsResolver);
    }

    // --- Acceptance Criterion 1: Convert to lowercase ---

    public function test_normalize_domain_converts_to_lowercase(): void
    {
        $this->assertSame('example.com', $this->service->normalizeDomain('EXAMPLE.COM'));
        $this->assertSame('my-domain.org', $this->service->normalizeDomain('My-Domain.ORG'));
        $this->assertSame('test.example.co.uk', $this->service->normalizeDomain('TEST.Example.CO.UK'));
    }

    // --- Acceptance Criterion 2: Strip URI scheme ---

    public function test_normalize_domain_strips_https_scheme(): void
    {
        $this->assertSame('example.com', $this->service->normalizeDomain('https://example.com'));
    }

    public function test_normalize_domain_strips_http_scheme(): void
    {
        $this->assertSame('example.com', $this->service->normalizeDomain('http://example.com'));
    }

    public function test_normalize_domain_strips_ftp_scheme(): void
    {
        $this->assertSame('example.com', $this->service->normalizeDomain('ftp://example.com'));
    }

    // --- Acceptance Criterion 3: Strip path component ---

    public function test_normalize_domain_strips_path(): void
    {
        $this->assertSame('example.com', $this->service->normalizeDomain('example.com/path/to/page'));
        $this->assertSame('example.com', $this->service->normalizeDomain('https://example.com/path'));
    }

    // --- Acceptance Criterion 4: Strip port number ---

    public function test_normalize_domain_strips_port(): void
    {
        $this->assertSame('example.com', $this->service->normalizeDomain('example.com:8080'));
        $this->assertSame('example.com', $this->service->normalizeDomain('https://example.com:443'));
        $this->assertSame('localhost', $this->service->normalizeDomain('localhost:3000'));
    }

    // --- Acceptance Criterion 5: Trim leading/trailing whitespace ---

    public function test_normalize_domain_trims_whitespace(): void
    {
        $this->assertSame('example.com', $this->service->normalizeDomain('  example.com  '));
        $this->assertSame('example.com', $this->service->normalizeDomain("\t example.com \n"));
    }

    // --- Acceptance Criterion 6: Idempotent ---

    public function test_normalize_domain_is_idempotent(): void
    {
        $inputs = [
            'https://EXAMPLE.COM:8080/path',
            '  My-Domain.ORG  ',
            'http://test.co.uk/page?q=1',
            'example.com',
            'UPPER.CASE.COM',
        ];

        foreach ($inputs as $input) {
            $first = $this->service->normalizeDomain($input);
            $second = $this->service->normalizeDomain($first);
            $this->assertSame($first, $second, "normalizeDomain is not idempotent for input: {$input}");
        }
    }

    // --- Acceptance Criterion 7: null, empty, whitespace-only → empty string ---

    public function test_normalize_domain_returns_empty_for_null(): void
    {
        $this->assertSame('', $this->service->normalizeDomain(null));
    }

    public function test_normalize_domain_returns_empty_for_empty_string(): void
    {
        $this->assertSame('', $this->service->normalizeDomain(''));
    }

    public function test_normalize_domain_returns_empty_for_whitespace_only(): void
    {
        $this->assertSame('', $this->service->normalizeDomain('   '));
        $this->assertSame('', $this->service->normalizeDomain("\t\n"));
    }

    // --- Acceptance Criterion 8: Empty host after stripping → empty string ---

    public function test_normalize_domain_returns_empty_for_scheme_only(): void
    {
        $this->assertSame('', $this->service->normalizeDomain('https://'));
        $this->assertSame('', $this->service->normalizeDomain('http://'));
    }

    // --- Acceptance Criterion 9: Result never exceeds 253 characters ---

    public function test_normalize_domain_truncates_to_253_characters(): void
    {
        $longDomain = str_repeat('a', 300) . '.com';
        $result = $this->service->normalizeDomain($longDomain);
        $this->assertLessThanOrEqual(253, strlen($result));
    }

    // --- Combined scenarios ---

    public function test_normalize_domain_handles_combined_scheme_port_path(): void
    {
        $this->assertSame(
            'example.com',
            $this->service->normalizeDomain('https://EXAMPLE.COM:8080/path/to/page')
        );
    }

    public function test_normalize_domain_handles_subdomain(): void
    {
        $this->assertSame(
            'sub.domain.example.com',
            $this->service->normalizeDomain('https://SUB.DOMAIN.EXAMPLE.COM/path')
        );
    }

    // --- filterDomain: Acceptance Criterion 1 - Reject app URL host ---

    public function test_filter_domain_rejects_app_url_host(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $errors = $this->service->filterDomain('topupengine.test');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('reserved by the platform', $errors[0]);
    }

    // --- filterDomain: Acceptance Criterion 2 - Reject Filament admin domain ---

    public function test_filter_domain_rejects_filament_admin_domain(): void
    {
        // Temporarily set the env value
        putenv('FILAMENT_ADMIN_DOMAIN=admin.topupengine.test');
        $_ENV['FILAMENT_ADMIN_DOMAIN'] = 'admin.topupengine.test';
        $_SERVER['FILAMENT_ADMIN_DOMAIN'] = 'admin.topupengine.test';

        config(['app.url' => 'https://topupengine.test']);

        $errors = $this->service->filterDomain('admin.topupengine.test');

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, 'reserved by the platform'))
        );

        // Clean up
        putenv('FILAMENT_ADMIN_DOMAIN');
        unset($_ENV['FILAMENT_ADMIN_DOMAIN'], $_SERVER['FILAMENT_ADMIN_DOMAIN']);
    }

    // --- filterDomain: Acceptance Criterion 3 - Reject docs domain ---

    public function test_filter_domain_rejects_docs_domain(): void
    {
        putenv('DOCS_DOMAIN=docs.topupengine.test');
        $_ENV['DOCS_DOMAIN'] = 'docs.topupengine.test';
        $_SERVER['DOCS_DOMAIN'] = 'docs.topupengine.test';

        config(['app.url' => 'https://topupengine.test']);

        $errors = $this->service->filterDomain('docs.topupengine.test');

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, 'reserved by the platform'))
        );

        // Clean up
        putenv('DOCS_DOMAIN');
        unset($_ENV['DOCS_DOMAIN'], $_SERVER['DOCS_DOMAIN']);
    }

    // --- filterDomain: Acceptance Criterion 4 - Reject localhost ---

    public function test_filter_domain_rejects_localhost(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $errors = $this->service->filterDomain('localhost');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('reserved by the platform', $errors[0]);
    }

    // --- filterDomain: Acceptance Criterion 5 - Reject IP addresses ---

    public function test_filter_domain_rejects_ipv4_address(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $errors = $this->service->filterDomain('192.168.1.1');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('IP addresses are not allowed', $errors[0]);
    }

    public function test_filter_domain_rejects_ipv6_address(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $errors = $this->service->filterDomain('::1');

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, 'IP addresses are not allowed'))
        );
    }

    // --- filterDomain: Acceptance Criterion 6 - Reject subdomains of platform host ---

    public function test_filter_domain_rejects_subdomains_of_platform_host(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $errors = $this->service->filterDomain('anything.topupengine.test');

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, 'Subdomains of the platform host'))
        );
    }

    public function test_filter_domain_rejects_deep_subdomains_of_platform_host(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $errors = $this->service->filterDomain('deep.sub.topupengine.test');

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, 'Subdomains of the platform host'))
        );
    }

    // --- filterDomain: Acceptance Criterion 7 - Reject reserved subdomains ---

    public function test_filter_domain_rejects_reserved_subdomains(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        foreach (Tenant::RESERVED_SUBDOMAINS as $reserved) {
            $errors = $this->service->filterDomain("{$reserved}.topupengine.test");

            $this->assertNotEmpty($errors, "Expected rejection for reserved subdomain: {$reserved}");
        }
    }

    // --- filterDomain: Acceptance Criterion 8 - Reject domains already in use ---

    public function test_filter_domain_rejects_domain_already_assigned_to_another_tenant(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        Tenant::create([
            'name' => 'Existing Tenant',
            'subdomain' => 'existing',
            'custom_domain' => 'taken.example.com',
        ]);

        $errors = $this->service->filterDomain('taken.example.com');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('already in use', $errors[0]);
    }

    public function test_filter_domain_allows_own_domain_when_exclude_tenant_id_provided(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $tenant = Tenant::create([
            'name' => 'My Tenant',
            'subdomain' => 'mytenant',
            'custom_domain' => 'mydomain.example.com',
        ]);

        $errors = $this->service->filterDomain('mydomain.example.com', $tenant->id);

        $this->assertEmpty($errors);
    }

    // --- filterDomain: Acceptance Criterion 9 - Valid domain returns empty array ---

    public function test_filter_domain_accepts_valid_external_domain(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $errors = $this->service->filterDomain('my-custom-domain.example.com');

        $this->assertEmpty($errors);
    }

    public function test_filter_domain_accepts_different_tld(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $errors = $this->service->filterDomain('mystore.co.uk');

        $this->assertEmpty($errors);
    }

    // =========================================================================
    // PROPERTY-BASED TESTS for normalizeDomain()
    // =========================================================================

    /**
     * Property 1: Normalization strips all non-host components.
     *
     * For any string containing a URI scheme, path, port, or surrounding whitespace,
     * the normalizeDomain() output SHALL contain none of those components and SHALL
     * be entirely lowercase.
     *
     * Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5
     */
    public function test_property_1_normalization_strips_all_non_host_components(): void
    {
        $faker = \Faker\Factory::create();

        $schemes = ['http://', 'https://', 'ftp://', 'ftps://', 'ws://', 'wss://'];
        $paths = ['/path', '/path/to/page', '/index.html', '/api/v1/resource', '/a/b/c/d'];
        $ports = [':80', ':443', ':8080', ':3000', ':9090', ':1234'];
        $whitespaces = [' ', '  ', "\t", "\n", " \t ", "\r\n"];
        $tlds = ['com', 'org', 'net', 'io', 'co.uk', 'de', 'app', 'test'];

        for ($i = 0; $i < 100; $i++) {
            // Generate a random domain host
            $host = strtolower($faker->lexify('????')) . '.' . $faker->randomElement($tlds);

            // Randomly add scheme, port, path, whitespace
            $input = $host;

            $addScheme = $faker->boolean(60);
            $addPort = $faker->boolean(50);
            $addPath = $faker->boolean(50);
            $addWhitespace = $faker->boolean(40);

            if ($addPort) {
                $port = $faker->randomElement($ports);
                $input = $input . $port;
            }

            if ($addPath) {
                $path = $faker->randomElement($paths);
                $input = $input . $path;
            }

            if ($addScheme) {
                $scheme = $faker->randomElement($schemes);
                $input = $scheme . $input;
            }

            if ($addWhitespace) {
                $ws = $faker->randomElement($whitespaces);
                $input = $ws . $input . $ws;
            }

            // Also randomly uppercase parts of the input
            if ($faker->boolean(50)) {
                $input = strtoupper($input);
            }

            $result = $this->service->normalizeDomain($input);

            // Assert: result should not contain any scheme
            foreach ($schemes as $s) {
                $this->assertStringNotContainsString(
                    $s,
                    $result,
                    "Result '{$result}' still contains scheme '{$s}' for input: {$input}"
                );
            }

            // Assert: result should not contain a port
            $this->assertDoesNotMatchRegularExpression(
                '/:\d+/',
                $result,
                "Result '{$result}' still contains a port for input: {$input}"
            );

            // Assert: result should not contain path separator (/) indicating path remnants
            $this->assertStringNotContainsString(
                '/',
                $result,
                "Result '{$result}' still contains a path for input: {$input}"
            );

            // Assert: result should have no leading/trailing whitespace
            $this->assertSame(
                trim($result),
                $result,
                "Result has leading/trailing whitespace for input: {$input}"
            );

            // Assert: result is entirely lowercase
            $this->assertSame(
                strtolower($result),
                $result,
                "Result '{$result}' is not entirely lowercase for input: {$input}"
            );

            // Assert: result should not be empty (valid host was provided)
            $this->assertNotEmpty(
                $result,
                "Result is empty for input with valid host: {$input}"
            );
        }
    }

    /**
     * Property 2: Normalization idempotence.
     *
     * For any string input, applying normalizeDomain() twice SHALL produce the same
     * result as applying it once: normalizeDomain(normalizeDomain(x)) === normalizeDomain(x).
     *
     * Validates: Requirements 2.6
     */
    public function test_property_2_normalization_idempotence(): void
    {
        $faker = \Faker\Factory::create();

        $schemes = ['http://', 'https://', 'ftp://', ''];
        $paths = ['', '/path', '/path/to/page', '/index.html'];
        $ports = ['', ':80', ':443', ':8080', ':3000'];
        $tlds = ['com', 'org', 'net', 'io', 'co.uk', 'test'];

        for ($i = 0; $i < 100; $i++) {
            // Generate a diverse random URI-like string
            $host = $faker->lexify('?????') . '.' . $faker->randomElement($tlds);
            $scheme = $faker->randomElement($schemes);
            $port = $faker->randomElement($ports);
            $path = $faker->randomElement($paths);

            $input = $scheme . $host . $port . $path;

            // Randomly add whitespace
            if ($faker->boolean(30)) {
                $input = ' ' . $input . ' ';
            }

            // Randomly uppercase
            if ($faker->boolean(40)) {
                $input = strtoupper($input);
            }

            $firstPass = $this->service->normalizeDomain($input);
            $secondPass = $this->service->normalizeDomain($firstPass);

            $this->assertSame(
                $firstPass,
                $secondPass,
                "normalizeDomain is not idempotent for input: '{$input}' → first: '{$firstPass}', second: '{$secondPass}'"
            );
        }
    }

    /**
     * Property 3: Empty/whitespace input yields empty string.
     *
     * For any input that is null, empty, or composed entirely of whitespace characters,
     * normalizeDomain() SHALL return an empty string without throwing an exception.
     *
     * Validates: Requirements 2.7, 2.8
     */
    public function test_property_3_empty_whitespace_input_yields_empty_string(): void
    {
        $faker = \Faker\Factory::create();

        $whitespaceChars = [' ', "\t", "\n", "\r", "\r\n", "\v"];

        for ($i = 0; $i < 100; $i++) {
            // Generate various empty/whitespace-only inputs
            $type = $faker->numberBetween(0, 3);

            switch ($type) {
                case 0:
                    // null input
                    $input = null;
                    $label = 'null';
                    break;
                case 1:
                    // empty string
                    $input = '';
                    $label = 'empty string';
                    break;
                case 2:
                    // whitespace-only string of random length
                    $length = $faker->numberBetween(1, 20);
                    $input = '';
                    for ($j = 0; $j < $length; $j++) {
                        $input .= $faker->randomElement($whitespaceChars);
                    }
                    $label = 'whitespace-only (len ' . strlen($input) . ')';
                    break;
                case 3:
                    // Repeated spaces of varying lengths
                    $input = str_repeat(' ', $faker->numberBetween(1, 50));
                    $label = 'spaces (len ' . strlen($input) . ')';
                    break;
            }

            $result = $this->service->normalizeDomain($input);

            $this->assertSame(
                '',
                $result,
                "normalizeDomain should return empty string for {$label}, got: '{$result}'"
            );
        }
    }

    /**
     * Property 4: Normalized output length invariant.
     *
     * For any input string, the output of normalizeDomain() SHALL have a length
     * of at most 253 characters.
     *
     * Validates: Requirements 2.9
     */
    public function test_property_4_normalized_output_length_invariant(): void
    {
        $faker = \Faker\Factory::create();

        $tlds = ['com', 'org', 'net', 'io', 'co.uk', 'de'];
        $schemes = ['http://', 'https://', 'ftp://', ''];

        for ($i = 0; $i < 100; $i++) {
            // Generate strings of varying lengths including very long ones
            $lengthType = $faker->numberBetween(0, 4);

            switch ($lengthType) {
                case 0:
                    // Short domain (normal)
                    $input = $faker->lexify('????') . '.' . $faker->randomElement($tlds);
                    break;
                case 1:
                    // Medium-length domain with many subdomains
                    $parts = [];
                    for ($j = 0; $j < $faker->numberBetween(3, 8); $j++) {
                        $parts[] = $faker->lexify('??????');
                    }
                    $input = implode('.', $parts) . '.' . $faker->randomElement($tlds);
                    break;
                case 2:
                    // Very long domain (over 253 chars)
                    $input = str_repeat($faker->lexify('??????????') . '.', 30) . 'com';
                    break;
                case 3:
                    // Very long single label
                    $input = str_repeat('a', $faker->numberBetween(200, 500)) . '.com';
                    break;
                case 4:
                    // Long input with scheme, port, path (total way over 253)
                    $longHost = str_repeat('x', $faker->numberBetween(100, 400));
                    $scheme = $faker->randomElement($schemes);
                    $input = $scheme . $longHost . '.com:8080/very/long/path/' . str_repeat('z', 100);
                    break;
            }

            $result = $this->service->normalizeDomain($input);

            $this->assertLessThanOrEqual(
                253,
                strlen($result),
                "normalizeDomain output exceeds 253 chars (got " . strlen($result) . ") for input of length " . strlen($input)
            );
        }
    }

    // --- Property 5: IP addresses are always rejected ---
    // Validates: Requirements 3.5

    public function test_property_5_ip_addresses_are_always_rejected(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 100; $i++) {
            if ($i < 50) {
                // Generate random IPv4 addresses
                $ip = $faker->ipv4();
            } else {
                // Generate random IPv6 addresses
                $ip = $faker->ipv6();
            }

            $errors = $this->service->filterDomain($ip);

            $this->assertNotEmpty(
                $errors,
                "Property 5 violated: IP address '{$ip}' was not rejected by filterDomain()"
            );
            $this->assertTrue(
                collect($errors)->contains(fn ($e) => str_contains($e, 'IP addresses are not allowed')),
                "Property 5 violated: IP address '{$ip}' rejection did not contain expected message"
            );
        }
    }

    // --- Property 6: Platform host subdomains are always rejected ---
    // Validates: Requirements 3.6

    public function test_property_6_platform_host_subdomains_are_always_rejected(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 100; $i++) {
            // Generate a random prefix that forms a subdomain of the platform host
            $prefix = $faker->lexify('????????'); // 8 random lowercase letters
            $subdomain = strtolower($prefix) . '.topupengine.test';

            $errors = $this->service->filterDomain($subdomain);

            $this->assertNotEmpty(
                $errors,
                "Property 6 violated: Platform subdomain '{$subdomain}' was not rejected by filterDomain()"
            );
            $this->assertTrue(
                collect($errors)->contains(fn ($e) => str_contains($e, 'Subdomains of the platform host') || str_contains($e, 'reserved')),
                "Property 6 violated: Platform subdomain '{$subdomain}' rejection did not contain expected message"
            );
        }
    }

    // --- Property 7: Valid external domains are accepted ---
    // Validates: Requirements 3.9

    public function test_property_7_valid_external_domains_are_accepted(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $faker = \Faker\Factory::create();

        // TLDs that are clearly different from 'test' and won't match the platform host
        $safeTlds = ['com', 'net', 'org', 'io', 'co', 'dev', 'app', 'xyz', 'info', 'biz'];

        for ($i = 0; $i < 100; $i++) {
            // Generate random domain names that:
            // - Are not IP addresses
            // - Are not subdomains of topupengine.test
            // - Do not match any platform host
            // - Are not already assigned to another tenant
            $label = $faker->lexify('????????'); // 8 random lowercase letters
            $tld = $safeTlds[array_rand($safeTlds)];
            $domain = strtolower($label) . '.' . $tld;

            $errors = $this->service->filterDomain($domain);

            $this->assertEmpty(
                $errors,
                "Property 7 violated: Valid external domain '{$domain}' was rejected with errors: " . implode(', ', $errors)
            );
        }
    }

    // =========================================================================
    // PROPERTY-BASED TESTS for generateVerificationToken()
    // =========================================================================

    /**
     * Property 8: Verification token format invariant.
     *
     * For any generated verification token, the token SHALL match the regex pattern
     * ^topupengine-verify-[0-9a-f]{32}$ and have a total length of exactly 51 characters.
     *
     * Validates: Requirements 4.1, 4.4
     */
    public function test_property_8_verification_token_format_invariant(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $tenant = Tenant::create([
                'name' => "Tenant Format {$i}",
                'subdomain' => "tenant-fmt-{$i}",
                'custom_domain' => "domain-fmt-{$i}.example.com",
            ]);

            $token = $this->service->generateVerificationToken($tenant);

            // Assert token matches the expected format: topupengine-verify- followed by 32 hex chars
            $this->assertMatchesRegularExpression(
                '/^topupengine-verify-[0-9a-f]{32}$/',
                $token,
                "Property 8 violated: Token '{$token}' does not match expected format for tenant {$i}"
            );

            // Assert total length is exactly 51 characters (19 prefix + 32 hex)
            $this->assertSame(
                51,
                strlen($token),
                "Property 8 violated: Token '{$token}' has length " . strlen($token) . " instead of 51 for tenant {$i}"
            );
        }
    }

    /**
     * Property 9: Verification token uniqueness.
     *
     * For any set of N generated verification tokens (where N > 1), all tokens
     * SHALL be distinct from each other.
     *
     * Validates: Requirements 4.1, 4.4
     */
    public function test_property_9_verification_token_uniqueness(): void
    {
        $tokens = [];

        for ($i = 0; $i < 100; $i++) {
            $tenant = Tenant::create([
                'name' => "Tenant Unique {$i}",
                'subdomain' => "tenant-uniq-{$i}",
                'custom_domain' => "domain-uniq-{$i}.example.com",
            ]);

            $token = $this->service->generateVerificationToken($tenant);
            $tokens[] = $token;
        }

        // Assert all 100 tokens are unique (no duplicates)
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(
            count($tokens),
            $uniqueTokens,
            "Property 9 violated: Found duplicate tokens among " . count($tokens) . " generated tokens. Unique count: " . count($uniqueTokens)
        );
    }

    // =========================================================================
    // PROPERTY-BASED TESTS for verifyDomain()
    // =========================================================================

    /**
     * Property 10: DNS verification correctness.
     *
     * For any tenant with a valid custom domain and verification token, if the DNS TXT
     * records for that domain contain the exact token string, then verifyDomain() SHALL
     * set status to 'verified'; otherwise, it SHALL set status to 'failed'.
     *
     * Validates: Requirements 5.2, 5.4
     */
    public function test_property_10_dns_verification_correctness(): void
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 100; $i++) {
            // Create a tenant with a custom domain and verification token
            $domain = strtolower($faker->lexify('????????')) . '.' . $faker->randomElement(['com', 'net', 'org', 'io', 'dev']);
            $token = 'topupengine-verify-' . bin2hex(random_bytes(16));

            $tenant = Tenant::create([
                'name' => "Tenant DNS {$i}",
                'subdomain' => "tenant-dns-{$i}",
                'custom_domain' => $domain,
                'custom_domain_verification_token' => $token,
                'custom_domain_status' => Tenant::DOMAIN_STATUS_PENDING,
            ]);

            // Randomly decide if the token should be in the DNS records (50/50)
            $tokenPresent = $faker->boolean(50);

            // Generate random TXT records (noise)
            $txtRecords = [];
            $noiseCount = $faker->numberBetween(0, 5);
            for ($j = 0; $j < $noiseCount; $j++) {
                $txtRecords[] = $faker->sentence();
            }

            // If token should be present, insert it at a random position
            if ($tokenPresent) {
                $insertPos = $faker->numberBetween(0, count($txtRecords));
                array_splice($txtRecords, $insertPos, 0, [$token]);
            }

            // Mock the DnsResolverInterface for this iteration
            $dnsResolver = Mockery::mock(DnsResolverInterface::class);
            $dnsResolver->shouldReceive('getTxtRecords')
                ->with($domain, 10)
                ->andReturn($txtRecords);

            // Create a new service instance with this mock
            $service = new TenantDomainService($dnsResolver);

            // Call verifyDomain
            $result = $service->verifyDomain($tenant);

            // Refresh tenant from database to get updated attributes
            $tenant->refresh();

            if ($tokenPresent) {
                // Token IS in records: status should be 'verified', verified_at not null, last_error null
                $this->assertTrue(
                    $result,
                    "Property 10 violated (iteration {$i}): verifyDomain() should return true when token is in DNS records for domain '{$domain}'"
                );
                $this->assertSame(
                    Tenant::DOMAIN_STATUS_VERIFIED,
                    $tenant->custom_domain_status,
                    "Property 10 violated (iteration {$i}): status should be 'verified' when token is in DNS records for domain '{$domain}'"
                );
                $this->assertNotNull(
                    $tenant->custom_domain_verified_at,
                    "Property 10 violated (iteration {$i}): verified_at should not be null when token is in DNS records for domain '{$domain}'"
                );
                $this->assertNull(
                    $tenant->custom_domain_last_error,
                    "Property 10 violated (iteration {$i}): last_error should be null when token is in DNS records for domain '{$domain}'"
                );
            } else {
                // Token is NOT in records: status should be 'failed', last_error not null
                $this->assertFalse(
                    $result,
                    "Property 10 violated (iteration {$i}): verifyDomain() should return false when token is NOT in DNS records for domain '{$domain}'"
                );
                $this->assertSame(
                    Tenant::DOMAIN_STATUS_FAILED,
                    $tenant->custom_domain_status,
                    "Property 10 violated (iteration {$i}): status should be 'failed' when token is NOT in DNS records for domain '{$domain}'"
                );
                $this->assertNotNull(
                    $tenant->custom_domain_last_error,
                    "Property 10 violated (iteration {$i}): last_error should not be null when token is NOT in DNS records for domain '{$domain}'"
                );
            }
        }
    }

    // =========================================================================
    // PROPERTY-BASED TESTS for handleDomainChange()
    // =========================================================================

    /**
     * Property 12: Domain change resets all verification state.
     *
     * For any tenant with existing verification state, when the custom domain is changed
     * to a different normalized value, all verification fields (custom_domain_status,
     * custom_domain_verification_token, custom_domain_verified_at, custom_domain_last_error)
     * SHALL be reset (status to 'pending' with new token, others to null).
     *
     * Validates: Requirements 7.1, 7.2, 7.3, 7.4
     */
    public function test_property_12_domain_change_resets_all_verification_state(): void
    {
        $faker = \Faker\Factory::create();

        $tlds = ['com', 'net', 'org', 'io', 'dev', 'app', 'xyz', 'info'];

        for ($i = 0; $i < 100; $i++) {
            // Create a tenant with a fully verified custom domain
            $originalDomain = strtolower($faker->lexify('????????')) . '.' . $faker->randomElement($tlds);
            $originalToken = 'topupengine-verify-' . bin2hex(random_bytes(16));

            $tenant = Tenant::create([
                'name' => "Tenant Change {$i}",
                'subdomain' => "tenant-chg-{$i}",
                'custom_domain' => $originalDomain,
                'custom_domain_status' => Tenant::DOMAIN_STATUS_VERIFIED,
                'custom_domain_verification_token' => $originalToken,
                'custom_domain_verified_at' => now(),
                'custom_domain_last_error' => null,
            ]);

            // Generate a DIFFERENT domain (ensure it normalizes to a different value)
            $newDomain = strtolower($faker->lexify('????????')) . '-new.' . $faker->randomElement($tlds);

            // Ensure the new domain is actually different from the original
            while ($this->service->normalizeDomain($newDomain) === $this->service->normalizeDomain($originalDomain)) {
                $newDomain = strtolower($faker->lexify('????????')) . '-new.' . $faker->randomElement($tlds);
            }

            // Create a fresh service with mocked DNS resolver for this iteration
            $dnsResolver = Mockery::mock(DnsResolverInterface::class);
            $service = new TenantDomainService($dnsResolver);

            // Call handleDomainChange with the different domain
            $service->handleDomainChange($tenant, $newDomain);

            // Refresh the tenant from database
            $tenant->refresh();

            // Assert: status is reset to 'pending'
            $this->assertSame(
                Tenant::DOMAIN_STATUS_PENDING,
                $tenant->custom_domain_status,
                "Property 12 violated (iteration {$i}): custom_domain_status should be 'pending' after domain change from '{$originalDomain}' to '{$newDomain}', got '{$tenant->custom_domain_status}'"
            );

            // Assert: verification token is not null and different from original
            $this->assertNotNull(
                $tenant->custom_domain_verification_token,
                "Property 12 violated (iteration {$i}): custom_domain_verification_token should not be null after domain change"
            );
            $this->assertNotSame(
                $originalToken,
                $tenant->custom_domain_verification_token,
                "Property 12 violated (iteration {$i}): custom_domain_verification_token should be different from original token after domain change"
            );

            // Assert: verified_at is cleared to null
            $this->assertNull(
                $tenant->custom_domain_verified_at,
                "Property 12 violated (iteration {$i}): custom_domain_verified_at should be null after domain change"
            );

            // Assert: last_error is cleared to null
            $this->assertNull(
                $tenant->custom_domain_last_error,
                "Property 12 violated (iteration {$i}): custom_domain_last_error should be null after domain change"
            );
        }
    }

    /**
     * Property 13: Same normalized domain preserves verification state.
     *
     * For any two domain input strings that produce the same normalized output, changing
     * from one to the other SHALL NOT modify the verification state or generate a new token.
     *
     * Validates: Requirements 7.6
     */
    public function test_property_13_same_normalized_domain_preserves_verification_state(): void
    {
        $faker = \Faker\Factory::create();

        $tlds = ['com', 'net', 'org', 'io', 'dev', 'app', 'xyz', 'info'];
        $schemes = ['http://', 'https://', 'ftp://'];
        $ports = [':80', ':443', ':8080', ':3000'];
        $paths = ['/path', '/page', '/index.html', '/api'];

        for ($i = 0; $i < 100; $i++) {
            // Create a base domain (already normalized — lowercase, no scheme/port/path)
            $baseDomain = strtolower($faker->lexify('????????')) . '.' . $faker->randomElement($tlds);

            // Create a tenant with this domain in a verified state
            $originalToken = 'topupengine-verify-' . bin2hex(random_bytes(16));
            $verifiedAt = now()->subDays($faker->numberBetween(1, 30));

            $tenant = Tenant::create([
                'name' => "Tenant Same {$i}",
                'subdomain' => "tenant-same-{$i}",
                'custom_domain' => $baseDomain,
                'custom_domain_status' => Tenant::DOMAIN_STATUS_VERIFIED,
                'custom_domain_verification_token' => $originalToken,
                'custom_domain_verified_at' => $verifiedAt,
                'custom_domain_last_error' => null,
            ]);

            // Generate a variant of the same domain that normalizes to the same value
            // Apply random transformations: uppercase, add scheme, add port, add path, add whitespace
            $variant = $baseDomain;

            $transformationType = $faker->numberBetween(0, 4);
            switch ($transformationType) {
                case 0:
                    // Uppercase version
                    $variant = strtoupper($baseDomain);
                    break;
                case 1:
                    // With scheme
                    $variant = $faker->randomElement($schemes) . $baseDomain;
                    break;
                case 2:
                    // With port
                    $variant = $baseDomain . $faker->randomElement($ports);
                    break;
                case 3:
                    // With path
                    $variant = $baseDomain . $faker->randomElement($paths);
                    break;
                case 4:
                    // With whitespace
                    $variant = '  ' . strtoupper($baseDomain) . '  ';
                    break;
            }

            // Verify that the variant normalizes to the same value as the base domain
            $normalizedVariant = $this->service->normalizeDomain($variant);
            $this->assertSame(
                $baseDomain,
                $normalizedVariant,
                "Test setup error (iteration {$i}): variant '{$variant}' did not normalize to '{$baseDomain}', got '{$normalizedVariant}'"
            );

            // Create a fresh service with mocked DNS resolver
            $dnsResolver = Mockery::mock(DnsResolverInterface::class);
            $service = new TenantDomainService($dnsResolver);

            // Call handleDomainChange with the variant that normalizes to the same domain
            $service->handleDomainChange($tenant, $variant);

            // Refresh the tenant from database
            $tenant->refresh();

            // Assert: all verification fields remain unchanged
            $this->assertSame(
                Tenant::DOMAIN_STATUS_VERIFIED,
                $tenant->custom_domain_status,
                "Property 13 violated (iteration {$i}): custom_domain_status should remain 'verified' when domain normalizes to same value. Variant: '{$variant}'"
            );

            $this->assertSame(
                $originalToken,
                $tenant->custom_domain_verification_token,
                "Property 13 violated (iteration {$i}): custom_domain_verification_token should remain unchanged when domain normalizes to same value. Variant: '{$variant}'"
            );

            $this->assertNotNull(
                $tenant->custom_domain_verified_at,
                "Property 13 violated (iteration {$i}): custom_domain_verified_at should remain not null when domain normalizes to same value. Variant: '{$variant}'"
            );

            $this->assertNull(
                $tenant->custom_domain_last_error,
                "Property 13 violated (iteration {$i}): custom_domain_last_error should remain null when domain normalizes to same value. Variant: '{$variant}'"
            );
        }
    }
}
