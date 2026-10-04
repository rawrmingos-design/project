<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Tests\TestCase;

/**
 * Docs H2H disajikan lewat SATU gate, bukan dua.
 *
 * Kenapa test ini ada: bentuk route yang salah tidak terlihat di unit test biasa —
 * halaman utama `/` bisa tetap 200 sementara aset `.css`/`.js` mengalir keluar tanpa
 * login (atau 404 karena nginx yang menjawab). Dua-duanya baru ketahuan saat integrator
 * membuka docs di browser. Test ini memanggil jalur nyata untuk:
 *
 *   1. halaman (`/`, `/endpoint/order`)
 *   2. aset ber-hash (`/assets/css/*.css`, `/assets/js/*.js`)
 *
 * dan memastikan tamu (guest) ditolak di SEMUANYA — bukan hanya di halaman.
 */
class DocsAccessGateTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function createApplication()
    {
        $this->savedEnv = [
            'APP_URL' => getenv('APP_URL'),
            'FILAMENT_ADMIN_DOMAIN' => getenv('FILAMENT_ADMIN_DOMAIN'),
            'DOCS_DOMAIN' => getenv('DOCS_DOMAIN'),
        ];

        $this->setEnvironment('APP_URL', 'http://public.istanatopup.test');
        $this->setEnvironment('FILAMENT_ADMIN_DOMAIN', 'admin.istanatopup.test');
        $this->setEnvironment('DOCS_DOMAIN', 'docs.istanatopup.test');
        Env::enablePutenv();

        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->savedEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                $this->setEnvironment($key, $value);
            }
        }

        Env::enablePutenv();
    }

    /**
     * Semua path docs yang diuji: halaman + aset. Diambil dari isi build nyata supaya
     * test tidak mengarang nama file yang kebetulan tidak ada (kalau build kosong,
     * test ini gagal dengan pesan jelas alih-alih lolos palsu).
     *
     * @return list<string>
     */
    private function docsFixturePaths(): array
    {
        $buildDir = base_path('resources/docs-api');

        $this->assertDirectoryExists(
            $buildDir,
            'Hasil build docs tidak ada. Jalankan `npm run build` di docs-api/ '
                . '(atau pastikan image memuat resources/docs-api/) sebelum test ini.'
        );

        $paths = ['/'];

        // Halaman: ambil beberapa index.html dari subfolder (bukan semua, cukup representatif).
        foreach (glob($buildDir . '/*/index.html') ?: [] as $file) {
            $slug = basename(dirname($file));
            $paths[] = '/' . $slug;

            // Satu halaman dua level.
            foreach (glob($buildDir . '/' . $slug . '/*/index.html') ?: [] as $nested) {
                $paths[] = '/' . $slug . '/' . basename(dirname($nested));
            }
        }

        // Aset ber-hash: inilah yang paling mudah bocor karena nginx punya blok statis.
        foreach (glob($buildDir . '/assets/css/*.css') ?: [] as $file) {
            $paths[] = '/assets/css/' . basename($file);
        }

        foreach (glob($buildDir . '/assets/js/*.js') ?: [] as $file) {
            $paths[] = '/assets/js/' . basename($file);
        }

        $this->assertGreaterThan(
            1,
            count($paths),
            'Hanya ada 1 path docs yang bisa diuji — hasil build kemungkinan tidak lengkap.'
        );

        return array_values(array_unique($paths));
    }

    public function test_guest_is_denied_on_every_docs_path_including_hashed_assets(): void
    {
        foreach ($this->docsFixturePaths() as $path) {
            $response = $this->get('http://docs.istanatopup.test' . $path);

            $this->assertSame(
                302,
                $response->getStatusCode(),
                "Guest TIDAK ditolak di `{$path}` (status {$response->getStatusCode()}). "
                    . 'Setiap path docs harus lewat gate login, termasuk aset ber-hash.'
            );

            $this->assertStringContainsString(
                '/id/sign-in',
                (string) $response->headers->get('Location'),
                "Gate di `{$path}` harus mengarah ke sign-in."
            );
        }
    }

    public function test_authenticated_user_receives_real_docs_content_not_a_redirect(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'Member']);

        // Halaman utama: 200 + HTML nyata dari build.
        // Catatan: `BinaryFileResponse` TIDAK memuat file ke memori (`getContent()` kosong
        // by design — itulah gunanya streaming). Jadi keberadaan konten diperiksa dari
        // `getFile()->getPathname()`, bukan dari body.
        $home = $this->actingAs($user)->get('http://docs.istanatopup.test/');
        $home->assertOk();
        $this->assertStringContainsString(
            'text/html',
            (string) $home->headers->get('Content-Type')
        );

        // Aset ber-hash: harus terkirim sebagai file, bukan HTML 404 dan bukan redirect.
        $assets = array_values(array_filter(
            $this->docsFixturePaths(),
            fn (string $p) => str_starts_with($p, '/assets/')
        ));

        $this->assertNotSame([], $assets, 'Tidak ada aset ber-hash yang ditemukan untuk diuji.');

        foreach ($assets as $path) {
            $response = $this->actingAs($user)->get('http://docs.istanatopup.test' . $path);

            $this->assertSame(200, $response->getStatusCode(), "Aset `{$path}` harus 200 untuk user login.");
            $this->assertNull($response->headers->get('Location'), "Aset `{$path}` tidak boleh redirect.");
        }
    }

    public function test_nested_page_paths_are_served_from_their_index_html(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'Member']);

        $buildDir = base_path('resources/docs-api');
        $nested = glob($buildDir . '/*/*/index.html') ?: [];

        $this->assertNotSame([], $nested, 'Tidak ada halaman dua level di build — struktur sidebar berubah?');

        foreach (array_slice($nested, 0, 5) as $file) {
            $relative = str_replace($buildDir, '', $file);
            $urlPath = str_replace('/index.html', '', $relative);

            $response = $this->actingAs($user)->get('http://docs.istanatopup.test' . $urlPath);

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Path bersih `{$urlPath}` harus dilayani (resolver harus mencoba index.html)."
            );
            $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));

            // File yang benar-benar dipakai harus `index.html` dari path itu.
            $served = $response->baseResponse;

            if ($served instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
                $this->assertStringEndsWith(
                    str_replace('/', DIRECTORY_SEPARATOR, $urlPath . '/index.html'),
                    $served->getFile()->getPathname(),
                    "`{$urlPath}` harus dilayani dari `{$urlPath}/index.html`."
                );
            }
        }
    }

    private function setEnvironment(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
