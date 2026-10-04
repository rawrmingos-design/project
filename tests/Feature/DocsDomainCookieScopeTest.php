<?php

namespace Tests\Feature;

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
 * Test ini tidak butuh database; ia memeriksa konfigurasi, bukan perilaku runtime.
 * Di staging temuan serupa sudah nyata terjadi sebelum `DOCS_DOMAIN` diperbaiki.
 */
class DocsDomainCookieScopeTest extends TestCase
{
    /** Ambil host bersih dari nilai konfigurasi yang mungkin berupa URL. */
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

    public function test_docs_domain_shares_the_session_cookie_scope(): void
    {
        $docsHost = $this->hostOf((string) config('app.docs_domain', ''));

        if ($docsHost === '') {
            $this->markTestSkipped('DOCS_DOMAIN tidak diisi di environment ini.');
        }

        $appHost = $this->hostOf((string) config('app.url', ''));
        $sessionDomain = ltrim(strtolower(trim((string) config('session.domain', ''))), '.');

        $this->assertNotSame('', $appHost, 'APP_URL harus terisi supaya cakupan cookie bisa diuji.');

        // Dokumen harus berada di domain yang sama atau subdomain dari host aplikasi.
        $sameTree = $docsHost === $appHost
            || str_ends_with($docsHost, '.' . $appHost)
            || str_ends_with($appHost, '.' . $docsHost);

        $this->assertTrue(
            $sameTree,
            "DOCS_DOMAIN ({$docsHost}) dan APP_URL ({$appHost}) tidak satu pohon domain — "
                . 'cookie session tidak akan berlaku di domain docs.'
        );

        // `SESSION_DOMAIN` harus berada di pohon yang sama, dan mencakup host docs.
        if ($sessionDomain !== '') {
            $covered = $docsHost === $sessionDomain
                || str_ends_with($docsHost, '.' . $sessionDomain);

            $this->assertTrue(
                $covered,
                "SESSION_DOMAIN (.{$sessionDomain}) tidak mencakup DOCS_DOMAIN ({$docsHost}) — "
                    . 'user yang sudah login akan tetap diminta login di domain docs.'
            );
        }
    }

    public function test_docs_domain_is_a_https_origin(): void
    {
        $docsHost = $this->hostOf((string) config('app.docs_domain', ''));

        if ($docsHost === '') {
            $this->markTestSkipped('DOCS_DOMAIN tidak diisi di environment ini.');
        }

        $this->assertSame(
            'https://' . $docsHost,
            app(\App\Services\PublicSiteConfigService::class)->docsUrl(),
            'docsUrl() harus selalu https — link HTTP dari domain HTTPS akan dicampur blokir.'
        );
    }
}
