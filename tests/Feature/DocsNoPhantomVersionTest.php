<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Docs tidak boleh memuat versi atau host yang tidak punya bukti di kode.
 *
 * Docs lama memuat narasi "BREAKING CHANGES v2.4" dan penomoran v2.3/v2.4 — padahal
 * riwayat git tidak punya tag versi sama sekali (`git tag` kosong); satu-satunya
 * penomoran versi nyata adalah header `X-API-Version: 1`. Begitu pula host contoh:
 * `namadomain.com` akan membuat integrator bingung. Test ini menolak semuanya di
 * sumber docs, jadi narasi fiktif tidak bisa masuk lagi tanpa CI MERAH.
 *
 * Catatan: perubahan `/product` → `/category` itu NYATA (commit def1b4e0), jadi
 * `/category` justru WAJIB ada di docs — bukan dilarang.
 */
class DocsNoPhantomVersionTest extends TestCase
{
    /** @return array<string, string> */
    private function docsSources(): array
    {
        $base = base_path('docs-api/docs');

        $this->assertDirectoryExists($base, 'Sumber docs (docs-api/docs) tidak ditemukan.');

        $sources = [];

        foreach (File::allFiles($base) as $file) {
            if (! in_array($file->getExtension(), ['md', 'mdx'], true)) {
                continue;
            }

            $sources[$file->getRelativePathname()] = File::get($file->getPathname());
        }

        return $sources;
    }

    public function test_docs_do_not_mention_phantom_api_versions(): void
    {
        $violations = [];

        foreach ($this->docsSources() as $path => $source) {
            // v2.x, v3.x — versi H2H yang tidak pernah ada di riwayat repo.
            if (preg_match_all('/\bv([2-9]\.[0-9]+)\b/', $source, $matches)) {
                foreach (array_unique($matches[1]) as $version) {
                    $violations[] = "{$path}: versi fiktif v{$version}";
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Docs memuat nomor versi yang tidak punya bukti di kode (git tag kosong):\n  - "
                . implode("\n  - ", $violations)
        );
    }

    public function test_docs_do_not_reference_the_retired_product_endpoint(): void
    {
        $violations = [];

        foreach ($this->docsSources() as $path => $source) {
            // `/product` sudah di-rename ke `/category` (def1b4e0); menyebut endpoint
            // lama sebagai cara pakai = integrator akan menembak endpoint yang hilang.
            if (preg_match('#/api/v1/product\b#', $source)) {
                $violations[] = $path;
            }
        }

        $this->assertSame([], $violations, "Docs masih menyebut endpoint lama /api/v1/product:\n  - " . implode("\n  - ", $violations));
    }

    public function test_docs_use_the_api_base_component_instead_of_hardcoded_hosts(): void
    {
        $violations = [];

        foreach ($this->docsSources() as $path => $source) {
            // Host literal di contoh akan salah di environment lain (staging vs prod).
            // Satu-satunya sumber base URL adalah build arg + komponen <ApiBase />.
            foreach (['istanatopup.com', 'jasakoding.web.id', 'namadomain.com', 'example.com'] as $host) {
                if (str_contains($source, $host)) {
                    $violations[] = "{$path}: host literal `{$host}`";
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Docs memuat host literal — gunakan komponen <ApiBase /> supaya base URL dari build arg:\n  - "
                . implode("\n  - ", $violations)
        );
    }

    public function test_docs_reference_the_real_version_header(): void
    {
        $docs = implode("\n", array_values($this->docsSources()));

        $this->assertStringContainsString(
            'X-API-Version',
            $docs,
            'Docs harus menyebut header versi yang benar (X-API-Version).'
        );
    }
}
