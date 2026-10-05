<?php

namespace Tests\Feature;

use App\Http\Controllers\Controller;
use App\Support\PublicThemeRegistry;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

/**
 * Kontrak permukaan tema.
 *
 * Aturan: SETIAP controller di `app/Http/Controllers/Public/**` harus mengambil
 * salah satu dari dua sikap yang eksplisit:
 *
 *   1. IKUT TEMA  -> memakai gerbang `PublicThemeRegistry::rendersLegacyBlade()`
 *      (langsung, atau lewat `activeForEnvironment()`), sehingga theme `default`
 *      render legacy Blade dan theme modern render Inertia; ATAU
 *   2. REACT-ONLY -> terdaftar di
 *      `PublicThemeRegistry::INERTIA_ONLY_CONTROLLERS`, yang berarti memang
 *      belum ada view Blade-nya (lihat Tahap 3).
 *
 * Sikap ketiga — "selalu Inertia tapi tidak terdaftar" — adalah pelanggaran
 * senyap: halaman itu diam-diam keluar dari tema tanpa ada yang tahu. Test ini
 * menolaknya, jadi menambah controller React-only baru akan MEMBUAT CI MERAH
 * sampai keputusan sadar diambil.
 */
class PublicThemeSurfaceContractTest extends TestCase
{
    private const PUBLIC_CONTROLLER_PATH = 'app/Http/Controllers/Public';

    /** @return array<string, string> relatif path => isi file */
    private function publicControllerSources(): array
    {
        $base = base_path(self::PUBLIC_CONTROLLER_PATH);
        $sources = [];

        foreach (File::allFiles($base) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $sources[$file->getRelativePathname()] = File::get($file->getPathname());
        }

        return $sources;
    }

    private function classFromRelativePath(string $relativePath): string
    {
        $withoutExtension = preg_replace('/\.php$/', '', $relativePath);
        $namespacePath = str_replace('/', '\\', $withoutExtension);

        return 'App\\Http\\Controllers\\Public\\' . $namespacePath;
    }

    private function rendersInertia(string $source): bool
    {
        return str_contains($source, 'Inertia::render(');
    }

    /**
     * Apakah controller ini merender halaman (Blade atau Inertia)?
     * Controller JSON/redirect-only mengembalikan false.
     */
    private function rendersAnyPage(string $source): bool
    {
        if ($this->rendersInertia($source)) {
            return true;
        }

        if (str_contains($source, 'return view(') || str_contains($source, 'View::make(')) {
            return true;
        }

        // Pola ringkas: `Inertia::render(...)` dikembalikan tanpa `return`.
        return (bool) preg_match('/\b(view|View::make)\s*\(/', $source);
    }

    private function usesThemeGate(string $source): bool
    {
        return str_contains($source, 'rendersLegacyBlade')
            || str_contains($source, 'activeForEnvironment');
    }

    // -----------------------------------------------------------------
    // Kasus 1: controller publik yang render Inertia harus punya sikap
    // -----------------------------------------------------------------

    public function test_every_public_controller_rendering_inertia_is_either_theme_aware_or_declared_react_only(): void
    {
        $violations = [];
        $declared = [];

        foreach ($this->publicControllerSources() as $relativePath => $source) {
            if (! $this->rendersInertia($source)) {
                continue;
            }

            $class = $this->classFromRelativePath($relativePath);

            if ($this->usesThemeGate($source)) {
                continue;
            }

            if (PublicThemeRegistry::isInertiaOnlyController($class)) {
                $declared[] = $class;
                continue;
            }

            $violations[] = $class;
        }

        $this->assertSame(
            [],
            $violations,
            "Controller publik ini selalu render Inertia TANPA cabang tema dan TIDAK terdaftar di "
                . "PublicThemeRegistry::INERTIA_ONLY_CONTROLLERS, jadi halaman-nya keluar dari tema:\n  - "
                . implode("\n  - ", $violations)
                . "\nPilih: ikut tema (pakai rendersLegacyBlade) atau daftarkan sebagai React-only."
        );

        // Kontrak harus benar-benar dipakai, bukan daftar mati.
        $this->assertNotSame([], $declared, 'Tidak ada controller React-only yang terdeteksi — kontrak ini mungkin sudah basi.');
    }

