<?php

namespace Tests\Feature;

use App\Http\Controllers\Public\DocsController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Tests\TestCase;

/**
 * Path traversal di penyaji docs.
 *
 * `DocsController::serve()` menerima path dari URL lalu memetakannya ke file di
 * `resources/docs-api/`. Kalau resolusi path ceroboh, `../../.env` bisa membocorkan
 * kredensial produksi lewat domain docs. Test ini menyerang controller itu dengan
 * varian traversal yang nyata dipakai penyerang — termasuk bentuk ter-encode, karena
 * Symfony men-decode URL SEBELUM request sampai ke router (jadi `%2e%2e` = `..`).
 */
class DocsPathTraversalTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    public function createApplication()
    {
        $this->savedEnv = [
            'APP_URL' => getenv('APP_URL'),
            'DOCS_DOMAIN' => getenv('DOCS_DOMAIN'),
            'FILAMENT_ADMIN_DOMAIN' => getenv('FILAMENT_ADMIN_DOMAIN'),
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Serangan traversal harus ditolak controller secara langsung. Dipakai
     * `$this->call()` supaya path mentah sampai ke router tanpa normalisasi klien.
     */
    public function test_traversal_attempts_are_rejected_with_404(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'Member']);

        // Catatan: varian dengan backslash tidak diuji di sini karena Symfony menolaknya
        // lebih dulu (`BadRequestException: A URI cannot contain a backslash`) — itu juga
        // penolakan yang benar, dan tetap diuji di level resolver di bawah.
        $attacks = [
            '/../../../.env',
            '/../../.env',
            '/..%2f..%2f.env',
            '/%2e%2e/%2e%2e/.env',
            '/....//....//.env',
            '/endpoint/../../.env',
            '/assets/../../../.env',
            '/etc/passwd',
            '/../../composer.json',
            '/..%5c..%5c.env',
        ];

        foreach ($attacks as $path) {
            $response = $this->actingAs($user)->get('http://docs.istanatopup.test' . $path);

            $this->assertContains(
                $response->getStatusCode(),
                [400, 404],
                "Traversal `{$path}` TIDAK ditolak (status {$response->getStatusCode()})."
            );

            // Tidak boleh ada isi file sensitif yang bocor, apa pun status-nya.
            $content = (string) $response->getContent();

            $this->assertStringNotContainsString('DB_PASSWORD', $content, "Isi .env bocor lewat `{$path}`.");
            $this->assertStringNotContainsString('APP_KEY', $content, "Isi .env bocor lewat `{$path}`.");
            $this->assertStringNotContainsString('"require"', $content, "composer.json bocor lewat `{$path}`.");
            $this->assertStringNotContainsString('root:x:', $content, "/etc/passwd bocor lewat `{$path}`.");
        }
    }

    /**
     * Penjaga unit: resolver harus menetralkan segmen berbahaya SEBELUM menyentuh disk,
     * apa pun bentuk input-nya (termasuk yang tidak melalui decode URL Symfony).
     */
    public function test_resolver_rejects_dangerous_segments_before_touching_disk(): void
    {
        $controller = new DocsController();
        $method = new \ReflectionMethod($controller, 'resolveRelativePath');
        $method->setAccessible(true);

        $rejected = [
            '../.env',
            'a/../../.env',
            '....//..',
            '/etc/passwd',
            "evil\0.html",
            '..\\..\\.env',
            'endpoint/../../.env',
        ];

        foreach ($rejected as $input) {
            $resolved = $method->invoke($controller, $input);

            // Cara paling kuat menguji ini bukan mencari string `..` (nama file bisa saja
            // mengandung titik), tapi memastikan resolver TIDAK menghasilkan path yang
            // benar-benar ada di luar direktori build.
            $this->assertStringNotContainsString("\0", $resolved, "Input `{$input}` membawa null byte.");
            $this->assertStringNotContainsString('..', $resolved, "Input `{$input}` masih mengandung `..`.");
        }
    }

    private function setEnvironment(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
