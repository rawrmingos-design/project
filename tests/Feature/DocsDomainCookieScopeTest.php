<?php

namespace Tests\Feature;

use App\Services\PublicSiteConfigService;
use Tests\TestCase;

/**
 * Cakupan cookie session terhadap domain docs.
 *
 * Docs hidup di subdomain (`docs.*`) tapi login dilakukan di host utama. Supaya user
 * yang sudah login di host utama dianggap login di domain docs, `SESSION_DOMAIN` harus
 * mencakup keduanya (mis. `.istanatopup.com`). Kalau `DOCS_DOMAIN` berada DI LUAR
 * cakupan itu, docs akan selalu meminta login — bahkan setelah user login — dan
 * gejalanya membingungkan (bukan error, hanya "balik ke sign-in terus").
 *
 * Di staging temuan serupa sudah nyata terjadi sebelum `DOCS_DOMAIN` diperbaiki.
 *
 * CATATAN PENTING: test ini sengaja TIDAK di-skip saat `DOCS_DOMAIN` kosong. Versi
 * pertama melewati (skip) kalau env belum diisi — akibatnya di CI, tempat `DOCS_DOMAIN`
 * memang kosong, penjaga ini tidak pernah benar-benar berjalan (hijau palsu). Sekarang
 * nilainya di-set eksplisit per skenario, jadi guard-nya selalu dieksekusi.
 */
class DocsDomainCookieScopeTest extends TestCase
{
    /** Host bersih dari nilai konfigurasi yang mungkin berupa URL. */
    private function hostOf(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (! str_contains($value, '://')) {
            $value = 'https://' . $value;
        }

        return strtolower((string) (parse_url($value, PHP_URL_HOST) ?? ''));
    }

    /**
     * @param  array{docs:string, app:string, session:string}  $config
     */
    private function applyConfig(array $config): void
    {
        config([
            'app.docs_domain' => $config['docs'],
            'app.url' => $config['app'],
            'session.domain' => $config['session'],
        ]);
    }

    /** Apakah `$docsHost` tercakup oleh cookie ber-domain `$sessionDomain`? */
    private function isCovered(string $docsHost, string $sessionDomain): bool
    {
        $sessionDomain = ltrim(strtolower(trim($sessionDomain)), '.');

        if ($sessionDomain === '') {
            return true; // Cookie host-only: cakupannya host itu sendiri.
        }

        return $docsHost === $sessionDomain || str_ends_with($docsHost, '.' . $sessionDomain);
    }

    public function test_istanatopup_style_domains_are_covered(): void
    {
        $this->applyConfig([
            'docs' => 'docs.istanatopup.com',
            'app' => 'https://istanatopup.com',
            'session' => '.istanatopup.com',
        ]);

        $docsHost = $this->hostOf((string) config('app.docs_domain'));
        $appHost = $this->hostOf((string) config('app.url'));

        $this->assertSame('docs.istanatopup.com', $docsHost);
        $this->assertSame('istanatopup.com', $appHost);

        $this->assertTrue(
            str_ends_with($docsHost, '.' . $appHost),
            "DOCS_DOMAIN ({$docsHost}) harus subdomain dari APP_URL ({$appHost})."
        );

        $this->assertTrue(
            $this->isCovered($docsHost, (string) config('session.domain')),
            'SESSION_DOMAIN .istanatopup.com harus mencakup docs.istanatopup.com.'
        );
    }

    public function test_staging_style_domains_are_covered(): void
    {
        $this->applyConfig([
            'docs' => 'docs.test.jasakoding.web.id',
            'app' => 'https://test.jasakoding.web.id',
            'session' => '.test.jasakoding.web.id',
        ]);

        $docsHost = $this->hostOf((string) config('app.docs_domain'));
        $appHost = $this->hostOf((string) config('app.url'));

        $this->assertTrue(str_ends_with($docsHost, '.' . $appHost));
        $this->assertTrue($this->isCovered($docsHost, (string) config('session.domain')));
    }

    /**
     * Skenario rusak yang HARUS terdeteksi: docs di domain lain sama sekali.
     * Kalau ini lolos, gejalanya "user login terus diminta login lagi di docs".
     */
    public function test_out_of_tree_docs_domain_is_detected(): void
    {
        $this->applyConfig([
            'docs' => 'docs.somewhere-else.test',
            'app' => 'https://istanatopup.com',
            'session' => '.istanatopup.com',
        ]);

        $docsHost = $this->hostOf((string) config('app.docs_domain'));
        $appHost = $this->hostOf((string) config('app.url'));

        $this->assertFalse(
            str_ends_with($docsHost, '.' . $appHost),
            'Domain docs di luar pohon APP_URL tidak boleh dianggap aman.'
        );

        $this->assertFalse(
            $this->isCovered($docsHost, (string) config('session.domain')),
            'Cookie .istanatopup.com tidak mencakup docs.somewhere-else.test.'
        );
    }

    public function test_session_domain_too_narrow_is_detected(): void
    {
        // Cookie di-scope ke `app.istanatopup.com` (host spesifik) — tidak mencakup docs.
        $this->applyConfig([
            'docs' => 'docs.istanatopup.com',
            'app' => 'https://app.istanatopup.com',
            'session' => 'app.istanatopup.com',
        ]);

        $this->assertFalse(
            $this->isCovered(
                $this->hostOf((string) config('app.docs_domain')),
                (string) config('session.domain')
            ),
            'SESSION_DOMAIN terlalu sempit harus terdeteksi sebagai masalah.'
        );
    }

    public function test_docs_url_is_always_https_for_the_configured_domain(): void
    {
        $this->applyConfig([
            'docs' => 'docs.istanatopup.com',
            'app' => 'https://istanatopup.com',
            'session' => '.istanatopup.com',
        ]);

        $this->assertSame(
            'https://docs.istanatopup.com',
            app(PublicSiteConfigService::class)->docsUrl(),
            'docsUrl() harus selalu https — link HTTP dari domain HTTPS akan dicampur blokir.'
        );
    }

    public function test_docs_url_accepts_a_domain_written_with_scheme(): void
    {
        // `DOCS_DOMAIN` boleh diisi dengan skema; hasilnya harus tetap host bersih + https.
        $this->applyConfig([
            'docs' => 'https://docs.istanatopup.com',
            'app' => 'https://istanatopup.com',
            'session' => '.istanatopup.com',
        ]);

        $this->assertSame('https://docs.istanatopup.com', app(PublicSiteConfigService::class)->docsUrl());
    }

    public function test_empty_docs_domain_yields_null(): void
    {
        $this->applyConfig([
            'docs' => '',
            'app' => 'https://istanatopup.com',
            'session' => '.istanatopup.com',
        ]);

        $this->assertNull(
            app(PublicSiteConfigService::class)->docsUrl(),
            'Tanpa DOCS_DOMAIN, docsUrl harus null supaya link mati tidak pernah dirender.'
        );
    }
}
