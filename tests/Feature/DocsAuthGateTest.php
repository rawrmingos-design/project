<?php

namespace Tests\Feature;

use App\Http\Controllers\Public\DocsController;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Kunci perilaku gate dokumentasi API (Fase 4a).
 *
 * Dua hal yang dijaga di sini:
 *
 * 1. **Tujuan asli dibawa setelah login.** Guest yang membuka halaman docs harus
 *    diarahkan ke sign-in DENGAN `?redirect=<path docs>`, sehingga setelah login
 *    dia kembali ke halaman docs yang dia tuju — bukan mendarat di dashboard.
 *
 *    Aplikasi ini TIDAK memakai `url.intended` di jalur login web; ia memakai
 *    trait `HandlesLoginRedirect` yang membaca `?redirect=`. Middleware karenanya
 *    harus mengisi `?redirect=`, bukan `redirect()->guest()` (yang hanya mengisi
 *    `url.intended` dan akan bocor ke `redirect()->intended()` login Filament admin).
 *
 * 2. **Tujuan tidak boleh lintas host.** `safeLoginRedirect()` sengaja hanya
 *    menerima path relatif; URL absolut (apalagi beda host) akan DITOLAK dan user
 *    balik ke dashboard. Karena docs hidup di host sendiri, target harus tetap
 *    path relatif supaya setelah login tetap nyangkut di host docs.
 */
class DocsAuthGateTest extends TestCase
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

    public function test_docs_route_still_guarded_and_bound_to_docs_host(): void
    {
        $route = Route::getRoutes()->getByName('docs.index');

        $this->assertNotNull($route, 'Route docs.index harus tetap ada selama Fase 4a.');
        $this->assertSame('docs.istanatopup.test', $route->getDomain());
        $this->assertContains(
            'auth.message:Anda harus login terlebih dahulu untuk mengakses dokumentasi API.',
            $route->gatherMiddleware(),
            'Route docs harus tetap di balik gate auth.message.'
        );
    }

    public function test_guest_on_docs_host_is_redirected_to_sign_in_with_intended_path(): void
    {
        $response = $this->get('http://docs.istanatopup.test/');

        $response->assertStatus(302);

        $location = $response->headers->get('Location');

        $this->assertNotNull($location);
        $this->assertStringContainsString('/id/sign-in', $location);
        $this->assertStringContainsString(
            'redirect=',
            $location,
            'Gate harus membawa ?redirect= supaya user kembali ke docs setelah login.'
        );

        // Tujuan yang dibawa harus path docs itu sendiri (relatif, tanpa host lain).
        $query = [];
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('redirect', $query);
        $this->assertSame('/', $query['redirect']);
    }

    public function test_intended_redirect_survives_login_and_returns_to_docs_host(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        // Simulasikan alur nyata: guest mampir ke docs, gate mengarahkan ke
        // sign-in sambil membawa `?redirect=`, lalu user mengikuti halaman itu
        // (itu yang menyimpan tujuan di session), lalu login di host yang sama.
        $gate = $this->get('http://docs.istanatopup.test/');
        $gate->assertStatus(302);

        $signInUrl = (string) $gate->headers->get('Location');
        $this->assertStringContainsString('/id/sign-in', $signInUrl);

        $this->get($signInUrl)->assertOk();

        $this->assertSame('/', session('auth.login.redirect'));

        $response = $this->post('http://docs.istanatopup.test/id/sign-in', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        // `redirect()->to($target)` di-resolve terhadap host request => tetap di host docs.
        $response->assertRedirect('http://docs.istanatopup.test');

        $this->assertNull(
            session('auth.login.redirect'),
            'Target harus dikonsumsi sekali pakai setelah login.'
        );
    }

    public function test_docs_controller_remains_the_canonical_docs_surface(): void
    {
        $route = Route::getRoutes()->getByName('docs.index');

        $this->assertNotNull($route);
        $this->assertSame(
            DocsController::class . '@serve',
            $route->getActionName(),
            'Permukaan docs kanonik harus DocsController::serve (penyaji statis), bukan Inertia lagi.'
        );
    }

    /**
     * Aset docs ikut di balik gate.
     *
     * Kalau catch-all hanya menutup `/` (bentuk lama), permintaan aset `.css`/`.js`
     * akan jatuh ke nginx dan dijawab 404 (file-nya tidak ada di `public/`) — atau
     * lebih buruk, dilayani tanpa login. Karena itu route harus catch-all, dan
     * hanya path yang memang milik aplikasi (`id/`, `api/`) yang dikecualikan.
     */
    public function test_docs_route_is_a_catch_all_that_excludes_app_paths(): void
    {
        $route = Route::getRoutes()->getByName('docs.index');

        $this->assertNotNull($route);

        $wheres = $route->wheres;

        $this->assertArrayHasKey('any', $wheres, 'Route docs harus catch-all ber-parameter `any`.');

        $pattern = $wheres['any'];

        // Path aplikasi harus DIBIARKAN lewat: kalau `id/sign-in` tertangkap catch-all,
        // halaman login ikut minta login lagi → redirect loop tanpa akhir.
        foreach (['id/sign-in', 'id/dashboard', 'api/v1/order'] as $appPath) {
            $this->assertSame(
                0,
                preg_match('#' . $pattern . '#', $appPath),
                "Path aplikasi `{$appPath}` TIDAK boleh ditangkap catch-all docs (bikin redirect loop)."
            );
        }

        // Sementara halaman & aset docs harus TERTANGKAP, supaya semuanya melewati gate login.
        foreach (['', 'endpoint/order', 'assets/css/main.abc123.css', 'webhook/payload'] as $docsPath) {
            $this->assertSame(
                1,
                preg_match('#' . $pattern . '#', $docsPath),
                "Path docs `{$docsPath}` harus ditangkap catch-all supaya ikut gate login."
            );
        }
    }

    private function setEnvironment(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