    // -----------------------------------------------------------------
    // Kasus 2: entri daftar React-only harus nyata & masih relevan
    // -----------------------------------------------------------------

    public function test_declared_react_only_controllers_exist_and_do_not_use_the_theme_gate(): void
    {
        $stale = [];
        $nowThemeAware = [];

        foreach (PublicThemeRegistry::INERTIA_ONLY_CONTROLLERS as $class) {
            if (! class_exists($class)) {
                $stale[] = "{$class} (class tidak ada)";
                continue;
            }

            $reflection = new ReflectionClass($class);

            if (! $reflection->isSubclassOf(Controller::class)) {
                $stale[] = "{$class} (bukan turunan Controller)";
                continue;
            }

            $file = $reflection->getFileName();

            if ($file === false || ! is_readable($file)) {
                $stale[] = "{$class} (file tidak terbaca)";
                continue;
            }

            $source = File::get($file);

            if ($this->usesThemeGate($source)) {
                $nowThemeAware[] = $class;
            }
        }

        $this->assertSame([], $stale, "Entri PUBLIC_THEME React-only sudah basi:\n  - " . implode("\n  - ", $stale));

        $this->assertSame(
            [],
            $nowThemeAware,
            "Controller ini sudah ikut tema, jadi HARUS dihapus dari daftar React-only:\n  - "
                . implode("\n  - ", $nowThemeAware)
        );
    }

    // -----------------------------------------------------------------
    // Kasus 3: daftar harus menunjuk file publik yang benar-benar ada
    // -----------------------------------------------------------------

    public function test_react_only_list_only_contains_controllers_under_the_public_namespace(): void
    {
        $outside = [];

        foreach (PublicThemeRegistry::INERTIA_ONLY_CONTROLLERS as $class) {
            if (! str_starts_with($class, 'App\\Http\\Controllers\\Public\\')) {
                $outside[] = $class;
            }
        }

        $this->assertSame(
            [],
            $outside,
            "INERTIA_ONLY_CONTROLLERS harus berisi controller di namespace Public saja:\n  - " . implode("\n  - ", $outside)
        );
    }

    // -----------------------------------------------------------------
    // Kasus 4: setiap route publik punya controller yang punya sikap jelas
    // -----------------------------------------------------------------

    public function test_public_get_routes_resolve_to_controllers_with_a_declared_theme_stance(): void
    {
        $unknown = [];

        foreach (Route::getRoutes() as $route) {
            /** @var RoutingRoute $route */
            $action = $route->getActionName();

            if ($action === 'Closure' || ! str_contains($action, 'App\\Http\\Controllers\\Public\\')) {
                continue;
            }

            $controller = str_contains($action, '@') ? explode('@', $action)[0] : $action;

            if (! class_exists($controller)) {
                continue;
            }

            $reflection = new ReflectionClass($controller);
            $file = $reflection->getFileName();

            if ($file === false || ! is_readable($file)) {
                continue;
            }

            $source = File::get($file);
            $declared = PublicThemeRegistry::isInertiaOnlyController($controller);

            // Controller JSON/redirect-only tidak merender halaman, jadi tidak
            // punya urusan dengan tema (mis. endpoint notifikasi, rotate key).
            if (! $this->rendersAnyPage($source)) {
                continue;
            }

            // Untuk controller yang merender halaman: harus punya sikap — entah
            // gerbang tema di file-nya, atau terdaftar sebagai React-only.
            if (! $this->usesThemeGate($source) && ! $declared) {
                $unknown[] = "{$controller} ({$route->uri()})";
            }
        }

        $unknown = array_values(array_unique($unknown));

        $this->assertSame(
            [],
            $unknown,
            "Route publik ini ditangani controller yang merender halaman tanpa sikap tema yang jelas:\n  - " . implode("\n  - ", $unknown)
        );
    }
}
