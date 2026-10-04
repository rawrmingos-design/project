<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kontrak docs vs kode.
 *
 * Akar masalah docs lama bukan isi yang jelek, tapi **tidak ada yang membandingkan
 * docs dengan kode** — jadi docs bisa menyebut header yang tidak pernah dibaca,
 * error code yang tidak pernah dikirim, atau endpoint yang tidak ada, tanpa ada yang
 * tahu. Test ini mengekstrak token yang pasti dari markdown docs lalu memastikan
 * masing-masing benar-benar ada di kode sumber.
 *
 * Token yang diperiksa sengaja dibatasi pada yang tak bisa ditafsirkan ganda:
 * `error_code`, nama header, dan path endpoint `api/v1/...`. Bebas dari regex rapuh
 * yang menafsirkan prosa.
 */
class DocsContractDriftTest extends TestCase
{
    /**
     * Kumpulan seluruh isi markdown docs (sumber, bukan hasil build).
     *
     * @return array<string, string> path relatif => isi
     */
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

        $this->assertNotSame([], $sources, 'Tidak ada file markdown docs yang terbaca.');

        return $sources;
    }

    /** Semua isi digabung untuk pencarian token. */
    private function allDocs(): string
    {
        return implode("\n", array_values($this->docsSources()));
    }

    /**
     * Gabungan kode yang BERHAK menentukan kontrak: response builder, middleware,
     * dan definisi route API.
     */
    private function contractCode(): string
    {
        $files = [
            'app/Support/ResellerApiResponse.php',
            'app/Http/Controllers/Api/OrderApiController.php',
            'app/Http/Controllers/Api/SandboxOrderApiController.php',
            'app/Http/Middleware/EnforceResellerIpWhitelist.php',
            'app/Http/Middleware/ResolveLiveResellerIntegration.php',
            'app/Http/Middleware/ResolveSandboxResellerIntegration.php',
            'app/Services/ResellerCallbackDeliveryService.php',
            'routes/api.php',
        ];

        $code = '';

        foreach ($files as $relative) {
            $path = base_path($relative);

            if (File::exists($path)) {
                $code .= "\n" . File::get($path);
            }
        }

        $this->assertNotSame('', trim($code), 'Tidak ada file kontrak yang terbaca — daftar file mungkin sudah basi.');

        return $code;
    }

    public function test_every_error_code_documented_exists_in_code(): void
    {
        $docs = $this->allDocs();
        $code = $this->contractCode();

        // Error code nyata berbentuk SCREAMING_SNAKE_CASE dan ditulis di kolom pertama
        // tabel. Versi pertama test ini hanya menangkap token lowercase (`[a-z]`), jadi
        // SEMUA error code asli lolos tanpa diperiksa — test hijau palsu. Ekstraksi di
        // bawah membaca sel tabel yang tepat, jadi menghapus/mengganti satu error code
        // benar-benar membuat test ini MERAH.
        preg_match_all('/^\|\s*`([A-Z][A-Z0-9_]{3,})`/m', $docs, $matches);

        $codes = array_values(array_unique($matches[1]));

        $this->assertNotSame(
            [],
            $codes,
            'Tidak ada error code SCREAMING_SNAKE_CASE yang terbaca dari tabel docs — format berubah?'
        );

        // Sanity: jumlahnya harus masuk akal untuk dokumentasi ini (≥ 8), supaya test
        // tidak diam-diam menyusut jadi pemeriksaan satu token saja.
        $this->assertGreaterThanOrEqual(
            8,
            count($codes),
            'Terlalu sedikit error code yang terbaca (' . count($codes) . ') — regex berhenti cocok?'
        );

        $missing = [];

        foreach ($codes as $token) {
            if (! str_contains($code, $token)) {
                $missing[] = $token;
            }
        }

        $this->assertSame(
            [],
            $missing,
            "error_code ini didokumentasikan tapi TIDAK ADA di kode (docs melenceng dari kontrak):\n  - "
                . implode("\n  - ", $missing)
        );
    }

    public function test_every_api_v1_endpoint_documented_is_registered(): void
    {
        $docs = $this->allDocs();

        // Path endpoint docs selalu ditulis sebagai `POST /api/v1/...` atau `/api/v1/...`.
        preg_match_all('#/api/v1(/[a-z0-9\-]+)#', $docs, $matches);

        $paths = array_values(array_unique($matches[1]));

        $this->assertNotSame([], $paths, 'Tidak ada path /api/v1 yang disebut di docs — format berubah?');

        // Diperiksa dari ROUTER yang benar-benar terdaftar, bukan dari teks routes/api.php.
        // Mencocokkan source dengan regex rapuh: route berparameter ditulis
        // `'status-order/{invoice}'`, dan prefix `v1` ditulis sekali di `Route::prefix('v1')`.
        $registered = [];

        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/v1/')) {
                continue;
            }

            // `api/v1/variant` → `variant`; `api/v1/status-order/{invoice}` → `status-order`.
            $registered[] = explode('/', substr($uri, strlen('api/v1/')))[0];
        }

        $registered = array_values(array_unique($registered));

        $this->assertNotSame([], $registered, 'Tidak ada route api/v1 yang terdaftar — prefix berubah?');

        $missing = [];

        foreach ($paths as $path) {
            $segment = ltrim($path, '/');

            if (! in_array($segment, $registered, true)) {
                $missing[] = '/api/v1' . $path;
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Endpoint ini didokumentasikan tapi TIDAK terdaftar di router:\n  - "
                . implode("\n  - ", $missing)
                . "\n\nRoute api/v1 yang ada: " . implode(', ', $registered)
        );
    }

    public function test_authentication_is_documented_as_bearer_only(): void
    {
        $docs = $this->allDocs();

        $this->assertStringContainsStringIgnoringCase(
            'bearer',
            $docs,
            'Docs harus menjelaskan autentikasi bearer token.'
        );

        // Header ini TIDAK PERNAH dibaca middleware autentikasi (hanya muncul di pesan
        // error). Kalau docs menuntutnya sebagai syarat, integrator akan mengejar header
        // yang tidak berpengaruh apa-apa — jadi kehadirannya sebagai syarat = MERAH.
        $this->assertDoesNotMatchRegularExpression(
            '/X-Reseller-Integration-Code/',
            $docs,
            'Docs tidak boleh menyebut X-Reseller-Integration-Code sebagai syarat: header itu tidak pernah dibaca kode.'
        );
    }
}
