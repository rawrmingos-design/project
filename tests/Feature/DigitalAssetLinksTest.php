<?php

namespace Tests\Feature;

use Tests\TestCase;

class DigitalAssetLinksTest extends TestCase
{
    public function test_asset_links_statement_is_served_with_valid_json(): void
    {
        $response = $this->get('/.well-known/assetlinks.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');

        $statements = json_decode($response->getContent(), true);
        $this->assertIsArray($statements);
        $this->assertNotEmpty($statements);

        $statement = $statements[0];
        $this->assertSame(
            ['delegate_permission/common.handle_all_urls'],
            $statement['relation'],
        );
        $this->assertSame('android_app', $statement['target']['namespace']);
        $this->assertNotEmpty($statement['target']['package_name']);
        $this->assertMatchesRegularExpression(
            '/^[0-9A-F:]{95}$/',
            $statement['target']['sha256_cert_fingerprints'][0] ?? '',
            'Fingerprint harus format SHA-256 hex colon (atau placeholder yang akan diganti).',
        );
    }
}
